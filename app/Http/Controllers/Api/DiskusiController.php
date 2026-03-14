<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Diskusi;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class DiskusiController extends Controller
{
    /**
     * Get all discussions for a specific LKPD
     */
    public function index($lkpd_id)
    {
        try {
            $diskusi = Diskusi::with('user:id,nama,role')
                ->where('lkpd_id', $lkpd_id)
                ->orderBy('is_important', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Diskusi berhasil diambil',
                'data' => $diskusi
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil diskusi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new discussion post
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lkpd_id' => 'required|exists:lkpd,id',
            'pesan' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $diskusi = Diskusi::create([
                'lkpd_id' => $request->lkpd_id,
                'user_id' => Auth::id(),
                'pesan' => $request->pesan,
                'is_important' => false
            ]);

            $diskusi->load('user:id,nama,role');

            return response()->json([
                'status' => true,
                'message' => 'Diskusi berhasil dibuat',
                'data' => $diskusi
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal membuat diskusi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark discussion as important (teacher only)
     */
    public function markImportant($id)
    {
        try {
            $user = Auth::user();
            
            if ($user->role !== 'guru') {
                return response()->json([
                    'status' => false,
                    'message' => 'Hanya guru yang dapat menandai diskusi penting'
                ], 403);
            }

            $diskusi = Diskusi::findOrFail($id);
            $diskusi->is_important = !$diskusi->is_important;
            $diskusi->save();

            return response()->json([
                'status' => true,
                'message' => 'Status penting berhasil diubah',
                'data' => $diskusi
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengubah status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete discussion post
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $diskusi = Diskusi::findOrFail($id);

            // Guru can delete any post, students only their own
            if ($user->role !== 'guru' && $diskusi->user_id !== $user->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $diskusi->delete();

            return response()->json([
                'status' => true,
                'message' => 'Diskusi berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus diskusi: ' . $e->getMessage()
            ], 500);
        }
    }
}
