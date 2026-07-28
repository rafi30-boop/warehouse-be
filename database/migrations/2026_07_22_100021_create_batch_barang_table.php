<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_barang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_id')->constrained('barang')->cascadeOnDelete();
            $table->string('batch_number', 50);
            $table->date('expired_date')->nullable();
            $table->decimal('qty', 14, 2)->default(0);
            $table->timestamps();

            $table->unique(['barang_id', 'batch_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_barang');
    }
};