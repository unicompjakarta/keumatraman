<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_financial_reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();

            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');

            $table->unsignedInteger('employee_total')->default(0);
            $table->unsignedInteger('employee_contributor_total')->default(0);

            $table->decimal('monthly_target_amount', 14, 2)->default(0);

            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->decimal('central_fund_received', 14, 2)->default(0);

            $table->decimal('total_sent_amount', 14, 2)->default(0);
            $table->decimal('mandatory_amount', 14, 2)->default(0);
            $table->decimal('sunnah_amount', 14, 2)->default(0);

            $table->date('sent_date')->nullable();

            $table->decimal('closing_balance', 14, 2)->default(0);

            $table->string('status')->default('draft');
            $table->boolean('is_locked')->default(false);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['branch_id', 'month', 'year']);
            $table->index(['month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_financial_reports');
    }
};
