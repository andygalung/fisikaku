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
        Schema::create('soal_lkpd', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lkpd_id')->constrained('lkpd')->onDelete('cascade');
            $table->enum('tipe_soal', ['text', 'multiple_choice', 'drag_drop', 'analysis', 'photo_upload', 'video_upload']);
            $table->text('pertanyaan');
            $table->json('opsi_jawaban')->nullable(); // For multiple choice
            $table->string('jawaban_benar')->nullable(); // For auto-grading
            $table->integer('urutan')->default(0); // Question order
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('soal_lkpd');
    }
};
