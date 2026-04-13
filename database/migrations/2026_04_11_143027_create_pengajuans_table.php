<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuans', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('keperluan');
            $table->string('keterangan')->nullable();
            $table->decimal('jumlah', 14, 2)->default(0);
            $table->string('status')->default('Menunggu');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuans');
    }
};
