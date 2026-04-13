<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\Bill;
use App\Models\BillItem;

class PaymentController extends Controller
{
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {

            /*
            =========================
            VALIDATION
            =========================
            */

            $data = $request->validate([
                'bill_id' => ['required', 'exists:bills,id'],
                'payment_date' => ['required', 'date'],
                'payment_method_id' => ['required', 'exists:payment_methods,id'],
                'note' => ['nullable', 'string'],

                'items' => ['required', 'array', 'min:1'],
                'items.*.id' => ['required', 'exists:bill_items,id'],
                'items.*.amount' => ['required', 'numeric', 'min:1'],
            ]);

            /*
            =========================
            CREATE PAYMENT HEADER
            =========================
            */

            $payment = Payment::create([
                'bill_id' => $data['bill_id'],
                'payment_date' => $data['payment_date'],
                'payment_method_id' => $data['payment_method_id'],
                'note' => $data['note'] ?? null,
            ]);

            /*
            =========================
            CREATE PAYMENT ITEMS
            =========================
            */

            foreach ($data['items'] as $row) {

                $billItem = BillItem::findOrFail($row['id']);

                $amount = (float) $row['amount'];

                /*
                SAVE DETAIL PAYMENT
                */

                PaymentItem::create([
                    'payment_id' => $payment->id,
                    'bill_item_id' => $billItem->id,
                    'amount' => $amount,
                ]);

                /*
                UPDATE PAID AMOUNT
                */

                $billItem->increment(
                    'paid_amount',
                    $amount
                );
            }

            /*
            =========================
            UPDATE TOTAL BILL
            =========================
            */

            $bill = Bill::findOrFail($data['bill_id']);

            $totalPaid = $bill->items()
                ->sum('paid_amount');

            $bill->update([
                'total_paid' => $totalPaid,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Pembayaran berhasil disimpan',
                'payment_id' => $payment->id
            ]);

            $remaining =
                $billItem->subtotal
                - $billItem->paid_amount;

            if ($amount > $remaining) {

                throw new \Exception(
                    'Pembayaran melebihi sisa tagihan'
                );

            }

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Gagal simpan',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    //Delete payment
    public function destroy(Payment $payment)
{
    DB::beginTransaction();

    try {
        $affectedBillIds = [];

        $payment->load('items');

        foreach ($payment->items as $item) {
            $billItem = BillItem::find($item->bill_item_id);

            if ($billItem) {
                $billItem->paid_amount = max(
                    0,
                    (float) $billItem->paid_amount - (float) $item->amount
                );
                $billItem->save();

                $affectedBillIds[] = $billItem->bill_id;
            }
        }

        $payment->delete();

        foreach (array_unique($affectedBillIds) as $billId) {
            $bill = Bill::find($billId);

            if ($bill) {
                $bill->update([
                    'total_paid' => $bill->items()->sum('paid_amount'),
                ]);
            }
        }

        DB::commit();

        return response()->json([
            'message' => 'Pembayaran berhasil dihapus',
        ]);
    } catch (\Throwable $e) {
        DB::rollBack();

        return response()->json([
            'message' => 'Gagal hapus pembayaran',
            'error' => $e->getMessage(),
        ], 500);
    }
}
}
