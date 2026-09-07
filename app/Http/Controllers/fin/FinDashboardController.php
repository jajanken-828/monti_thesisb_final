<?php

namespace App\Http\Controllers\fin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class FinDashboardController extends Controller
{
    /**
     * ------------------------------------------------------------------
     * DUMMY DATA ONLY — no fin_* tables exist yet (no migrations).
     * Everything below is hardcoded sample data for Monti Textile so the
     * Finance module is viewable. Replace with Eloquent queries once the
     * finance migrations/models are created.
     * ------------------------------------------------------------------
     */
    private function dummy(): array
    {
        return [
            'stats' => [
                'revenueYtd' => 18450000,
                'revenueMonth' => 1725000,
                'outstandingAR' => 3250000,
                'overdueAR' => 640000,
                'outstandingAP' => 2180000,
                'overdueAP' => 310000,
                'cashOnHand' => 5620000,
                'netProfitMonth' => 386000,
                'payrollMonth' => 1240000,
                'expenseMonth' => 1339000,
            ],
            'revenueTrend' => [
                ['month' => 'Mar', 'revenue' => 1350000, 'expenses' => 1020000],
                ['month' => 'Apr', 'revenue' => 1420000, 'expenses' => 1080000],
                ['month' => 'May', 'revenue' => 1510000, 'expenses' => 1130000],
                ['month' => 'Jun', 'revenue' => 1480000, 'expenses' => 1150000],
                ['month' => 'Jul', 'revenue' => 1630000, 'expenses' => 1210000],
                ['month' => 'Aug', 'revenue' => 1725000, 'expenses' => 1339000],
            ],
            'cashFlow' => [
                ['label' => 'Client collections', 'inflow' => 1580000, 'outflow' => 0],
                ['label' => 'Supplier payments', 'inflow' => 0, 'outflow' => 640000],
                ['label' => 'Payroll payout', 'inflow' => 0, 'outflow' => 1240000],
                ['label' => 'Utilities & rent', 'inflow' => 0, 'outflow' => 285000],
                ['label' => 'Dye chemicals', 'inflow' => 0, 'outflow' => 196000],
                ['label' => 'Other income', 'inflow' => 145000, 'outflow' => 0],
            ],
            'arAging' => [
                ['bucket' => 'Current', 'amount' => 1890000, 'count' => 14],
                ['bucket' => '1–30 days', 'amount' => 720000, 'count' => 6],
                ['bucket' => '31–60 days', 'amount' => 410000, 'count' => 4],
                ['bucket' => '60+ days', 'amount' => 230000, 'count' => 2],
            ],
            'apAging' => [
                ['bucket' => 'Current', 'amount' => 1320000, 'count' => 11],
                ['bucket' => '1–30 days', 'amount' => 550000, 'count' => 5],
                ['bucket' => '31–60 days', 'amount' => 220000, 'count' => 2],
                ['bucket' => '60+ days', 'amount' => 90000, 'count' => 1],
            ],
            'receivables' => [
                ['id' => 1, 'invoice_no' => 'INV-2026-0841', 'client' => 'Glamour Garments Inc.', 'amount' => 485000, 'paid' => 200000, 'due_date' => '2026-09-18', 'status' => 'partial'],
                ['id' => 2, 'invoice_no' => 'INV-2026-0837', 'client' => 'Metro Retail Group', 'amount' => 720000, 'paid' => 0, 'due_date' => '2026-09-10', 'status' => 'overdue'],
                ['id' => 3, 'invoice_no' => 'INV-2026-0844', 'client' => 'ExportLine Corp.', 'amount' => 950000, 'paid' => 0, 'due_date' => '2026-09-30', 'status' => 'unpaid'],
                ['id' => 4, 'invoice_no' => 'INV-2026-0829', 'client' => 'Cebu Apparel Co.', 'amount' => 310000, 'paid' => 310000, 'due_date' => '2026-08-28', 'status' => 'paid'],
                ['id' => 5, 'invoice_no' => 'INV-2026-0831', 'client' => 'Davao Fashion Hub', 'amount' => 265000, 'paid' => 0, 'due_date' => '2026-09-05', 'status' => 'overdue'],
                ['id' => 6, 'invoice_no' => 'INV-2026-0847', 'client' => 'Glamour Garments Inc.', 'amount' => 520000, 'paid' => 0, 'due_date' => '2026-10-12', 'status' => 'unpaid'],
            ],
            'payables' => [
                ['id' => 1, 'bill_no' => 'BILL-2026-0512', 'supplier' => 'Prime Yarn Supply', 'category' => 'Raw materials', 'amount' => 640000, 'paid' => 0, 'due_date' => '2026-09-15', 'status' => 'unpaid'],
                ['id' => 2, 'bill_no' => 'BILL-2026-0498', 'supplier' => 'ColorChem Trading', 'category' => 'Dye chemicals', 'amount' => 196000, 'paid' => 0, 'due_date' => '2026-09-02', 'status' => 'overdue'],
                ['id' => 3, 'bill_no' => 'BILL-2026-0505', 'supplier' => 'Meralco / Utilities', 'category' => 'Utilities', 'amount' => 285000, 'paid' => 285000, 'due_date' => '2026-08-30', 'status' => 'paid'],
                ['id' => 4, 'bill_no' => 'BILL-2026-0519', 'supplier' => 'Speed Freight Corp.', 'category' => 'Logistics', 'amount' => 114000, 'paid' => 0, 'due_date' => '2026-09-25', 'status' => 'unpaid'],
                ['id' => 5, 'bill_no' => 'BILL-2026-0489', 'supplier' => 'Prime Yarn Supply', 'category' => 'Raw materials', 'amount' => 420000, 'paid' => 210000, 'due_date' => '2026-09-08', 'status' => 'partial'],
            ],
            'expenses' => [
                ['id' => 1, 'date' => '2026-08-04', 'category' => 'Raw materials', 'description' => 'Cotton yarn bulk purchase', 'amount' => 640000, 'department' => 'Production'],
                ['id' => 2, 'date' => '2026-08-09', 'category' => 'Payroll', 'description' => 'Plant workers mid-month payout', 'amount' => 620000, 'department' => 'HR'],
                ['id' => 3, 'date' => '2026-08-14', 'category' => 'Dye chemicals', 'description' => 'Reactive dyes + softeners', 'amount' => 196000, 'department' => 'Dyeing'],
                ['id' => 4, 'date' => '2026-08-20', 'category' => 'Utilities', 'description' => 'Electricity + water', 'amount' => 285000, 'department' => 'Facilities'],
                ['id' => 5, 'date' => '2026-08-22', 'category' => 'Logistics', 'description' => 'Outbound freight', 'amount' => 114000, 'department' => 'Logistics'],
                ['id' => 6, 'date' => '2026-08-27', 'category' => 'Maintenance', 'description' => 'Squeezer machine parts', 'amount' => 68000, 'department' => 'Maintenance'],
            ],
            'expenseCategories' => [
                ['category' => 'Raw materials', 'amount' => 640000],
                ['category' => 'Payroll', 'amount' => 620000],
                ['category' => 'Utilities', 'amount' => 285000],
                ['category' => 'Dye chemicals', 'amount' => 196000],
                ['category' => 'Logistics', 'amount' => 114000],
                ['category' => 'Maintenance', 'amount' => 68000],
            ],
            'payroll' => [
                ['department' => 'Knitting', 'headcount' => 18, 'gross' => 378000, 'deductions' => 42000, 'net' => 336000, 'status' => 'paid'],
                ['department' => 'Dyeing', 'headcount' => 24, 'gross' => 504000, 'deductions' => 58000, 'net' => 446000, 'status' => 'paid'],
                ['department' => 'Packaging', 'headcount' => 12, 'gross' => 216000, 'deductions' => 24000, 'net' => 192000, 'status' => 'processing'],
                ['department' => 'Warehouse', 'headcount' => 9, 'gross' => 171000, 'deductions' => 19000, 'net' => 152000, 'status' => 'processing'],
                ['department' => 'Office', 'headcount' => 11, 'gross' => 275000, 'deductions' => 33000, 'net' => 242000, 'status' => 'pending'],
            ],
            'budgets' => [
                ['department' => 'Production', 'allocated' => 900000, 'spent' => 836000],
                ['department' => 'Dyeing', 'allocated' => 350000, 'spent' => 264000],
                ['department' => 'Logistics', 'allocated' => 200000, 'spent' => 114000],
                ['department' => 'Facilities', 'allocated' => 320000, 'spent' => 285000],
            ],
            'recentTransactions' => [
                ['id' => 1, 'date' => '2026-09-05', 'description' => 'Collection — Metro Retail (INV-2026-0835)', 'type' => 'inflow', 'amount' => 350000],
                ['id' => 2, 'date' => '2026-09-04', 'description' => 'Yarn payment — Prime Yarn (BILL-2026-0489)', 'type' => 'outflow', 'amount' => 210000],
                ['id' => 3, 'date' => '2026-09-03', 'description' => 'Payroll payout — Dyeing dept', 'type' => 'outflow', 'amount' => 446000],
                ['id' => 4, 'date' => '2026-09-02', 'description' => 'Collection — Cebu Apparel (INV-2026-0829)', 'type' => 'inflow', 'amount' => 310000],
                ['id' => 5, 'date' => '2026-09-01', 'description' => 'Utilities — Meralco', 'type' => 'outflow', 'amount' => 285000],
            ],
            'profitLoss' => [
                ['line' => 'Sales revenue', 'amount' => 1725000, 'section' => 'income'],
                ['line' => 'Other income', 'amount' => 145000, 'section' => 'income'],
                ['line' => 'Raw materials', 'amount' => -640000, 'section' => 'cogs'],
                ['line' => 'Direct labor', 'amount' => -620000, 'section' => 'cogs'],
                ['line' => 'Utilities', 'amount' => -285000, 'section' => 'opex'],
                ['line' => 'Dye chemicals', 'amount' => -196000, 'section' => 'opex'],
                ['line' => 'Logistics', 'amount' => -114000, 'section' => 'opex'],
                ['line' => 'Maintenance', 'amount' => -68000, 'section' => 'opex'],
            ],
        ];
    }

    public function managerDashboard()
    {
        $d = $this->dummy();

        return Inertia::render('Dashboard/FIN/Manager/index', [
            'user' => auth()->user(),
            'stats' => $d['stats'],
            'revenueTrend' => $d['revenueTrend'],
            'cashFlow' => $d['cashFlow'],
            'arAging' => $d['arAging'],
            'apAging' => $d['apAging'],
            'recentTransactions' => $d['recentTransactions'],
            'budgets' => $d['budgets'],
            'isDummy' => true,
        ]);
    }

    public function staffDashboard()
    {
        $d = $this->dummy();

        return Inertia::render('Dashboard/FIN/Employee/index', [
            'user' => auth()->user(),
            'stats' => $d['stats'],
            'receivables' => $d['receivables'],
            'payables' => $d['payables'],
            'recentTransactions' => $d['recentTransactions'],
            'isDummy' => true,
        ]);
    }

    public function receivables()
    {
        $d = $this->dummy();

        return Inertia::render('Dashboard/FIN/Manager/Receivables', [
            'receivables' => $d['receivables'],
            'arAging' => $d['arAging'],
            'stats' => $d['stats'],
            'isDummy' => true,
        ]);
    }

    public function payables()
    {
        $d = $this->dummy();

        return Inertia::render('Dashboard/FIN/Manager/Payables', [
            'payables' => $d['payables'],
            'apAging' => $d['apAging'],
            'stats' => $d['stats'],
            'isDummy' => true,
        ]);
    }

    public function expenses()
    {
        $d = $this->dummy();

        return Inertia::render('Dashboard/FIN/Manager/Expenses', [
            'expenses' => $d['expenses'],
            'expenseCategories' => $d['expenseCategories'],
            'stats' => $d['stats'],
            'isDummy' => true,
        ]);
    }

    public function payroll()
    {
        $d = $this->dummy();

        return Inertia::render('Dashboard/FIN/Manager/Payroll', [
            'payroll' => $d['payroll'],
            'stats' => $d['stats'],
            'isDummy' => true,
        ]);
    }

    public function reports()
    {
        $d = $this->dummy();

        return Inertia::render('Dashboard/FIN/Manager/Reports', [
            'profitLoss' => $d['profitLoss'],
            'revenueTrend' => $d['revenueTrend'],
            'stats' => $d['stats'],
            'isDummy' => true,
        ]);
    }
}
