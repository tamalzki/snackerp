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
    Schema::create('consignment_receivables', function (Blueprint $table) {
        $table->id();
        $table->foreignId('stock_transfer_id')->constrained()->cascadeOnDelete();
        $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
        $table->string('dr_number', 50)->nullable();
        $table->decimal('total_amount', 12, 2)->default(0);
        $table->decimal('amount_paid', 12, 2)->default(0);
        $table->decimal('amount_returned', 12, 2)->default(0);
        $table->decimal('balance', 12, 2)->default(0);
        $table->enum('status', ['open', 'partial', 'paid'])->default('open');
        $table->date('delivery_date');
        $table->foreignId('created_by')->nullable()->constrained('users');
        $table->timestamps();
    });
}
public function down(): void { Schema::dropIfExists('consignment_receivables'); }
};
