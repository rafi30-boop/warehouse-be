<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add catatan_penolakan to izin_requests
        Schema::table('izin_requests', function (Blueprint $table) {
            $table->text('catatan_penolakan')->nullable()->after('status');
        });

        // Alter status enum to add 'dibatalkan'
        DB::statement("ALTER TABLE izin_requests MODIFY status ENUM('menunggu', 'disetujui', 'ditolak', 'dibatalkan') DEFAULT 'menunggu'");

        // Alter absensi.sumber enum to add 'pengajuan'
        DB::statement("ALTER TABLE absensi MODIFY sumber ENUM('qr', 'manual', 'pengajuan') DEFAULT 'manual'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('izin_requests', function (Blueprint $table) {
            $table->dropColumn('catatan_penolakan');
        });

        DB::statement("ALTER TABLE izin_requests MODIFY status ENUM('menunggu', 'disetujui', 'ditolak') DEFAULT 'menunggu'");
        DB::statement("ALTER TABLE absensi MODIFY sumber ENUM('qr', 'manual') DEFAULT 'manual'");
    }
};
