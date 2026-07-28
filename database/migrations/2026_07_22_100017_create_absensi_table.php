<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('gudang_id')->constrained('gudang');
            $table->foreignId('shift_id')->constrained('shift');
            $table->date('tanggal');
            $table->time('jam_masuk')->nullable();
            $table->time('jam_pulang')->nullable();
            $table->enum('status', ['hadir', 'izin', 'sakit', 'alpha', 'cuti', 'terlambat'])->default('hadir');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->string('foto_masuk')->nullable();
            $table->string('foto_pulang')->nullable();
            $table->integer('radius_validasi')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'tanggal']);
        });

        DB::statement('ALTER TABLE absensi ADD lokasi_checkin POINT NOT NULL');
        DB::statement('ALTER TABLE absensi ADD lokasi_checkout POINT NOT NULL');
        DB::statement('CREATE SPATIAL INDEX absensi_lokasi_checkin_index ON absensi (lokasi_checkin)');
        DB::statement('CREATE SPATIAL INDEX absensi_lokasi_checkout_index ON absensi (lokasi_checkout)');
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi');
    }
};