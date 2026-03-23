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
    Schema::create('consignment_payments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('consignment_receivable_id')->constrained()->cascadeOnDelete();
        $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
        $table->decimal('amount', 12, 2);
        $table->date('payment_date');
        $table->string('reference', 100)->nullable();
        $table->string('notes', 500)->nullable();
        $table->foreignId('created_by')->nullable()->constrained('users');
        $table->timestamps();
    });
}
public function down(): void { Schema::dropIfExists('consignment_payments'); }
};
