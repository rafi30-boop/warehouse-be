<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('barang', function (Blueprint $table) {
            $table->unsignedBigInteger('stok_saat_ini')->default(0)->after('max_stok');
        });
        
        // Populate stok_saat_ini from kartu_stok (running balance)
        DB::statement("UPDATE barang 
                       SET stok_saat_ini = COALESCE((
                           SELECT SUM(saldo_sebelum + qty - saldo_sebelum) 
                           FROM kartu_stok 
                           WHERE barang_id = barang.id 
                           GROUP BY barang_id
                       ), 0)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barang', function (Blueprint $table) {
            $table->dropColumn('stok_saat_ini');
        });
    }
};
