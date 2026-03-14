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
        Schema::create('progress_lkpd', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('lkpd_id')->constrained('lkpd')->onDelete('cascade');
            $table->enum('status', ['belum_mulai', 'sedang_dikerjakan', 'selesai'])->default('belum_mulai');
            $table->integer('progress_percentage')->default(0); // 0-100
            $table->timestamp('submitted_at')->nullable();
            $table->unique(['user_id', 'lkpd_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('progress_lkpd');
    }
};
