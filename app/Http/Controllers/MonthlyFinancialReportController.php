<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\MonthlyFinancialReport;
use App\Models\MonthlyFinancialReportItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MonthlyFinancialReportController extends Controller
{
    public function index(Request $request): Response
    {
        $branches = Branch::orderBy('name')->get();

        $branchId = $request->integer('branch_id') ?: $branches->first()?->id;
        $month = $request->integer('month') ?: now()->month;
        $year = $request->integer('year') ?: now()->year;

        $report = null;
        $summary = [
            'employee_total' => 0,
            'employee_contributor_total' => 0,
            'monthly_target_amount' => 0,
            'opening_balance' => 0,
            'central_fund_received' => 0,
            'mandatory_amount' => 0,
            'total_sent_amount' => 0,
            'sent_date' => now()->toDateString(),
            'notes' => null,
        ];

        $expenses = collect();
        $proposals = collect();

        if ($branchId) {
            $report = MonthlyFinancialReport::with([
                'branch',
                'items' => fn ($q) => $q->orderByRaw('COALESCE(sort_order, 999999), id'),
            ])
                ->where('branch_id', $branchId)
                ->where('month', $month)
                ->where('year', $year)
                ->first();

            // Sementara global, karena students/pics belum punya branch_id
            $employeeTotal = DB::table('students')->count();

            $employeeContributorTotal = DB::table('bills')
                ->join('students', 'students.id', '=', 'bills.student_id')
                ->join('bill_items', 'bill_items.bill_id', '=', 'bills.id')
                ->join('products', 'products.id', '=', 'bill_items.product_id')
                ->where('bills.month', $month)
                ->where('bills.year', $year)
                ->where('bills.is_excluded', false)
                ->whereRaw('LOWER(products.name) = ?', ['infak'])
                ->where('bill_items.paid_amount', '>', 0)
                ->distinct()
                ->count('students.id');

            $infakRows = DB::table('bills')
                ->join('bill_items', 'bill_items.bill_id', '=', 'bills.id')
                ->join('products', 'products.id', '=', 'bill_items.product_id')
                ->where('bills.month', $month)
                ->where('bills.year', $year)
                ->where('bills.is_excluded', false)
                ->whereRaw('LOWER(products.name) = ?', ['infak'])
                ->select(
                    DB::raw('COALESCE(bill_items.subtotal, 0) as subtotal'),
                    DB::raw('COALESCE(bill_items.paid_amount, 0) as paid_amount')
                )
                ->get();

            $monthlyTargetAmount = (float) $infakRows->sum('subtotal');
            $mandatoryAmount = (float) $infakRows->sum('paid_amount');
            $totalSentAmount = $mandatoryAmount;

            $previousMonth = $month - 1;
            $previousYear = $year;

            if ($previousMonth < 1) {
                $previousMonth = 12;
                $previousYear = $year - 1;
            }

            $previousReport = MonthlyFinancialReport::query()
                ->where('branch_id', $branchId)
                ->where('month', $previousMonth)
                ->where('year', $previousYear)
                ->first();

            $openingBalance = (float) (
                $report?->opening_balance
                ?? $previousReport?->closing_balance
                ?? 0
            );

            $centralFundReceived = (float) ($report?->central_fund_received ?? 0);
            $totalSentAmount = (float) ($report?->total_sent_amount ?? $totalSentAmount);

            $sentDate = $report?->sent_date
                ? Carbon::parse($report->sent_date)->toDateString()
                : now()->toDateString();

            $summary = [
                'employee_total' => $employeeTotal,
                'employee_contributor_total' => $employeeContributorTotal,
                'monthly_target_amount' => $monthlyTargetAmount,
                'opening_balance' => $openingBalance,
                'central_fund_received' => $centralFundReceived,
                'mandatory_amount' => $mandatoryAmount,
                'total_sent_amount' => $totalSentAmount,
                'sent_date' => $sentDate,
                'notes' => $report?->notes,
            ];

            $items = collect($report?->items ?? []);

            $expenses = $items
                ->where('type', 'expense')
                ->values();

            $proposals = $items
                ->where('type', 'proposal')
                ->values();
        }

        return Inertia::render('MonthlyFinancialReports/Index', [
            'filters' => [
                'branch_id' => $branchId,
                'month' => $month,
                'year' => $year,
            ],
            'branches' => $branches,
            'report' => $report,
            'summary' => $summary,
            'expenses' => $expenses,
            'proposals' => $proposals,
        ]);
    }

    public function show(Request $request)
    {
        $branches = Branch::orderBy('name')->get();

        $branchId = $request->integer('branch_id') ?: $branches->first()?->id;
        $month = $request->integer('month') ?: now()->month;
        $year = $request->integer('year') ?: now()->year;

        if (! $branchId) {
            abort(404);
        }

        $branch = $branches->firstWhere('id', $branchId) ?? Branch::findOrFail($branchId);

        $report = MonthlyFinancialReport::with([
            'branch',
            'items' => fn ($q) => $q->orderByRaw('COALESCE(sort_order, 999999), id'),
        ])
            ->where('branch_id', $branchId)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if (! $report) {
            $report = $this->findOrCreateReport($branchId, $month, $year);
            $report->load([
                'branch',
                'items' => fn ($q) => $q->orderByRaw('COALESCE(sort_order, 999999), id'),
            ]);
        }

        $items = collect($report->items ?? []);
        $expenseItems = $items->where('type', 'expense')->values();
        $proposalItems = $items->where('type', 'proposal')->values();
        $expenseTotal = (float) $expenseItems->sum(fn ($item) => (float) $item->amount);
        $proposalTotal = (float) $proposalItems->sum(fn ($item) => (float) $item->amount);

        return view('reports.monthly', [
            'branch' => $branch,
            'month' => $month,
            'year' => $year,
            'report' => $report,
            'expenseItems' => $expenseItems,
            'proposalItems' => $proposalItems,
            'expenseTotal' => $expenseTotal,
            'proposalTotal' => $proposalTotal,
        ]);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $branchId = (int) $request->integer('branch_id');
        $month = (int) ($request->integer('month') ?: now()->month);
        $year = (int) ($request->integer('year') ?: now()->year);

        if ($branchId <= 0) {
            abort(422, 'branch_id wajib diisi');
        }

        $branch = Branch::findOrFail($branchId);

        $report = MonthlyFinancialReport::with([
            'branch',
            'items' => fn ($q) => $q->orderByRaw('COALESCE(sort_order, 999999), id'),
        ])
            ->where('branch_id', $branchId)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if (! $report) {
            $report = $this->findOrCreateReport($branchId, $month, $year);
            $report->load([
                'branch',
                'items' => fn ($q) => $q->orderByRaw('COALESCE(sort_order, 999999), id'),
            ]);
        }

        $items = collect($report->items ?? []);
        $expenseItems = $items->where('type', 'expense')->values();
        $proposalItems = $items->where('type', 'proposal')->values();
        $expenseTotal = (float) $expenseItems->sum(fn ($item) => (float) $item->amount);
        $proposalTotal = (float) $proposalItems->sum(fn ($item) => (float) $item->amount);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setTitle('Laporan Bulanan')
            ->setSubject('Laporan Bulanan')
            ->setDescription('Export Excel untuk edit manual');

        // Sheet 1: Header
        $sheetHeader = $spreadsheet->getActiveSheet();
        $sheetHeader->setTitle('Header');

        $periodText = sprintf('%02d/%d', $month, $year);
        $sheetHeader->fromArray([
            ['Cabang', $branch->name],
            ['Periode', $periodText],
            ['Jumlah Pegawai', (int) ($report->employee_total ?? 0)],
            ['Pegawai Ikut Iuran', (int) ($report->employee_contributor_total ?? 0)],
            ['Target Capaian Bulanan', (float) ($report->monthly_target_amount ?? 0)],
            ['Total Infak Masuk', (float) ($report->mandatory_amount ?? 0)],
            ['Total Kiriman', (float) ($report->total_sent_amount ?? 0)],
            ['Tanggal Kirim', $report->sent_date ? Carbon::parse($report->sent_date)->format('Y-m-d') : ''],
            ['Catatan', (string) ($report->notes ?? '')],
        ], null, 'A1', true);

        $sheetHeader->getStyle('A1:A9')->getFont()->setBold(true);
        $sheetHeader->getColumnDimension('A')->setAutoSize(true);
        $sheetHeader->getColumnDimension('B')->setAutoSize(true);
        $sheetHeader->getStyle('B5:B7')->getNumberFormat()->setFormatCode('#,##0');

        // Sheet 2: Pengeluaran
        $sheetExpense = $spreadsheet->createSheet();
        $sheetExpense->setTitle('Pengeluaran');
        $sheetExpense->fromArray(['ID', 'Tanggal', 'Kategori', 'Uraian', 'Jumlah'], null, 'A1', true);
        $sheetExpense->getStyle('A1:E1')->getFont()->setBold(true);

        $row = 2;
        foreach ($expenseItems as $it) {
            $sheetExpense->fromArray([
                (int) $it->id,
                $it->entry_date ? Carbon::parse($it->entry_date)->format('Y-m-d') : '',
                (string) ($it->category ?? ''),
                (string) ($it->description ?? ''),
                (float) ($it->amount ?? 0),
            ], null, 'A' . $row, true);
            $row++;
        }

        // Total row
        $sheetExpense->setCellValue('D' . $row, 'Total');
        $sheetExpense->setCellValue('E' . $row, $expenseTotal);
        $sheetExpense->getStyle('D' . $row . ':E' . $row)->getFont()->setBold(true);
        if ($row > 2) {
            $sheetExpense->getStyle('E2:E' . $row)->getNumberFormat()->setFormatCode('#,##0');
        }
        foreach (range('A', 'E') as $col) {
            $sheetExpense->getColumnDimension($col)->setAutoSize(true);
        }

        // Sheet 3: Pengajuan
        $sheetProposal = $spreadsheet->createSheet();
        $sheetProposal->setTitle('Pengajuan');
        $sheetProposal->fromArray(['ID', 'Periode', 'Kategori', 'Uraian', 'Jumlah'], null, 'A1', true);
        $sheetProposal->getStyle('A1:E1')->getFont()->setBold(true);

        $row = 2;
        foreach ($proposalItems as $it) {
            $sheetProposal->fromArray([
                (int) $it->id,
                ($it->target_month && $it->target_year)
                    ? sprintf('%02d/%d', (int) $it->target_month, (int) $it->target_year)
                    : '',
                (string) ($it->category ?? ''),
                (string) ($it->description ?? ''),
                (float) ($it->amount ?? 0),
            ], null, 'A' . $row, true);
            $row++;
        }

        $sheetProposal->setCellValue('D' . $row, 'Total');
        $sheetProposal->setCellValue('E' . $row, $proposalTotal);
        $sheetProposal->getStyle('D' . $row . ':E' . $row)->getFont()->setBold(true);
        if ($row > 2) {
            $sheetProposal->getStyle('E2:E' . $row)->getNumberFormat()->setFormatCode('#,##0');
        }
        foreach (range('A', 'E') as $col) {
            $sheetProposal->getColumnDimension($col)->setAutoSize(true);
        }

        // Sheet 4: Ringkasan
        $sheetSummary = $spreadsheet->createSheet();
        $sheetSummary->setTitle('Ringkasan');
        $sheetSummary->fromArray([
            ['Saldo Awal', (float) ($report->opening_balance ?? 0)],
            ['Realisasi Pusat Bulan Ini', (float) ($report->central_fund_received ?? 0)],
            ['Total Pengeluaran', $expenseTotal],
            ['Saldo Akhir', (float) ($report->closing_balance ?? 0)],
        ], null, 'A1', true);
        $sheetSummary->getStyle('A1:A4')->getFont()->setBold(true);
        $sheetSummary->getColumnDimension('A')->setAutoSize(true);
        $sheetSummary->getColumnDimension('B')->setAutoSize(true);
        $sheetSummary->getStyle('B1:B4')->getNumberFormat()->setFormatCode('#,##0');

        $spreadsheet->setActiveSheetIndex(0);

        $filename = sprintf(
            'laporan-bulanan-%s-%02d-%d.xlsx',
            preg_replace('/[^a-zA-Z0-9\-_]+/', '-', (string) $branch->name),
            $month,
            $year
        );

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function expensePage(Request $request): Response
    {
        $branches = Branch::orderBy('name')->get();

        $branchId = $request->integer('branch_id') ?: $branches->first()?->id;
        $month = $request->integer('month') ?: now()->month;
        $year = $request->integer('year') ?: now()->year;

        $report = null;
        $pengeluaranList = collect();
        $pengajuanList = collect();

        if ($branchId) {
            $report = MonthlyFinancialReport::with([
                'branch',
                'items' => fn ($q) => $q->orderByRaw('COALESCE(sort_order, 999999), id'),
            ])
                ->where('branch_id', $branchId)
                ->where('month', $month)
                ->where('year', $year)
                ->first();

            $items = collect($report?->items ?? []);

            $pengeluaranList = $items
                ->where('type', 'expense')
                ->values();

            $pengajuanList = $items
                ->where('type', 'proposal')
                ->values();
        }

        return Inertia::render('Pengeluaran/Index', [
            'filters' => [
                'branch_id' => $branchId,
                'month' => $month,
                'year' => $year,
            ],
            'branches' => $branches,
            'months' => collect(range(1, 12))->map(fn ($m) => [
                'value' => $m,
                'label' => str_pad((string) $m, 2, '0', STR_PAD_LEFT),
            ])->values(),
            'years' => collect(range(now()->year - 3, now()->year + 1))->map(fn ($y) => [
                'value' => $y,
                'label' => (string) $y,
            ])->values(),
            'report' => $report,
            'pengeluaranList' => $pengeluaranList->all(),
            'pengajuanList' => $pengajuanList->all(),
        ]);
    }

    public function storeExpense(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'min:2000'],
            'entry_date' => ['required', 'date'],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $report = $this->findOrCreateReport(
            (int) $validated['branch_id'],
            (int) $validated['month'],
            (int) $validated['year']
        );

        MonthlyFinancialReportItem::create([
            'monthly_financial_report_id' => $report->id,
            'type' => 'expense',
            'entry_date' => $validated['entry_date'],
            'target_month' => null,
            'target_year' => null,
            'category' => $validated['category'] ?? null,
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'sort_order' => $validated['sort_order'] ?? null,
        ]);

        $report->recalculateClosingBalance();

        return redirect()->route('pengeluaran.index', [
            'branch_id' => $report->branch_id,
            'month' => $report->month,
            'year' => $report->year,
        ])->with('success', 'Pengeluaran berhasil ditambahkan.');
    }

    public function storeProposal(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'min:2000'],
            'target_month' => ['required', 'integer', 'between:1,12'],
            'target_year' => ['required', 'integer', 'min:2000'],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $report = $this->findOrCreateReport(
            (int) $validated['branch_id'],
            (int) $validated['month'],
            (int) $validated['year']
        );

        MonthlyFinancialReportItem::create([
            'monthly_financial_report_id' => $report->id,
            'type' => 'proposal',
            'entry_date' => null,
            'target_month' => (int) $validated['target_month'],
            'target_year' => (int) $validated['target_year'],
            'category' => $validated['category'] ?? null,
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'sort_order' => $validated['sort_order'] ?? null,
        ]);

        return redirect()->route('pengeluaran.index', [
            'branch_id' => $report->branch_id,
            'month' => $report->month,
            'year' => $report->year,
        ])->with('success', 'Pengajuan berhasil ditambahkan.');
    }

    public function storeHeader(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000'],
            'employee_total' => ['nullable', 'integer', 'min:0'],
            'employee_contributor_total' => ['nullable', 'integer', 'min:0'],
            'monthly_target_amount' => ['nullable', 'numeric', 'min:0'],
            'opening_balance' => ['nullable', 'numeric'],
            'central_fund_received' => ['nullable', 'numeric'],
            'mandatory_amount' => ['nullable', 'numeric', 'min:0'],
            'total_sent_amount' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        $report = MonthlyFinancialReport::updateOrCreate(
            [
                'branch_id' => $validated['branch_id'],
                'month' => $validated['month'],
                'year' => $validated['year'],
            ],
            [
                'employee_total' => (int) ($validated['employee_total'] ?? 0),
                'employee_contributor_total' => (int) ($validated['employee_contributor_total'] ?? 0),
                'monthly_target_amount' => (float) ($validated['monthly_target_amount'] ?? 0),
                'opening_balance' => (float) ($validated['opening_balance'] ?? 0),
                'central_fund_received' => (float) ($validated['central_fund_received'] ?? 0),
                'mandatory_amount' => (float) ($validated['mandatory_amount'] ?? 0),
                'total_sent_amount' => (float) ($validated['total_sent_amount'] ?? 0),
                'sent_date' => now()->toDateString(),
                'notes' => $validated['notes'] ?? null,
                'status' => 'draft',
            ]
        );

        $report->recalculateClosingBalance();

        return redirect()->route('financial-reports.show', [
            'branch_id' => $report->branch_id,
            'month' => $report->month,
            'year' => $report->year,
        ]);
    }

    public function storeOrUpdateHeader(Request $request)
    {
        return $this->storeHeader($request);
    }

    private function findOrCreateReport(int $branchId, int $month, int $year): MonthlyFinancialReport
    {
        $report = MonthlyFinancialReport::firstOrNew([
            'branch_id' => $branchId,
            'month' => $month,
            'year' => $year,
        ]);

        if ($report->exists) {
            return $report;
        }

        $previousMonth = $month === 1 ? 12 : $month - 1;
        $previousYear = $month === 1 ? $year - 1 : $year;

        $previousReport = MonthlyFinancialReport::query()
            ->where('branch_id', $branchId)
            ->where('month', $previousMonth)
            ->where('year', $previousYear)
            ->first();

        $report->fill([
            'employee_total' => 0,
            'employee_contributor_total' => 0,
            'monthly_target_amount' => 0,
            'opening_balance' => (float) ($previousReport?->closing_balance ?? 0),
            'central_fund_received' => 0,
            'total_sent_amount' => 0,
            'mandatory_amount' => 0,
            'sunnah_amount' => 0,
            'sent_date' => null,
            'closing_balance' => (float) ($previousReport?->closing_balance ?? 0),
            'status' => 'draft',
            'is_locked' => false,
        ]);

        $report->save();

        return $report;
    }
}
