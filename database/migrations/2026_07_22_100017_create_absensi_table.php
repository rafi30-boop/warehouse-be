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

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE absensi ADD lokasi_checkin POINT NULL');
            DB::statement('ALTER TABLE absensi ADD lokasi_checkout POINT NULL');
        } else {
            Schema::table('absensi', function (Blueprint $table) {
                $table->string('lokasi_checkin')->nullable();
                $table->string('lokasi_checkout')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi');
    }
};