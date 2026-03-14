<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JawabanSiswa;
use App\Models\SoalLkpd;
use App\Models\Lkpd;
use App\Models\Gamifikasi;
use App\Models\ProgressLkpd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class JawabanController extends Controller
{
    /**
     * Grade a student's answer (teacher only)
     */
    public function grade(Request $request, $id)
    {
        $user = $request->user();

        if ($user->role !== 'guru') {
            return response()->json([
                'status' => false,
                'message' => 'Only teachers can grade answers'
            ], 403);
        }

        $jawaban = JawabanSiswa::with(['soal.lkpd'])->find($id);

        if (!$jawaban) {
            return response()->json([
                'status' => false,
                'message' => 'Answer not found'
            ], 404);
        }

        // Check if teacher owns this LKPD
        if ($jawaban->soal->lkpd->guru_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nilai' => 'required|integer|min:0|max:100',
            'feedback' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $jawaban->update([
            'nilai' => $request->nilai,
            'feedback' => $request->feedback,
            'is_graded' => true,
        ]);

        // Auto-award gamification points (1:1 formula: score = points)
        try {
            $gamifikasi = \App\Models\Gamifikasi::firstOrCreate(
                ['user_id' => $jawaban->user_id],
                ['poin' => 0, 'badge' => json_encode([])]
            );
            
            $gamifikasi->poin += $request->nilai;
            $gamifikasi->save();
        } catch (\Exception $e) {
            // Log error but don't fail the grading
            Log::error('Failed to award gamification points: ' . $e->getMessage());
        }

        return response()->json([
            'status' => true,
            'message' => 'Answer graded successfully',
            'data' => $jawaban
        ]);
    }

    /**
     * Get all student answers for an LKPD (teacher only)
     */
    public function getByLkpd(Request $request, $lkpd_id)
    {
        $user = $request->user();

        if ($user->role !== 'guru') {
            return response()->json([
                'status' => false,
                'message' => 'Only teachers can view all answers'
            ], 403);
        }

        $lkpd = Lkpd::find($lkpd_id);

        if (!$lkpd) {
            return response()->json([
                'status' => false,
                'message' => 'LKPD not found'
            ], 404);
        }

        if ($lkpd->guru_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Get all answers grouped by student
        $jawaban = JawabanSiswa::whereHas('soal', function($query) use ($lkpd_id) {
                $query->where('lkpd_id', $lkpd_id);
            })
            ->with(['user', 'soal'])
            ->get()
            ->groupBy('user_id');

        return response()->json([
            'status' => true,
            'data' => $jawaban
        ]);
    }

    /**
     * Get own answers for an LKPD (student only)
     */
    public function getMyAnswers(Request $request, $lkpd_id)
    {
        $user = $request->user();

        $jawaban = JawabanSiswa::whereHas('soal', function($query) use ($lkpd_id) {
                $query->where('lkpd_id', $lkpd_id);
            })
            ->where('user_id', $user->id)
            ->with(['soal'])
            ->get();

        return response()->json([
            'status' => true,
            'data' => $jawaban
        ]);
    }

    /**
     * Submit an answer (student only)
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'siswa') {
            return response()->json([
                'status' => false,
                'message' => 'Only students can submit answers'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'soal_id' => 'required|exists:soal_lkpd,id',
            'jawaban' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if answer already exists
        $existing = JawabanSiswa::where('soal_id', $request->soal_id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            // Update existing answer if not graded yet
            if ($existing->is_graded) {
                return response()->json([
                    'status' => false,
                    'message' => 'Answer already graded, cannot update'
                ], 400);
            }

            $existing->update(['jawaban' => $request->jawaban]);

            return response()->json([
                'status' => true,
                'message' => 'Answer updated successfully',
                'data' => $existing
            ]);
        }

        $jawaban = JawabanSiswa::create([
            'soal_id' => $request->soal_id,
            'user_id' => $user->id,
            'jawaban' => $request->jawaban,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Answer submitted successfully',
            'data' => $jawaban
        ], 201);
    }

    /**
     * Update an answer (student only, before grading)
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $jawaban = JawabanSiswa::find($id);

        if (!$jawaban) {
            return response()->json([
                'status' => false,
                'message' => 'Answer not found'
            ], 404);
        }

        if ($jawaban->user_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if ($jawaban->is_graded) {
            return response()->json([
                'status' => false,
                'message' => 'Answer already graded, cannot update'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'jawaban' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $jawaban->update(['jawaban' => $request->jawaban]);

        return response()->json([
            'status' => true,
            'message' => 'Answer updated successfully',
            'data' => $jawaban
        ]);
    }

    /**
     * Submit entire LKPD with all answers
     */
    public function submitLkpd(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'siswa') {
            return response()->json([
                'status' => false,
                'message' => 'Only students can submit LKPD'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'lkpd_id' => 'required|exists:lkpd,id',
            'jawaban' => 'required|array',
            'jawaban.*.soal_id' => 'required|exists:soal_lkpd,id',
            'jawaban.*.jawaban' => 'nullable|string',
            'jawaban.*.tipe_jawaban' => 'required|in:teks,pilgan,foto,video',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $totalNilai = 0;
        $jumlahSoal = count($request->jawaban);
        $savedAnswers = [];

        foreach ($request->jawaban as $item) {
            $soal = SoalLkpd::find($item['soal_id']);
            
            // Prepare data
            $data = [
                'soal_id' => $item['soal_id'],
                'user_id' => $user->id,
                'tipe_jawaban' => $item['tipe_jawaban'],
            ];

            // Handle different answer types
            if ($item['tipe_jawaban'] === 'teks' || $item['tipe_jawaban'] === 'pilgan') {
                $data['jawaban'] = $item['jawaban'] ?? '';
                
                // Auto-grade multiple choice
                if ($item['tipe_jawaban'] === 'pilgan' && $soal->tipe_soal === 'pilgan') {
                    if ($item['jawaban'] === $soal->jawaban_benar) {
                        $data['nilai'] = 100;
                        $data['is_graded'] = true;
                        $data['feedback'] = 'Jawaban benar!';
                        $totalNilai += 100;
                    } else {
                        $data['nilai'] = 0;
                        $data['is_graded'] = true;
                        $data['feedback'] = 'Jawaban kurang tepat. Coba pelajari lagi materinya.';
                    }
                }
            }

            // Check if answer exists
            $jawaban = JawabanSiswa::updateOrCreate(
                [
                    'soal_id' => $item['soal_id'],
                    'user_id' => $user->id
                ],
                $data
            );

            $savedAnswers[] = $jawaban;
        }

        // Update progress
        $progress = ProgressLkpd::updateOrCreate(
            [
                'user_id' => $user->id,
                'lkpd_id' => $request->lkpd_id
            ],
            [
                'status' => 'selesai',
                'progress_percentage' => 100,
                'submitted_at' => now()
            ]
        );

        // Award points (gamifikasi)
        $poinDiperoleh = max(10, intval($totalNilai / $jumlahSoal)); // Min 10 poin
        $gamifikasi = Gamifikasi::firstOrCreate(
            ['user_id' => $user->id],
            ['poin' => 0, 'badge' => []]
        );
        $gamifikasi->increment('poin', $poinDiperoleh);

        // Auto-award badges
        $this->checkAndAwardBadges($gamifikasi);

        return response()->json([
            'status' => true,
            'message' => 'LKPD submitted successfully',
            'data' => [
                'jawaban' => $savedAnswers,
                'progress' => $progress,
                'poin_diperoleh' => $poinDiperoleh,
                'total_poin' => $gamifikasi->poin
            ]
        ]);
    }

    /**
     * Upload photo answer
     */
    public function uploadPhoto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'soal_id' => 'required|exists:soal_lkpd,id',
            'foto' => 'required|image|max:5120', // 5MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $file = $request->file('foto');
        $filename = 'jawaban_foto_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('jawaban/foto', $filename, 'public');

        // Update or create answer
        $jawaban = JawabanSiswa::updateOrCreate(
            [
                'soal_id' => $request->soal_id,
                'user_id' => $user->id
            ],
            [
                'jawaban_foto_url' => Storage::url($path),
                'tipe_jawaban' => 'foto'
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'Photo uploaded successfully',
            'data' => [
                'url' => Storage::url($path),
                'jawaban' => $jawaban
            ]
        ]);
    }

    /**
     * Upload video answer
     */
    public function uploadVideo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'soal_id' => 'required|exists:soal_lkpd,id',
            'video' => 'required|mimes:mp4,mov,avi|max:51200', // 50MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $file = $request->file('video');
        $filename = 'jawaban_video_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('jawaban/video', $filename, 'public');

        // Update or create answer
        $jawaban = JawabanSiswa::updateOrCreate(
            [
                'soal_id' => $request->soal_id,
                'user_id' => $user->id
            ],
            [
                'jawaban_video_url' => Storage::url($path),
                'tipe_jawaban' => 'video'
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'Video uploaded successfully',
            'data' => [
                'url' => Storage::url($path),
                'jawaban' => $jawaban
            ]
        ]);
    }

    /**
     * Check and award badges based on points
     */
    private function checkAndAwardBadges($gamifikasi)
    {
        $badges = [];
        $poin = $gamifikasi->poin;

        if ($poin >= 2000) $badges[] = 'Einstein Jr';
        elseif ($poin >= 1000) $badges[] = 'Master Fisika';
        elseif ($poin >= 500) $badges[] = 'Bintang Kelas';
        elseif ($poin >= 100) $badges[] = 'Pelajar Aktif';
        else $badges[] = 'Pemula';

        $gamifikasi->update(['badge' => $badges]);
    }

    /**
     * Submit multiple answers at once (student only)
     */
    public function submitBulk(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'siswa') {
            return response()->json(['status' => false, 'message' => 'Hanya siswa yang dapat mengirim jawaban'], 403);
        }

        $validator = Validator::make($request->all(), [
            'lkpd_id' => 'required|exists:lkpd,id',
            'jawaban' => 'required|array',
            'jawaban.*.soal_id' => 'required|exists:soal_lkpd,id',
            'jawaban.*.jawaban' => 'nullable|string',
            'jawaban.*.tipe_jawaban' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        foreach ($request->jawaban as $answerData) {
            JawabanSiswa::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'soal_id' => $answerData['soal_id']
                ],
                [
                    'jawaban' => $answerData['jawaban'],
                    'tipe_jawaban' => $answerData['tipe_jawaban']
                ]
            );
        }

        // Mark LKPD as finished
        $progress = ProgressLkpd::firstOrCreate(
            [
                'user_id' => $user->id,
                'lkpd_id' => $request->lkpd_id
            ]
        );
        $progress->status = 'selesai';
        $progress->save();

        // Award points for submission
        $poinDiperoleh = 10; // Poin default untuk submit
        try {
            $gamifikasi = Gamifikasi::firstOrCreate(
                ['user_id' => $user->id],
                ['poin' => 0, 'badge' => json_encode([])]
            );
            $gamifikasi->poin += $poinDiperoleh;
            $gamifikasi->save();
        } catch (\Exception $e) {
            Log::error('Gagal memberikan poin gamifikasi: ' . $e->getMessage());
        }

        return response()->json([
            'status' => true,
            'message' => 'Jawaban berhasil disubmit!',
            'data' => [
                'poin_diperoleh' => $poinDiperoleh
            ]
        ]);
    }
}
