<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('expenses', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->enum('category', [
            'utilities',
            'salaries',
            'rent',
            'supplies',
            'maintenance',
            'transport',
            'other'
        ]);
        $table->decimal('amount', 15, 2);
        $table->date('expense_date');
        $table->enum('paid_from', ['cash', 'bank']);
        $table->foreignId('source_id')->comment('cash_account_id or bank_account_id');
        $table->text('notes')->nullable();
        $table->foreignId('created_by')->constrained('users');
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('expenses');
}
};
