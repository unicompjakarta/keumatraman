<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Needs to support large totals (e.g. tens/hundreds of billions) safely.
        DB::statement('ALTER TABLE `bills` MODIFY `total_amount` DECIMAL(18,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `bills` MODIFY `total_paid` DECIMAL(18,2) NOT NULL DEFAULT 0');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `bills` MODIFY `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `bills` MODIFY `total_paid` DECIMAL(12,2) NOT NULL DEFAULT 0');
    }
};

