<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_cash_bank_names', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('daily_cash_bank_day_cells', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_cash_day_id')->constrained('daily_cash_days')->cascadeOnDelete();
            $table->foreignId('daily_cash_bank_name_id')->constrained('daily_cash_bank_names')->cascadeOnDelete();
            $table->string('movement', 32);
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('detail', 255)->nullable();
            $table->timestamps();

            $table->unique(['daily_cash_day_id', 'daily_cash_bank_name_id', 'movement'], 'dcd_bank_cells_unique_triple');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_cash_bank_day_cells');
        Schema::dropIfExists('daily_cash_bank_names');
    }
};
