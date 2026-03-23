<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {

            $table->boolean('media_active')->default(false);
            $table->integer('media_qty')->default(0);

            $table->boolean('tabloid_active')->default(false);
            $table->integer('tabloid_qty')->default(0);

        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {

            $table->dropColumn([
                'media_active',
                'media_qty',
                'tabloid_active',
                'tabloid_qty'
            ]);

        });
    }
};
