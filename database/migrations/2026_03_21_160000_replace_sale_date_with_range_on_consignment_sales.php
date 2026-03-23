<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consignment_sales', function (Blueprint $table) {
            $table->date('sale_date_from')->nullable()->after('branch_id');
            $table->date('sale_date_to')->nullable()->after('sale_date_from');
        });

        foreach (DB::table('consignment_sales')->select('id', 'sale_date')->cursor() as $row) {
            DB::table('consignment_sales')->where('id', $row->id)->update([
                'sale_date_from' => $row->sale_date,
                'sale_date_to' => $row->sale_date,
            ]);
        }

        Schema::table('consignment_sales', function (Blueprint $table) {
            $table->dropColumn('sale_date');
        });
    }

    public function down(): void
    {
        Schema::table('consignment_sales', function (Blueprint $table) {
            $table->date('sale_date')->nullable()->after('branch_id');
        });

        foreach (DB::table('consignment_sales')->select('id', 'sale_date_to')->cursor() as $row) {
            DB::table('consignment_sales')->where('id', $row->id)->update([
                'sale_date' => $row->sale_date_to,
            ]);
        }

        Schema::table('consignment_sales', function (Blueprint $table) {
            $table->dropColumn(['sale_date_from', 'sale_date_to']);
        });
    }
};
