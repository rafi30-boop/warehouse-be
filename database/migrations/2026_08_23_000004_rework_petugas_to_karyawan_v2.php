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
            $table->string('nama')->nullable()->after('id');
        });

        DB::table('petugas')
            ->join('users', 'users.id', '=', 'petugas.user_id')
            ->whereNull('petugas.nama')
            ->update(['petugas.nama' => DB::raw('users.name')]);

        Schema::table('petugas', function (Blueprint $table) {
            $table->string('nama')->change();

            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('petugas', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        DB::table('petugas')->whereNull('user_id')->delete();

        Schema::table('petugas', function (Blueprint $table) {
            $table->foreignId('user_id')->change();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->dropColumn('nama');
        });
    }
};
