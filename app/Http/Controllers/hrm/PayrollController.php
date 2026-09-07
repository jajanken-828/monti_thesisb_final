<?php

namespace App\Http\Controllers\hrm;

use App\Http\Controllers\Controller;
use App\Models\hrm\AttendanceLog;
use App\Models\hrm\EmployeeShift;
use App\Models\hrm\Holiday;
use App\Models\hrm\LeaveRequest;
use App\Models\hrm\Payroll;
use App\Models\hrm\PayrollSet;
use App\Models\hrm\PayrollRate;
use App\Models\core\User;
use App\Models\hrm\GovernmentContributionRate;
use App\Traits\HasPagePermissions;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PayrollController extends Controller
{
    use HasPagePermissions;

    /**
     * Display the payroll list with statistics.
     */
    public function index()
    {
        $payrollData = Payroll::latest()->get()->map(function ($payroll) {
            return [
                'id' => $payroll->id,
                'employee_id' => $payroll->employee_id,
                'employee_name' => $payroll->employee_name,
                'role' => $payroll->role,
                'base_salary' => $payroll->base_salary,
                'days_worked' => $payroll->days_worked,
                'daily_rate' => $payroll->daily_rate,
                'total_days_amt' => $payroll->total_days_amt,
                'overtime_hours' => $payroll->overtime_hours,
                'ot_rate' => $payroll->ot_rate,
                'ot_amt' => $payroll->ot_amt,
                'night_hours' => $payroll->night_hours,
                'night_rate' => $payroll->night_rate,
                'night_amt' => $payroll->night_amt,
                'sunday_restday_hours' => $payroll->sunday_restday_hours,
                'sun_sp_rate' => $payroll->sun_sp_rate,
                'sun_sp_amt' => $payroll->sun_sp_amt,
                'holiday_amt' => $payroll->holiday_amt,
                'late_minutes' => $payroll->late_minutes,
                'late_rate_min' => $payroll->late_rate_min,
                'late_total_deduction' => $payroll->late_total_deduction,
                'sss_deduction' => $payroll->sss_deduction,
                'philhealth_deduction' => $payroll->philhealth_deduction,
                'pagibig_deduction' => $payroll->pagibig_deduction,
                'sss_loan' => $payroll->sss_loan,
                'pf_loan' => $payroll->pf_loan,
                'gross_pay' => $payroll->gross_pay,
                'net_pay' => $payroll->net_pay,
                'status' => $payroll->status,
                'created_at' => $payroll->created_at,
            ];
        });

        $permissions = $this->getPagePermissionsForModule('HRM');

        $employees = User::where('is_active', true)
            ->whereIn('position', ['staff', 'manager'])
            ->select('id', 'name', 'position as role')
            ->get();

        return Inertia::render('Dashboard/HRM/Payroll', [
            'payroll_data' => $payrollData,
            'stats' => [
                'total_payout' => Payroll::where('status', 'approved')->sum('net_pay'),
                'pending_approvals' => Payroll::where('status', 'pending')->count(),
            ],
            'permissions' => $permissions,
            'employees' => $employees,
        ]);
    }

    /**
     * Show the payroll rates page.
     */
    public function rates()
    {
        $rates = PayrollRate::first();
        if (!$rates) {
            $rates = PayrollRate::create([]);
        }

        return Inertia::render('Dashboard/HRM/PayrollRates', [
            'rates' => $rates
        ]);
    }

    /**
     * Update payroll rates.
     */
    public function updateRates(Request $request)
    {
        $validated = $request->validate([
            'daily_rate' => 'required|numeric|min:0',
            'daily_rate_usd' => 'nullable|numeric|min:0',
            'overtime_rate' => 'required|numeric|min:0|max:300',
            'night_diff_rate' => 'required|numeric|min:0|max:200',
            'holiday_rate' => 'required|numeric|min:0|max:300',
            'special_holiday_rate' => 'required|numeric|min:0|max:300',
            'rest_day_rate' => 'required|numeric|min:0|max:300',
            'late_deduction_per_minute' => 'required|numeric|min:0',
            'sss_rate' => 'nullable|numeric|min:0|max:100',
            'philhealth_rate' => 'nullable|numeric|min:0|max:100',
            'pagibig_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $rates = PayrollRate::first();
        if (!$rates) {
            $rates = new PayrollRate();
        }

        $rates->fill($validated);
        $rates->save();

        return back()->with('success', 'Payroll rates updated successfully.');
    }

    /**
     * Show the form for generating payroll (or return data for modal).
     */
    public function create()
    {
        $employees = User::where('is_active', true)
            ->whereIn('position', ['staff', 'manager'])
            ->select('id', 'name', 'position as role')
            ->get();

        return Inertia::render('Dashboard/HRM/GeneratePayroll', [
            'employees' => $employees,
        ]);
    }

    /**
     * Generate payroll for selected employees within a cutoff period.
     */
    public function generate(Request $request)
    {
        $request->validate([
            'cutoff_start' => 'required|date',
            'cutoff_end'   => 'required|date|after_or_equal:cutoff_start',
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:users,id',
        ]);

        $start = Carbon::parse($request->cutoff_start);
        $end   = Carbon::parse($request->cutoff_end);
        $period = CarbonPeriod::create($start, $end);

        $employees = User::whereIn('id', $request->employee_ids)->get();
        $payrollRecords = [];

        DB::transaction(function () use ($employees, $period, &$payrollRecords) {
            foreach ($employees as $employee) {
                // ✅ Skip if employee_id is missing (prevents SQL error)
                if (empty($employee->employee_id)) {
                    Log::warning("Payroll generation skipped for user ID {$employee->id} ({$employee->name}) - missing employee_id.");
                    continue;
                }

                // Get active payroll set for employee type (staff/manager)
                $payrollSet = PayrollSet::forType($employee->position)->first();
                if (!$payrollSet) {
                    Log::warning("Payroll generation skipped for user ID {$employee->id} - no active payroll set for position '{$employee->position}'.");
                    continue;
                }

                // Compute attendance summary for the cutoff
                $summary = $this->computeAttendanceSummary($employee, $period, $payrollSet);

                // Calculate gross pay
                $grossPay = $summary['total_days_amt'] +
                            $summary['ot_amt'] +
                            $summary['night_amt'] +
                            $summary['sun_sp_amt'] +
                            $summary['holiday_amt'];

                // Deductions
                $lateDeduction = $summary['late_total_deduction'];
                $sss = $this->computeSSS($grossPay);
                $philhealth = $this->computePhilHealth($grossPay);
                $pagibig = $this->computePagIBIG($grossPay);
                $tax = $this->computeWithholdingTax($grossPay, $payrollSet->base_salary);

                $totalDeductions = $lateDeduction + $sss + $philhealth + $pagibig + $tax;
                $netPay = $grossPay - $totalDeductions;

                // Create payroll record
                $payroll = Payroll::create([
                    'employee_id'            => $employee->employee_id,
                    'employee_name'          => $employee->name,
                    'role'                   => $employee->position === 'manager' ? 'Manager' : 'Staff',
                    'base_salary'            => $payrollSet->base_salary,
                    'days_worked'            => $summary['days_worked'],
                    'daily_rate'             => $summary['daily_rate'],
                    'total_days_amt'         => $summary['total_days_amt'],
                    'night_hours'            => $summary['night_hours'],
                    'night_rate'             => $summary['night_rate'],
                    'night_amt'              => $summary['night_amt'],
                    'overtime_hours'         => $summary['overtime_hours'],
                    'ot_rate'                => $summary['ot_rate'],
                    'ot_amt'                 => $summary['ot_amt'],
                    'sunday_restday_hours'   => $summary['sunday_restday_hours'],
                    'sun_sp_rate'            => $summary['sun_sp_rate'],
                    'sun_sp_amt'             => $summary['sun_sp_amt'],
                    'holiday_amt'            => $summary['holiday_amt'],
                    'late_minutes'           => $summary['late_minutes'],
                    'late_rate_min'          => $summary['late_rate_min'],
                    'late_total_deduction'   => $lateDeduction,
                    'sss_deduction'          => $sss,
                    'philhealth_deduction'   => $philhealth,
                    'pagibig_deduction'      => $pagibig,
                    'tax_withheld'           => $tax,
                    'sss_loan'               => 0, // Placeholder – integrate with loans table if needed
                    'pf_loan'                => 0,
                    'gross_pay'              => $grossPay,
                    'net_pay'                => $netPay,
                    'status'                 => 'pending',
                ]);

                $payrollRecords[] = $payroll;
            }
        });

        return redirect()->route('hrm.payroll')->with('message', count($payrollRecords) . ' payroll records generated.');
    }

    /**
     * Display the specified payroll record.
     */
    public function show(Payroll $payroll)
    {
        return Inertia::render('Dashboard/HRM/PayrollDetail', [
            'payroll' => $payroll,
        ]);
    }

    /**
     * Approve a pending payroll record.
     */
    public function approve(Payroll $payroll)
    {
        $payroll->update(['status' => 'approved']);
        return back()->with('message', 'Payroll approved.');
    }

    /**
     * Reject a pending payroll record.
     */
    public function reject(Request $request, Payroll $payroll)
    {
        $request->validate(['reason' => 'nullable|string']);
        $payroll->update(['status' => 'rejected']);
        // Optionally store rejection reason in a notes field or log
        return back()->with('message', 'Payroll rejected.');
    }

    /**
     * Core computation of attendance summary for a given employee and period.
     */
    private function computeAttendanceSummary($employee, $period, $payrollSet)
    {
        $dailyRate = $payrollSet->base_salary / 26; // DOLE standard divisor
        $daysWorked = 0;
        $totalDaysAmt = 0;
        $lateMinutesTotal = 0;
        $lateDeductionTotal = 0;
        $overtimeHours = 0;
        $otAmt = 0;
        $nightHours = 0;
        $nightAmt = 0;
        $sundayHours = 0;
        $sunAmt = 0;
        $holidayAmt = 0;

        $dates = $period->toArray();
        foreach ($dates as $date) {
            $dateStr = $date->toDateString();

            // Skip if on approved leave
            $onLeave = LeaveRequest::where('user_id', $employee->id)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $dateStr)
                ->whereDate('end_date', '>=', $dateStr)
                ->exists();
            if ($onLeave) continue;

            // Get assigned shift
            $shift = EmployeeShift::where('user_id', $employee->id)
                ->where('effective_date', $dateStr)
                ->first();
            if (!$shift) continue; // rest day / no shift

            // Get attendance log
            $log = AttendanceLog::where('user_id', $employee->id)
                ->where('date', $dateStr)
                ->first();

            if (!$log || !$log->clock_in) {
                continue; // absent
            }

            $daysWorked++;
            $totalDaysAmt += $dailyRate;

            // Shift times
            $shiftStart = Carbon::parse($dateStr . ' ' . $this->getShiftStart($shift->shift_type), 'Asia/Manila');
            $shiftEnd   = Carbon::parse($dateStr . ' ' . $this->getShiftEnd($shift->shift_type), 'Asia/Manila');
            if ($shiftEnd->lt($shiftStart)) {
                $shiftEnd->addDay();
            }

            $clockIn  = Carbon::parse($dateStr . ' ' . $log->clock_in, 'Asia/Manila');
            $clockOut = $log->clock_out ? Carbon::parse($dateStr . ' ' . $log->clock_out, 'Asia/Manila') : $shiftEnd;

            // Late deduction
            if ($clockIn->gt($shiftStart)) {
                $lateMins = $clockIn->diffInMinutes($shiftStart);
                $lateMinutesTotal += $lateMins;
                $lateDeductionTotal += $lateMins * $payrollSet->late_rate_per_minute;
            }

            // Overtime
            if ($clockOut->gt($shiftEnd)) {
                $otMins = $clockOut->diffInMinutes($shiftEnd);
                $otHours = $otMins / 60;
                $overtimeHours += $otHours;
                $hourlyRate = $dailyRate / 8;
                $otAmt += $otHours * $hourlyRate * $payrollSet->ot_rate;
            }

            // Night differential (10PM – 6AM)
            $nightMins = $this->calculateNightMinutes($clockIn, $clockOut);
            $nightHrs = $nightMins / 60;
            $nightHours += $nightHrs;
            $nightAmt += $nightHrs * ($dailyRate / 8) * $payrollSet->night_rate;

            // Sunday / Special non-working holiday premium
            if ($date->dayOfWeek === Carbon::SUNDAY || $this->isHoliday($dateStr, 'special_non_working')) {
                $hoursWorked = $clockIn->diffInHours($clockOut);
                $sundayHours += $hoursWorked;
                $sunAmt += $hoursWorked * ($dailyRate / 8) * $payrollSet->sunday_rate;
            }

            // Regular holiday premium
            $holiday = Holiday::where('holiday_date', $dateStr)
                ->where('holiday_type', 'regular')
                ->first();
            if ($holiday) {
                $holidayAmt += $dailyRate * $holiday->premium_rate;
            }
        }

        return [
            'days_worked'            => $daysWorked,
            'daily_rate'             => $dailyRate,
            'total_days_amt'         => $totalDaysAmt,
            'late_minutes'           => $lateMinutesTotal,
            'late_rate_min'          => $payrollSet->late_rate_per_minute,
            'late_total_deduction'   => $lateDeductionTotal,
            'overtime_hours'         => $overtimeHours,
            'ot_rate'                => $payrollSet->ot_rate,
            'ot_amt'                 => $otAmt,
            'night_hours'            => $nightHours,
            'night_rate'             => $payrollSet->night_rate,
            'night_amt'              => $nightAmt,
            'sunday_restday_hours'   => $sundayHours,
            'sun_sp_rate'            => $payrollSet->sunday_rate,
            'sun_sp_amt'             => $sunAmt,
            'holiday_amt'            => $holidayAmt,
        ];
    }

    private function getShiftStart($type): string
    {
        return match ($type) {
            'Morning'   => '08:00:00',
            'Afternoon' => '16:00:00',
            'Graveyard' => '00:00:00',
            default     => '08:00:00',
        };
    }

    private function getShiftEnd($type): string
    {
        return match ($type) {
            'Morning'   => '17:00:00',
            'Afternoon' => '01:00:00',
            'Graveyard' => '09:00:00',
            default     => '17:00:00',
        };
    }

    private function calculateNightMinutes($clockIn, $clockOut): int
    {
        $nightStart = $clockIn->copy()->setTime(22, 0);
        $nightEnd   = $clockIn->copy()->addDay()->setTime(6, 0);
        $overlapStart = $clockIn->max($nightStart);
        $overlapEnd   = $clockOut->min($nightEnd);
        return $overlapStart->lt($overlapEnd) ? $overlapStart->diffInMinutes($overlapEnd) : 0;
    }

    private function isHoliday($date, $type): bool
    {
        return Holiday::where('holiday_date', $date)->where('holiday_type', $type)->exists();
    }

    /**
     * Government contributions based on monthly gross pay.
     */
    private function computeSSS($monthlyGross)
    {
        $msc = $this->getMSC($monthlyGross);
        $rate = GovernmentContributionRate::ofType('sss')
            ->where('min_compensation', '<=', $msc)
            ->where('max_compensation', '>=', $msc)
            ->first();
        return $rate ? $msc * ($rate->percentage / 100) : 0;
    }

    private function computePhilHealth($monthlyGross)
    {
        $base = max(10000, min(100000, $monthlyGross));
        return $base * 0.025; // 2.5% employee share
    }

    private function computePagIBIG($monthlyGross)
    {
        return min($monthlyGross * 0.02, 200);
    }

    private function computeWithholdingTax($monthlyGross, $baseSalary)
    {
        // TRAIN Law annualized computation
        $annual = $monthlyGross * 12;
        if ($annual <= 250000) return 0;
        if ($annual <= 400000) return ($annual - 250000) * 0.15 / 12;
        if ($annual <= 800000) return (22500 + ($annual - 400000) * 0.20) / 12;
        if ($annual <= 2000000) return (102500 + ($annual - 800000) * 0.25) / 12;
        if ($annual <= 8000000) return (402500 + ($annual - 2000000) * 0.30) / 12;
        return (2202500 + ($annual - 8000000) * 0.35) / 12;
    }

    private function getMSC($monthly)
    {
        $msc = round($monthly / 500) * 500;
        return max(4000, min(30000, $msc));
    }
}