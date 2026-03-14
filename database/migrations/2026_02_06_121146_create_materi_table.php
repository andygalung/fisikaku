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
        Schema::create('materi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lkpd_id')->constrained('lkpd')->onDelete('cascade');
            $table->text('ringkasan_materi')->nullable();
            $table->string('file_url')->nullable(); // PDF/Word/PPT
            $table->string('video_url')->nullable(); // YouTube or local
            $table->string('animasi_url')->nullable(); // Animation file path
            $table->enum('animasi_type', ['gif', 'video', 'html'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materi');
    }
};
