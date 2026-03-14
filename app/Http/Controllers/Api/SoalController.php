<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SoalLkpd;
use App\Models\Lkpd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\Storage;

class SoalController extends Controller
{
    public function index($lkpd_id)
    {
        $soal = SoalLkpd::where('lkpd_id', $lkpd_id)
            ->orderBy('urutan')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $soal
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'guru') {
            return response()->json([
                'status' => false,
                'message' => 'Only teachers can add questions'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'lkpd_id' => 'required|exists:lkpd,id',
            'tipe_soal' => 'required|in:text,multiple_choice,drag_drop,analysis,photo_upload,video_upload',
            'pertanyaan' => 'required|string',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'opsi_jawaban' => 'nullable|string',
            'jawaban_benar' => 'nullable|string',
            'urutan' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if teacher owns this LKPD
        $lkpd = Lkpd::find($request->lkpd_id);
        if ($lkpd->guru_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $data = $request->only([
            'lkpd_id',
            'tipe_soal',
            'pertanyaan',
            'opsi_jawaban',
            'jawaban_benar',
            'urutan'
        ]);

        // Handle JSON string for opsi_jawaban if it's Multipart
        if (isset($data['opsi_jawaban']) && is_string($data['opsi_jawaban'])) {
            $data['opsi_jawaban'] = json_decode($data['opsi_jawaban'], true);
        }

        // Handle Photo Upload
        if ($request->hasFile('foto')) {
            $path = $request->file('foto')->store('soal_photos', 'public');
            // Hanya masukkan ke data jika kolom 'foto' ada di database
            if (\Illuminate\Support\Facades\Schema::hasColumn('soal_lkpd', 'foto')) {
                $data['foto'] = $path;
            }
        }

        // Auto-set urutan if not provided
        if (!isset($data['urutan'])) {
            $maxUrutan = SoalLkpd::where('lkpd_id', $data['lkpd_id'])->max('urutan');
            $data['urutan'] = ($maxUrutan ?? 0) + 1;
        }

        $soal = SoalLkpd::create($data);

        return response()->json([
            'status' => true,
            'message' => 'Question added successfully',
            'data' => $soal
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $soal = SoalLkpd::find($id);

        if (!$soal) {
            return response()->json([
                'status' => false,
                'message' => 'Question not found'
            ], 404);
        }

        // Check if teacher owns this LKPD
        $lkpd = $soal->lkpd;
        if ($user->role !== 'guru' || $lkpd->guru_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'tipe_soal' => 'sometimes|required|in:text,multiple_choice,drag_drop,analysis,photo_upload,video_upload',
            'pertanyaan' => 'sometimes|required|string',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'opsi_jawaban' => 'nullable|string',
            'jawaban_benar' => 'nullable|string',
            'urutan' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();

        // Handle JSON string for opsi_jawaban if it's Multipart
        if (isset($data['opsi_jawaban']) && is_string($data['opsi_jawaban'])) {
            $data['opsi_jawaban'] = json_decode($data['opsi_jawaban'], true);
        }

        // Handle Photo Upload
        if ($request->hasFile('foto')) {
            // Delete old photo if exists
            if ($soal->foto) {
                Storage::disk('public')->delete($soal->foto);
            }
            $path = $request->file('foto')->store('soal_photos', 'public');
            $data['foto'] = $path;
        }

        $soal->update($data);

        return response()->json([
            'status' => true,
            'message' => 'Question updated successfully',
            'data' => $soal
        ]);
    }

    public function reorder(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'lkpd_id' => 'required|exists:lkpd,id',
            'soal_order' => 'required|array',
            'soal_order.*.id' => 'required|exists:soal_lkpd,id',
            'soal_order.*.urutan' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if teacher owns this LKPD
        $lkpd = Lkpd::find($request->lkpd_id);
        if ($user->role !== 'guru' || $lkpd->guru_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        foreach ($request->soal_order as $item) {
            SoalLkpd::where('id', $item['id'])->update(['urutan' => $item['urutan']]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Questions reordered successfully'
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $soal = SoalLkpd::find($id);

        if (!$soal) {
            return response()->json([
                'status' => false,
                'message' => 'Question not found'
            ], 404);
        }

        // Check if teacher owns this LKPD
        $lkpd = $soal->lkpd;
        if ($user->role !== 'guru' || $lkpd->guru_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $soal->delete();

        return response()->json([
            'status' => true,
            'message' => 'Question deleted successfully'
        ]);
    }
}
