<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lokasi_rak', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gudang_id')->constrained('gudang')->cascadeOnDelete();
            $table->string('kode_rak', 50);
            $table->string('zona', 50)->nullable();
            $table->integer('kapasitas')->default(0);
            $table->text('deskripsi')->nullable();
            $table->enum('status', ['aktif', 'nonaktif', 'penuh'])->default('aktif');
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['gudang_id', 'kode_rak']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lokasi_rak');
    }
};