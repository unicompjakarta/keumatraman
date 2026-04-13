<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentMethod; // Pastikan model ini sudah ada
use Illuminate\Support\Facades\DB;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            ['name' => 'Transfer Jago'],
            ['name' => 'Cash'],
            ['name' => 'Titip'],
        ];

        foreach ($methods as $method) {
            // Menggunakan updateOrCreate agar tidak duplikat jika dijalankan ulang
            DB::table('payment_methods')->updateOrInsert(
                ['name' => $method['name']],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
