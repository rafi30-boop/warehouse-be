<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barang_masuk', function (Blueprint $table) {
            $table->index('gudang_id');
            $table->index('supplier_id');
            $table->index('tanggal');
            $table->index('status');
        });

        Schema::table('barang_keluar', function (Blueprint $table) {
            $table->index('gudang_id');
            $table->index('customer_id');
            $table->index('tanggal');
            $table->index('status');
        });

        Schema::table('mutasi_stok', function (Blueprint $table) {
            $table->index('barang_id');
            $table->index('gudang_asal_id');
            $table->index('gudang_tujuan_id');
            $table->index('status');
            $table->index('tanggal');
        });

        Schema::table('stok_opname', function (Blueprint $table) {
            $table->index('gudang_id');
            $table->index('status');
            $table->index('tanggal');
        });

        Schema::table('barang_masuk_detail', function (Blueprint $table) {
            $table->index('barang_id');
        });

        Schema::table('barang_keluar_detail', function (Blueprint $table) {
            $table->index('barang_id');
        });

        Schema::table('stok_opname_detail', function (Blueprint $table) {
            $table->index('barang_id');
            $table->index('stok_opname_id');
        });
    }

    public function down(): void
    {
        Schema::table('barang_masuk', function (Blueprint $table) {
            $table->dropIndex(['gudang_id']);
            $table->dropIndex(['supplier_id']);
            $table->dropIndex(['tanggal']);
            $table->dropIndex(['status']);
        });

        Schema::table('barang_keluar', function (Blueprint $table) {
            $table->dropIndex(['gudang_id']);
            $table->dropIndex(['customer_id']);
            $table->dropIndex(['tanggal']);
            $table->dropIndex(['status']);
        });

        Schema::table('mutasi_stok', function (Blueprint $table) {
            $table->dropIndex(['barang_id']);
            $table->dropIndex(['gudang_asal_id']);
            $table->dropIndex(['gudang_tujuan_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['tanggal']);
        });

        Schema::table('stok_opname', function (Blueprint $table) {
            $table->dropIndex(['gudang_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['tanggal']);
        });

        Schema::table('barang_masuk_detail', function (Blueprint $table) {
            $table->dropIndex(['barang_id']);
        });

        Schema::table('barang_keluar_detail', function (Blueprint $table) {
            $table->dropIndex(['barang_id']);
        });

        Schema::table('stok_opname_detail', function (Blueprint $table) {
            $table->dropIndex(['barang_id']);
            $table->dropIndex(['stok_opname_id']);
        });
    }
};