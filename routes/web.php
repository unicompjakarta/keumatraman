<?php
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\MonthlyFinancialReportController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\PengeluaranController;



Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/dashboard/export-xls', [DashboardController::class, 'exportXls'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.export-xls');

Route::delete('/bills/{bill}', [DashboardController::class, 'excludeBill'])
    ->middleware(['auth', 'verified'])
    ->name('bills.exclude');

//Route::post('/students', [StudentController::class, 'store'])->name('students.store');
Route::post('/students', [StudentController::class, 'store']);


Route::match(['put', 'post'], '/students/{student}', [StudentController::class, 'update'])
    ->name('students.update');

Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
Route::delete('/payments/{payment}', [PaymentController::class, 'destroy'])
    ->name('payments.destroy');

Route::get('/students/{student}/bills', [DashboardController::class, 'studentBills']);



Route::prefix('laporan')->name('financial-reports.')->group(function () {
    Route::get('/', [MonthlyFinancialReportController::class, 'index'])->name('index');
    Route::get('/bulanan', [MonthlyFinancialReportController::class, 'show'])->name('show');
    Route::post('/header/store', [MonthlyFinancialReportController::class, 'storeHeader'])->name('header.store');
});

//Pengeluaran
Route::get('/pengeluaran', [MonthlyFinancialReportController::class, 'expensePage'])
    ->name('pengeluaran.index');

Route::post('/pengeluaran/expense', [MonthlyFinancialReportController::class, 'storeExpense'])
    ->name('pengeluaran.expense');

Route::post('/pengeluaran/proposal', [MonthlyFinancialReportController::class, 'storeProposal'])
    ->name('pengeluaran.proposal');

Route::post('/pengajuan/store', [MonthlyFinancialReportController::class, 'storeProposal'])
    ->name('pengajuan.store');

Route::post('/pengajuan/store', [MonthlyFinancialReportController::class, 'storeProposal'])
    ->name('pengajuan.store');
Route::post('/pengajuan/store', [PengeluaranController::class, 'storePengajuan']);





Route::get('/laporan/bulanan', [MonthlyFinancialReportController::class, 'show'])
    ->name('financial-reports.show');

Route::get('/laporan/bulanan/excel', [MonthlyFinancialReportController::class, 'exportExcel'])
    ->name('financial-reports.export-excel');

Route::post('/laporan/header', [MonthlyFinancialReportController::class, 'storeOrUpdateHeader'])
    ->name('financial-reports.header.store');

Route::post('/pengeluaran/expense', [MonthlyFinancialReportController::class, 'storeExpense'])
    ->name('financial-reports.expense.store');

Route::post('/pengeluaran/proposal', [MonthlyFinancialReportController::class, 'storeProposal'])
    ->name('financial-reports.proposal.store');

Route::delete('/laporan/items/{item}', [MonthlyFinancialReportController::class, 'destroyItem'])
    ->name('financial-reports.items.destroy');




Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
