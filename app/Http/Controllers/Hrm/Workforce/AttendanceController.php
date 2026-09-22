<?php

namespace App\Http\Controllers\Hrm\Workforce;

use App\Http\Controllers\Controller;
use App\Models\Core\User;
use App\Models\Hrm\AttendanceLog;
use App\Models\Hrm\HrmDepartment;
use App\Models\Hrm\LeaveRequest;
use App\Traits\HasPagePermissions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * HRM attendance overview. Reads the canonical `attendance_logs`
 * written by the employee clock (Users\ClockController) — no new table.
 */
class AttendanceController extends Controller
{
    use HasPagePermissions;

    public function index(Request $request)
    {
        $date = $request->get('date', Carbon::today('Asia/Manila')->toDateString());

        $q = AttendanceLog::with('user')->where('date', $date);
        if ($s = $request->get('search')) {
            $q->whereHas('user', fn ($w) => $w->where('name', 'like', "%{$s}%"));
        }
        if ($dept = $request->get('department')) {
            $q->whereHas('user', fn ($w) => $w->where('role', $dept)->orWhere('department', $dept));
        }

        $paginated = $q->latest()->paginate(15)->withQueryString();
        $records = collect($paginated->items())->filter(fn ($l) => $l !== null)->map(fn ($log) => [
            'id' => $log->id,
            'user_id' => $log->user_id,
            'employee' => $log->user?->name ?? ('User #' . $log->user_id),
            'department' => $log->user?->role,
            'date' => $log->date,
            'time_in' => $log->clock_in ?: '—',
            'time_out' => $log->clock_out ?: '—',
            'hours' => $this->hours($log->clock_in, $log->clock_out),
            'ot' => $this->ot($log->clock_in, $log->clock_out),
            'status' => $this->display($log->status),
        ])->values()->all();

        $dayLogs = AttendanceLog::where('date', $date)->get();
        $onLeave = LeaveRequest::where('status', 'approved')
            ->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date)->count();

        return Inertia::render('Dashboard/HRM_NEW/Attendance', [
            'records' => $records,
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
            'metrics' => [
                ['label' => 'Present Today', 'value' => (string) $dayLogs->whereIn('status', ['On-Time', 'Present'])->count()],
                ['label' => 'Late', 'value' => (string) $dayLogs->where('status', 'Late')->count()],
                ['label' => 'Absent', 'value' => (string) $dayLogs->where('status', 'Absent')->count()],
                ['label' => 'On Leave', 'value' => (string) $onLeave],
            ],
            'filters' => ['date' => $date, 'search' => $request->get('search', ''), 'department' => $request->get('department', '')],
            'departments' => HrmDepartment::whereNull('archived_at')->select('code', 'name')->get(),
            'permissions' => $this->getPagePermissionsForModule('HRM'),
        ]);
    }

    public function export(Request $request)
    {
        $date = $request->get('date', Carbon::today('Asia/Manila')->toDateString());

        return response()->streamDownload(function () use ($date) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['employee', 'date', 'time_in', 'time_out', 'hours', 'ot', 'status']);
            AttendanceLog::with('user')->where('date', $date)->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $log) {
                    fputcsv($out, [
                        $log->user?->name, $log->date, $log->clock_in, $log->clock_out,
                        $this->hours($log->clock_in, $log->clock_out),
                        $this->ot($log->clock_in, $log->clock_out), $log->status,
                    ]);
                }
            });
            fclose($out);
        }, "attendance-{$date}.csv");
    }

    private function display(?string $status): string
    {
        return match (strtolower((string) $status)) {
            'on-time', 'present' => 'Present',
            'late' => 'Late',
            default => 'Absent',
        };
    }

    private function worked(?string $in, ?string $out): ?float
    {
        if (! $in || ! $out) {
            return null;
        }
        try {
            $start = Carbon::createFromFormat('h:i A', $in);
            $end = Carbon::createFromFormat('h:i A', $out);
            if ($end->lessThan($start)) {
                $end->addDay();
            }
            return round($start->floatDiffInHours($end), 1);
        } catch (\Throwable) {
            return null;
        }
    }

    private function hours(?string $in, ?string $out): string
    {
        $h = $this->worked($in, $out);
        return $h === null ? '0' : (string) $h;
    }

    private function ot(?string $in, ?string $out): float
    {
        $h = $this->worked($in, $out);
        return $h === null ? 0 : round(max(0, $h - 8), 1);
    }
}
