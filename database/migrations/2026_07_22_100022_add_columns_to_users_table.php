<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('gudang_id')->nullable()->constrained('gudang')->nullOnDelete();
            $table->string('no_pegawai')->nullable()->unique();
            $table->string('telepon', 20)->nullable();
            $table->string('foto')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['gudang_id']);
            $table->dropColumn([
                'gudang_id', 'no_pegawai', 'telepon', 'foto',
                'is_active', 'last_login_at', 'deleted_at',
            ]);
        });
    }
};