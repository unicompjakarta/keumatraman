<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengeluarans', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('kategori');
            $table->string('keterangan');
            $table->decimal('jumlah', 14, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengeluarans');
    }
};
