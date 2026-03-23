<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\BillItem;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function store(Request $request)
    {
        try {

            $payment = Payment::create([
                'bill_id' => $request->bill_id,
                'payment_date' => $request->payment_date,
                'payment_method_id' => null,
            ]);

            foreach ($request->items as $item) {

                $billItem = BillItem::findOrFail($item['id']);

                $billItem->increment(
                    'paid_amount',
                    $item['amount']
                );

            }

            return response()->json([
                'message' => 'Pembayaran berhasil'
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Gagal simpan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
