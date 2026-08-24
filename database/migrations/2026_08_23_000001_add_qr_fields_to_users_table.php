<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('qr_version')->default(1)->after('no_pegawai');
            $table->timestamp('qr_revoked_at')->nullable()->after('qr_version');

            $table->index(['no_pegawai']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['no_pegawai']);
            $table->dropColumn(['qr_version', 'qr_revoked_at']);
        });
    }
};
