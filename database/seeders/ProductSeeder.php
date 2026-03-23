<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Product;


class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::insert([
            [
                'name' => 'infak',
                'type' => 'bill'
            ],
            [
                'name' => 'media',
                'type' => 'bill'
            ],
            [
                'name' => 'tabloid',
                'type' => 'bill'
            ],
        ]);

    }
}
