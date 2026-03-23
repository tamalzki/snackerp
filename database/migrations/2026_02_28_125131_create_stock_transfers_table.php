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
    Schema::create('stock_transfers', function (Blueprint $table) {
        $table->id();
        $table->foreignId('branch_id')->constrained('branches');
        $table->date('transfer_date');
        $table->text('notes')->nullable();
        $table->foreignId('created_by')->constrained('users');
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('stock_transfers');
}

};
