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
    Schema::create('sales', function (Blueprint $table) {
        $table->id();
        $table->foreignId('branch_id')->constrained('branches');
        $table->date('sale_date');
        $table->decimal('total_amount', 15, 2)->default(0);
        $table->decimal('total_cost', 15, 2)->default(0);
        $table->text('notes')->nullable();
        $table->foreignId('created_by')->constrained('users');
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('sales');
}
};
