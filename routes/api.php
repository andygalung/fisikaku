<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LkpdController;
use App\Http\Controllers\Api\MateriController;
use App\Http\Controllers\Api\SoalController;
use App\Http\Controllers\Api\JawabanController;
use App\Http\Controllers\Api\DiskusiController;
use App\Http\Controllers\Api\GamifikasiController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\ReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes (no auth required)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Protected routes (require Sanctum auth token)
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Profile
    Route::get('/profile', [ProfileController::class, 'getProfile']);
    Route::put('/profile', [ProfileController::class, 'updateProfile']);
    Route::post('/profile/change-password', [ProfileController::class, 'changePassword']);
    Route::post('/profile/photo', [ProfileController::class, 'uploadPhoto']);

    // LKPD
    Route::apiResource('lkpd', LkpdController::class);

    // Materi
    Route::get('/lkpd/{lkpdId}/materi', [MateriController::class, 'index']);
    Route::post('/lkpd/{lkpdId}/materi', [MateriController::class, 'store']);
    Route::put('/materi/{id}', [MateriController::class, 'update']);
    Route::post('/materi/{id}', [MateriController::class, 'update']); // untuk multipart _method=PUT
    Route::delete('/materi/{id}', [MateriController::class, 'destroy']);

    // Soal
    Route::get('/lkpd/{lkpdId}/soal', [SoalController::class, 'index']);
    Route::post('/soal', [SoalController::class, 'store']);
    Route::put('/soal/{id}', [SoalController::class, 'update']);
    Route::delete('/soal/{id}', [SoalController::class, 'destroy']);
    Route::post('/soal/reorder', [SoalController::class, 'reorder']);

    // Jawaban
    Route::post('/jawaban/bulk', [JawabanController::class, 'submitBulk']);
    Route::post('/jawaban', [JawabanController::class, 'store']);
    Route::get('/lkpd/{lkpdId}/jawaban/my', [JawabanController::class, 'getMyAnswers']);
    Route::get('/lkpd/{lkpdId}/jawaban', [JawabanController::class, 'getByLkpd']);
    Route::patch('/jawaban/{id}/grade', [JawabanController::class, 'grade']);
    Route::post('/jawaban/photo', [JawabanController::class, 'uploadPhoto']);
    Route::post('/jawaban/video', [JawabanController::class, 'uploadVideo']);

    // Diskusi
    Route::get('/lkpd/{lkpdId}/diskusi', [DiskusiController::class, 'index']);
    Route::post('/diskusi', [DiskusiController::class, 'store']);
    Route::post('/diskusi/{id}/important', [DiskusiController::class, 'markImportant']);
    Route::delete('/diskusi/{id}', [DiskusiController::class, 'destroy']);

    // Gamifikasi
    Route::get('/gamifikasi/me', [GamifikasiController::class, 'myGamifikasi']);
    Route::get('/gamifikasi/leaderboard', [GamifikasiController::class, 'leaderboard']);

    // Students (guru only)
    Route::get('/students', [StudentController::class, 'index']);

    // Laporan (guru only)
    Route::get('/reports/lkpd-summary', [ReportController::class, 'getLkpdSummary']);
    Route::get('/reports/student-summary', [ReportController::class, 'getStudentSummary']);
});

// Route Sementara untuk Migrasi & Fix di Hostinger (Hapus setelah digunakan!)
Route::get('/run-migration', function () {
    try {
        Artisan::call('migrate', ['--force' => true]);
        return "Migrasi Berhasil: " . Artisan::output();
    } catch (\Exception $e) {
        return "Migrasi Gagal: " . $e->getMessage();
    }
});

Route::get('/fix-database', function () {
    try {
        // Paksa tambah kolom 'foto' jika belum ada
        if (!Schema::hasColumn('soal_lkpd', 'foto')) {
            Schema::table('soal_lkpd', function ($table) {
                $table->string('foto')->nullable()->after('pertanyaan');
            });
            return "Kolom 'foto' berhasil ditambahkan ke tabel soal_lkpd.";
        }
        return "Kolom 'foto' sudah ada di tabel soal_lkpd.";
    } catch (\Exception $e) {
        return "Error Fix Database: " . $e->getMessage();
    }
});
