<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Product;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $r)
    {
        /*
        =========================
        FILTER BULAN & TAHUN
        =========================
        */

        $month = $r->month ?? now()->month;
        $year  = $r->year  ?? now()->year;

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

        $bills = Bill::with([
            'student.pic',
            'items.product',
            'payments.items'
        ])
        ->where('month', $month)
        ->where('year', $year)
        ->orderBy('student_id')
        ->get();

        return Inertia::render('Dashboard', [

            'bills' => $bills,

            'students' => Student::select(
                'id',
                'name'
            )->get(),

            'filter' => [
                'month' => (int) $month,
                'year'  => (int) $year
            ]

        ]);
    }
}
