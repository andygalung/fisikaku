<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lkpd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LkpdController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        if ($user->role === 'guru') {
            $lkpd = Lkpd::where('guru_id', $user->id)
                ->with(['materi', 'soal'])
                ->latest()
                ->get();
        } else {
            $lkpd = Lkpd::with(['guru', 'materi', 'soal'])
                ->latest()
                ->get();
        }

        return response()->json([
            'status' => true,
            'data' => $lkpd
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'guru') {
            return response()->json([
                'status' => false,
                'message' => 'Only teachers can create LKPD'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $lkpd = Lkpd::create([
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
            'guru_id' => $user->id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'LKPD created successfully',
            'data' => $lkpd->load(['materi', 'soal'])
        ], 201);
    }

    public function show($id)
    {
        $lkpd = Lkpd::with(['guru', 'materi', 'soal', 'progress.user'])
            ->find($id);

        if (!$lkpd) {
            return response()->json([
                'status' => false,
                'message' => 'LKPD not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $lkpd
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $lkpd = Lkpd::find($id);

        if (!$lkpd) {
            return response()->json([
                'status' => false,
                'message' => 'LKPD not found'
            ], 404);
        }

        if ($user->role !== 'guru' || $lkpd->guru_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'judul' => 'sometimes|required|string|max:255',
            'deskripsi' => 'sometimes|required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $lkpd->update($request->only(['judul', 'deskripsi']));

        return response()->json([
            'status' => true,
            'message' => 'LKPD updated successfully',
            'data' => $lkpd->load(['materi', 'soal'])
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $lkpd = Lkpd::find($id);

        if (!$lkpd) {
            return response()->json([
                'status' => false,
                'message' => 'LKPD not found'
            ], 404);
        }

        if ($user->role !== 'guru' || $lkpd->guru_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $lkpd->delete();

        return response()->json([
            'status' => true,
            'message' => 'LKPD deleted successfully'
        ]);
    }
}
