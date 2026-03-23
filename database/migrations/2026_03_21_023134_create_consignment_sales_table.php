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
    Schema::create('consignment_sales', function (Blueprint $table) {
        $table->id();
        $table->foreignId('consignment_receivable_id')->constrained()->cascadeOnDelete();
        $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
        $table->date('sale_date');
        $table->decimal('total_amount', 12, 2)->default(0);
        $table->decimal('total_cost', 12, 2)->default(0);
        $table->string('notes', 500)->nullable();
        $table->foreignId('created_by')->nullable()->constrained('users');
        $table->timestamps();
    });
}
public function down(): void { Schema::dropIfExists('consignment_sales'); }
};
