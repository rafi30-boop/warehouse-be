<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('petugas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('kode')->unique();
            $table->string('telepon', 30)->nullable();
            $table->string('jabatan', 100)->nullable();
            $table->string('area_kerja', 100)->nullable();
            $table->date('tanggal_bergabung')->nullable();
            $table->enum('status_operasional', ['Aktif', 'Cuti', 'Non-Aktif'])->default('Aktif');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status_operasional']);
            $table->index(['jabatan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petugas');
    }
};
