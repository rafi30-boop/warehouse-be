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
        Schema::table('izin_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('petugas_id')->nullable()->after('user_id');
            $table->foreign('petugas_id')->references('id')->on('petugas')->nullOnDelete();
            
            // Make user_id nullable (dual-subject: one of user_id OR petugas_id required)
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('izin_requests', function (Blueprint $table) {
            $table->dropForeign(['petugas_id']);
            $table->dropColumn('petugas_id');
            
            // Restore user_id NOT NULL (requires data cleanup first in prod)
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
