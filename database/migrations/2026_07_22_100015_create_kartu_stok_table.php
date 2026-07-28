<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kartu_stok', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_id')->constrained('barang');
            $table->foreignId('gudang_id')->constrained('gudang');
            $table->foreignId('lokasi_rak_id')->nullable()->constrained('lokasi_rak');
            $table->enum('tipe', ['in', 'out', 'mutasi_in', 'mutasi_out', 'opname', 'adjustment']);
            $table->decimal('qty', 14, 2);
            $table->decimal('saldo_sebelum', 14, 2)->default(0);
            $table->decimal('saldo_sesudah', 14, 2)->default(0);
            $table->string('referensi_type')->nullable();
            $table->unsignedBigInteger('referensi_id')->nullable();
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['barang_id', 'gudang_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kartu_stok');
    }
};