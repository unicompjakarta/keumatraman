<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Product;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Models\PaymentMethod;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    private function isMobileRequest(Request $request): bool
    {
        $ua = (string) $request->header('User-Agent', '');

        // Common UA tokens for mobile devices
        return (bool) preg_match(
            '/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|Mobile|Windows Phone/i',
            $ua
        );
    }

    public function index(Request $r)
    {
        /*
        =========================
        FILTER BULAN & TAHUN
        =========================
        */

        $month = (int) ($r->month ?? now()->month);
        $year  = (int) ($r->year  ?? now()->year);
        $q = trim((string) ($r->q ?? ''));
        $status = trim((string) ($r->status ?? ''));

        /*
        =========================
        AMBIL SEMUA SISWA
        =========================
        */

        $students = Student::with(['pic', 'subscriptions'])->get();

        /*
        =========================
        AMBIL PRODUCT
        =========================
        */

        $products = Product::all()
            ->keyBy('name');

        DB::beginTransaction();

        try {

            foreach ($students as $student) {

                /*
                =========================
                1. CREATE BILL
                =========================
                */

                $bill = Bill::firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'month' => $month,
                        'year'  => $year,
                    ],
                    [
                        'total_amount' => 0,
                        'total_paid'   => 0,
                    ]
                );

                if ((bool) ($bill->is_excluded ?? false)) {
                    continue;
                }

                /*
                =========================
                2. CREATE INFAK
                =========================
                */

                if ($student->infak > 0) {

                    $product = $products['infak'];

                    BillItem::updateOrCreate(
                        [
                            'bill_id' => $bill->id,
                            'product_id' => $product->id,
                        ],
                        [
                            'qty' => 1,
                            'price' => $student->infak,
                            'subtotal' => $student->infak,
                        ]
                    );

                }

                /*
                =========================
                3. CREATE MEDIA
                =========================
                */

                if ($student->media_active) {

                    $product = $products['media'] ?? null;
                    $sub = $student->subscriptions->firstWhere('type', 'media');

                    if ($product && $sub) {
                        $qty = $student->media_qty ?? 1;
                        $price = $sub->price ?? 0;

                        BillItem::updateOrCreate(
                            [
                                'bill_id' => $bill->id,
                                'product_id' => $product->id,
                            ],
                            [
                                'qty' => $qty,
                                'price' => $price,
                                'subtotal' => $qty * $price,
                            ]
                        );
                    }
                }

                /*
                =========================
                4. CREATE TABLOID
                =========================
                */

                if ($student->tabloid_active) {

                    $product = $products['tabloid'] ?? null;
                    $sub = $student->subscriptions->firstWhere('type', 'tabloid');

                    if ($product && $sub) {
                        $qty = $student->tabloid_qty ?? 1;
                        $price = $sub->price ?? 0;

                        BillItem::updateOrCreate(
                            [
                                'bill_id' => $bill->id,
                                'product_id' => $product->id,
                            ],
                            [
                                'qty' => $qty,
                                'price' => $price,
                                'subtotal' => $qty * $price,
                            ]
                        );
                    }
                }

                /*
                =========================
                5. UPDATE TOTAL
                =========================
                */

                $totalAmount = $bill->items()
                    ->sum('subtotal');

                $totalPaid = $bill->items()
                    ->sum('paid_amount');

                $bill->update([
                    'total_amount' => $totalAmount,
                    'total_paid'   => $totalPaid,
                ]);

            }

            DB::commit();

        } catch (\Exception $e) {

            DB::rollBack();

            throw $e;

        }

        /*
        =========================
        LOAD DATA
        =========================
        */

        // $bills = Bill::with([ ini OKE
        //     'student.pic',
        //     'items.product',
        //     'payments.items'
        // ])
        // ->where('month', $month)
        // ->where('year', $year)
        // ->orderBy('student_id')
        // ->get();

        $billsQuery = Bill::with([
            'student.pic',
            'items.product',
            'payments.paymentMethod',
            'payments.items.billItem.product',
        ])
        ->where('month', $month)
        ->where('year', $year)
        ->where('is_excluded', false)
        ->orderBy('student_id');

        if ($q !== '') {
            $billsQuery->whereHas('student', function ($qq) use ($q) {
                $qq->where('name', 'like', '%' . $q . '%');
            });
        }

        if (in_array($status, ['belum_bayar', 'partial', 'lunas'], true)) {
            if ($status === 'belum_bayar') {
                $billsQuery->where('total_paid', '<=', 0);
            } elseif ($status === 'lunas') {
                $billsQuery->whereColumn('total_paid', '>=', 'total_amount');
            } else {
                $billsQuery->where('total_paid', '>', 0)
                    ->whereColumn('total_paid', '<', 'total_amount');
            }
        }

        $bills = $billsQuery->get();

        $summaryBills = Bill::with([
            'student.pic',
            'items.product',
        ])
            ->where('month', $month)
            ->where('year', $year)
            ->where('is_excluded', false)
            ->orderBy('student_id')
            ->get();

        $component = $this->isMobileRequest($r) ? 'Dashboardmobile' : 'Dashboard';

        return Inertia::render($component, [

            'bills' => $bills,
            'summaryBills' => $summaryBills,

            'students' => Student::select(
                'id',
                'name'
            )->get(),

            'picOptions' => Student::select('id', 'name', 'phone')
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->orderBy('name')
            ->get()
            ->unique(function ($s) {
                return mb_strtolower(trim($s->name));
            })
            ->values(),

            'paymentMethods' => PaymentMethod::select(
            'id',
            'name'
            )->get(),

            'filter' => [
                'month' => (int) $month,
                'year'  => (int) $year,
                'q' => $q,
                'status' => $status,
            ]

        ]);
    }

    //Endpoint Multi Payment
    public function studentBills(Student $student)
    {
        $bills = Bill::with([
            'items.product',
            'payments.paymentMethod',
            'payments.items.billItem.product',
        ])
            ->where('student_id', $student->id)
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        return response()->json([
            'student' => $student,
            'bills' => $bills,
        ]);
    }

    public function excludeBill(Bill $bill)
    {
        $bill->update(['is_excluded' => true]);

        return response()->json([
            'message' => 'Siswa berhasil dihapus dari daftar tagihan bulan ini.',
        ]);
    }

    public function exportXls(Request $request): StreamedResponse
    {
        $month = (int) ($request->month ?? now()->month);
        $year = (int) ($request->year ?? now()->year);
        $q = trim((string) ($request->q ?? ''));
        $status = trim((string) ($request->status ?? ''));

        $query = Bill::with(['student.pic', 'items.product'])
            ->where('month', $month)
            ->where('year', $year)
            ->where('is_excluded', false)
            ->orderBy('student_id');

        if ($q !== '') {
            $query->whereHas('student', function ($qq) use ($q) {
                $qq->where('name', 'like', '%' . $q . '%');
            });
        }

        if (in_array($status, ['belum_bayar', 'partial', 'lunas'], true)) {
            if ($status === 'belum_bayar') {
                $query->where('total_paid', '<=', 0);
            } elseif ($status === 'lunas') {
                $query->whereColumn('total_paid', '>=', 'total_amount');
            } else {
                $query->where('total_paid', '>', 0)
                    ->whereColumn('total_paid', '<', 'total_amount');
            }
        }

        $bills = $query->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Export');

        $headers = [
            'NO',
            'Bulan',
            'Nama',
            'Infak',
            'Status Bayar Infak',
            'Media',
            'Status Bayar Media',
            'Tabloid',
            'Status Bayar Tabloid',
            'PIC',
        ];

        $sheet->fromArray($headers, null, 'A1', true);
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
        $sheet->getStyle('A1:J1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE5E7EB');

        $row = 2;
        $no = 1;

        foreach ($bills as $bill) {
            $items = collect($bill->items ?? []);

            $getItemByName = function (string $name) use ($items) {
                return $items->first(function ($it) use ($name) {
                    return strtolower((string) ($it->product->name ?? '')) === strtolower($name);
                });
            };

            $infak = $getItemByName('infak');
            $media = $getItemByName('media');
            $tabloid = $getItemByName('tabloid');

            $statusItem = function ($item) {
                if (! $item) return 'Belum Ada';
                $subtotal = (float) ($item->subtotal ?? 0);
                $paid = (float) ($item->paid_amount ?? 0);
                if ($subtotal <= 0) return 'Belum Ada';
                if ($paid <= 0) return 'Belum Bayar';
                if ($paid >= $subtotal) return 'Lunas';
                return 'Partial';
            };

            $sheet->fromArray([
                $no++,
                sprintf('%02d/%d', $month, $year),
                $bill->student->name ?? '-',
                (float) ($infak->subtotal ?? 0),
                $statusItem($infak),
                (float) ($media->subtotal ?? 0),
                $statusItem($media),
                (float) ($tabloid->subtotal ?? 0),
                $statusItem($tabloid),
                $bill->student->pic->name ?? '-',
            ], null, 'A' . $row, true);

            $row++;
        }

        if ($row > 2) {
            $sheet->getStyle('D2:D' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('F2:F' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('H2:H' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0');
        }

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = sprintf('export-dashboard-%02d-%d.xlsx', $month, $year);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
