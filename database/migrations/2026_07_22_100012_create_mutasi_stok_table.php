<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mutasi_stok', function (Blueprint $table) {
            $table->id();
            $table->string('no_referensi', 50)->unique();
            $table->foreignId('barang_id')->constrained('barang');
            $table->foreignId('gudang_asal_id')->constrained('gudang');
            $table->foreignId('gudang_tujuan_id')->constrained('gudang');
            $table->foreignId('lokasi_rak_asal_id')->nullable()->constrained('lokasi_rak');
            $table->foreignId('lokasi_rak_tujuan_id')->nullable()->constrained('lokasi_rak');
            $table->decimal('qty', 14, 2);
            $table->date('tanggal');
            $table->text('keterangan')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutasi_stok');
    }
};