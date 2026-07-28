<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barang', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 50)->unique();
            $table->string('barcode', 50)->nullable()->unique();
            $table->string('nama', 200);
            $table->foreignId('kategori_id')->constrained('kategori_barang');
            $table->foreignId('satuan_id')->constrained('satuan');
            $table->decimal('min_stok', 14, 2)->default(0);
            $table->decimal('max_stok', 14, 2)->default(0);
            $table->decimal('berat', 10, 2)->nullable();
            $table->string('foto')->nullable();
            $table->decimal('harga_beli', 14, 2)->default(0);
            $table->decimal('harga_jual', 14, 2)->default(0);
            $table->text('deskripsi')->nullable();
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barang');
    }
};