<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProgressLkpd;
use App\Models\JawabanSiswa;
use App\Models\SoalLkpd;
use Illuminate\Support\Facades\DB;

class ProgressController extends Controller
{
    /**
     * Update progress for a student on a specific LKPD
     */
    public function updateProgress(Request $request)
    {
        try {
            $userId = $request->user_id ?? auth()->id();
            $lkpdId = $request->lkpd_id;

            // Calculate progress based on answered questions
            $totalSoal = SoalLkpd::where('lkpd_id', $lkpdId)->count();
            $answeredSoal = JawabanSiswa::whereHas('soal', function($query) use ($lkpdId) {
                $query->where('lkpd_id', $lkpdId);
            })->where('user_id', $userId)->count();

            $percentage = $totalSoal > 0 ? round(($answeredSoal / $totalSoal) * 100) : 0;
            
            // Determine status
            $status = 'belum_mulai';
            if ($percentage > 0 && $percentage < 100) {
                $status = 'sedang_dikerjakan';
            } elseif ($percentage == 100) {
                $status = 'selesai';
            }

            $progress = ProgressLkpd::updateOrCreate(
                [
                    'user_id' => $userId,
                    'lkpd_id' => $lkpdId
                ],
                [
                    'status' => $status,
                    'progress_percentage' => $percentage,
                    'submitted_at' => $status === 'selesai' ? now() : null
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Progress berhasil diupdate',
                'data' => $progress
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal update progress: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get student's progress for a specific LKPD
     */
    public function getStudentProgress($lkpd_id, $user_id = null)
    {
        try {
            $userId = $user_id ?? auth()->id();
            
            $progress = ProgressLkpd::where('user_id', $userId)
                ->where('lkpd_id', $lkpd_id)
                ->first();

            if (!$progress) {
                $progress = [
                    'user_id' => $userId,
                    'lkpd_id' => $lkpd_id,
                    'status' => 'belum_mulai',
                    'progress_percentage' => 0,
                    'submitted_at' => null
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Progress berhasil diambil',
                'data' => $progress
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil progress: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all students' progress for a specific LKPD (teacher view)
     */
    public function getClassProgress($lkpd_id)
    {
        try {
            $progress = ProgressLkpd::with('user:id,name,email')
                ->where('lkpd_id', $lkpd_id)
                ->orderBy('progress_percentage', 'desc')
                ->get()
                ->map(function($item) {
                    return [
                        'user_id' => $item->user_id,
                        'name' => $item->user->name,
                        'email' => $item->user->email,
                        'status' => $item->status,
                        'progress_percentage' => $item->progress_percentage,
                        'submitted_at' => $item->submitted_at
                    ];
                });

            // Get statistics
            $stats = [
                'total_students' => $progress->count(),
                'completed' => $progress->where('status', 'selesai')->count(),
                'in_progress' => $progress->where('status', 'sedang_dikerjakan')->count(),
                'not_started' => $progress->where('status', 'belum_mulai')->count(),
                'average_progress' => $progress->avg('progress_percentage')
            ];

            return response()->json([
                'success' => true,
                'message' => 'Progress kelas berhasil diambil',
                'data' => [
                    'students' => $progress,
                    'statistics' => $stats
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil progress kelas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all progress for current user (student view)
     */
    public function getMyProgress()
    {
        try {
            $progress = ProgressLkpd::with('lkpd:id,judul,deskripsi')
                ->where('user_id', auth()->id())
                ->orderBy('updated_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Progress berhasil diambil',
                'data' => $progress
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil progress: ' . $e->getMessage()
            ], 500);
        }
    }
}
