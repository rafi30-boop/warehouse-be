<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stok_opname_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stok_opname_id')->constrained('stok_opname')->cascadeOnDelete();
            $table->foreignId('barang_id')->constrained('barang');
            $table->foreignId('lokasi_rak_id')->nullable()->constrained('lokasi_rak');
            $table->decimal('stok_sistem', 14, 2)->default(0);
            $table->decimal('stok_fisik', 14, 2)->default(0);
            $table->decimal('selisih', 14, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stok_opname_detail');
    }
};