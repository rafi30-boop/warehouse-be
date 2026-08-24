<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('petugas', function (Blueprint $table) {
            $table->unsignedInteger('qr_version')->default(1)->after('status_operasional');
            $table->timestamp('qr_revoked_at')->nullable()->after('qr_version');
        });

        Schema::table('absensi', function (Blueprint $table) {
            $table->foreignId('petugas_id')
                ->nullable()
                ->after('user_id')
                ->constrained('petugas')
                ->nullOnDelete();

            $table->index(['petugas_id', 'tanggal']);
        });

        Schema::table('absensi', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('absensi', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        DB::table('absensi')->whereNull('user_id')->delete();

        Schema::table('absensi', function (Blueprint $table) {
            $table->foreignId('user_id')->change();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->dropForeign(['petugas_id']);
            $table->dropIndex(['petugas_id', 'tanggal']);
            $table->dropColumn('petugas_id');
        });

        Schema::table('petugas', function (Blueprint $table) {
            $table->dropColumn(['qr_version', 'qr_revoked_at']);
        });
    }
};
