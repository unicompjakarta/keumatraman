<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\Bill;
use App\Models\BillItem;
use Illuminate\Support\Facades\DB;
use App\Models\Product;

class GenerateMonthlyBills extends Command
{
    protected $signature = 'billing:generate';
    protected $description = 'Generate bill bulanan untuk semua student';

    // public function handle()
    // {
    //     $month = now()->month;
    //     $year = now()->year;

    //     $this->info("Generate bill $month/$year");

    //     $students = Student::with('subscriptions')->get();

    //     DB::beginTransaction();

    //     try {
    //         $products = Product::whereIn('name', ['infak', 'media', 'tabloid'])
    //             ->get()
    //             ->keyBy('name');


    //         if (!$products->has('infak')) {
    //             throw new \Exception('Product infak tidak ditemukan');
    //         }

    //         if (!$products->has('media')) {
    //             throw new \Exception('Product media tidak ditemukan');
    //         }

    //         if (!$products->has('tabloid')) {
    //             throw new \Exception('Product tabloid tidak ditemukan');
    //         }
    //         foreach ($students as $student) {

    //             // ❌ Jangan duplicate bill
    //             $exists = Bill::where('student_id', $student->id)
    //                 ->where('month', $month)
    //                 ->where('year', $year)
    //                 ->exists();

    //             if ($exists) {
    //                 continue;
    //             }

    //             // ✅ Buat bill
    //             $bill = Bill::create([
    //                 'student_id' => $student->id,
    //                 'month' => $month,
    //                 'year' => $year,
    //             ]);

    //             // ✅ INFak (selalu ada)
    //             BillItem::create([
    //                 'bill_id' => $bill->id,
    //                 'product_id' => $products['infak']->id,
    //                 'qty' => 1,
    //                 'price' => $student->infak,
    //                 'subtotal' => $student->infak,
    //             ]);

    //             // ✅ Subscription (media & tabloid)
    //             foreach ($student->subscriptions as $sub) {

    //                 $product = $products[$sub->type] ?? null;

    //                 if (!$product) continue;

    //                 BillItem::create([
    //                     'bill_id' => $bill->id,
    //                     'product_id' => $product->id,
    //                     'qty' => $sub->qty,
    //                     'price' => $sub->price,
    //                     'subtotal' => $sub->price,
    //                 ]);
    //             }
    //         }

    //         DB::commit();

    //         $this->info('Berhasil generate bill');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         $this->error($e->getMessage());
    //     }
    // }
}
