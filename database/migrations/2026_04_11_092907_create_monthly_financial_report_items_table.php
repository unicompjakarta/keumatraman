<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_financial_report_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('monthly_financial_report_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('type'); // expense | proposal
            $table->date('entry_date')->nullable();

            $table->unsignedTinyInteger('target_month')->nullable();
            $table->unsignedSmallInteger('target_year')->nullable();

            $table->string('category')->nullable();
            $table->text('description');
            $table->decimal('amount', 14, 2)->default(0);

            $table->unsignedInteger('sort_order')->nullable();

            $table->timestamps();

            $table->index(['type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_financial_report_items');
    }
};
