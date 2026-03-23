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
    Schema::create('deposits', function (Blueprint $table) {
        $table->id();
        $table->enum('source_type', ['cash', 'bank']);
        $table->foreignId('source_id')->comment('cash_account_id or bank_account_id');
        $table->decimal('amount', 15, 2);
        $table->date('deposit_date');
        $table->string('reference')->nullable();
        $table->text('notes')->nullable();
        $table->foreignId('created_by')->constrained('users');
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('deposits');
}
};
