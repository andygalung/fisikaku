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
        Schema::table('jawaban_siswa', function (Blueprint $table) {
            $table->string('jawaban_foto_url')->nullable()->after('jawaban');
            $table->string('jawaban_video_url')->nullable()->after('jawaban_foto_url');
            $table->enum('tipe_jawaban', ['teks', 'pilgan', 'foto', 'video'])->default('teks')->after('jawaban_video_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jawaban_siswa', function (Blueprint $table) {
            $table->dropColumn(['jawaban_foto_url', 'jawaban_video_url', 'tipe_jawaban']);
        });
    }
};
