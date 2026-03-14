<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\Lkpd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class MateriController extends Controller
{
    public function index($lkpd_id)
    {
        $materi = Materi::where('lkpd_id', $lkpd_id)->get();

        return response()->json([
            'status' => true,
            'data' => $materi
        ]);
    }

    public function store(Request $request, $lkpd_id)
    {
        $user = $request->user();

        if ($user->role !== 'guru') {
            return response()->json([
                'status' => false,
                'message' => 'Only teachers can add materials'
            ], 403);
        }

        // Pastikan lkpd_id ada di request data untuk validasi
        $request->merge(['lkpd_id' => $lkpd_id]);

        $validator = Validator::make($request->all(), [
            'lkpd_id' => 'required|exists:lkpd,id',
            'ringkasan_materi' => 'nullable|string',
            'file_url' => 'nullable|string',
            'video_url' => 'nullable|string',
            'animasi_url' => 'nullable|string',
            'animasi_type' => 'nullable|in:gif,video,html',
            'file' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
            'animasi_file' => 'nullable|file|mimes:gif,mp4,mov,avi,webm|max:51200',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $lkpd = Lkpd::find($request->lkpd_id);
        if ($lkpd->guru_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $data = $validator->validated();

        if (isset($data['file'])) {
            unset($data['file']);
        }

        if (isset($data['animasi_file'])) {
            unset($data['animasi_file']);
        }

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = 'materi_file_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('materi/files', $filename, 'public');
            $data['file_url'] = Storage::url($path);
        }

        if ($request->hasFile('animasi_file')) {
            $animasiFile = $request->file('animasi_file');
            $animasiFilename = 'materi_animasi_' . $user->id . '_' . time() . '.' . $animasiFile->getClientOriginalExtension();
            $animasiPath = $animasiFile->storeAs('materi/animasi', $animasiFilename, 'public');
            $data['animasi_url'] = Storage::url($animasiPath);

            if (empty($data['animasi_type'])) {
                $mime = $animasiFile->getMimeType();
                if ($mime && str_contains($mime, 'gif')) {
                    $data['animasi_type'] = 'gif';
                } else {
                    $data['animasi_type'] = 'video';
                }
            }
        }

        if (!empty($data['video_url'])) {
            $data['video_url'] = $this->normalizeYoutubeUrl($data['video_url']);
        }

        $materi = Materi::create($data);

        return response()->json([
            'status' => true,
            'message' => 'Material added successfully',
            'data' => $materi
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $materi = Materi::find($id);

        if (!$materi) {
            return response()->json([
                'status' => false,
                'message' => 'Material not found'
            ], 404);
        }

        $lkpd = $materi->lkpd;
        if ($user->role !== 'guru' || $lkpd->guru_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'ringkasan_materi' => 'nullable|string',
            'file_url' => 'nullable|string',
            'video_url' => 'nullable|string',
            'animasi_url' => 'nullable|string',
            'animasi_type' => 'nullable|in:gif,video,html',
            'file' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
            'animasi_file' => 'nullable|file|mimes:gif,mp4,mov,avi,webm|max:51200',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        if (isset($data['file'])) {
            unset($data['file']);
        }

        if (isset($data['animasi_file'])) {
            unset($data['animasi_file']);
        }

        if ($request->hasFile('file')) {
            if ($materi->file_url && str_contains($materi->file_url, '/storage/')) {
                $oldPath = parse_url($materi->file_url, PHP_URL_PATH);
                if ($oldPath) {
                    $oldPath = ltrim(str_replace('/storage/', '', $oldPath), '/');
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $file = $request->file('file');
            $filename = 'materi_file_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('materi/files', $filename, 'public');
            $data['file_url'] = Storage::url($path);
        }

        if ($request->hasFile('animasi_file')) {
            if ($materi->animasi_url && str_contains($materi->animasi_url, '/storage/')) {
                $oldPath = parse_url($materi->animasi_url, PHP_URL_PATH);
                if ($oldPath) {
                    $oldPath = ltrim(str_replace('/storage/', '', $oldPath), '/');
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $animasiFile = $request->file('animasi_file');
            $animasiFilename = 'materi_animasi_' . $user->id . '_' . time() . '.' . $animasiFile->getClientOriginalExtension();
            $animasiPath = $animasiFile->storeAs('materi/animasi', $animasiFilename, 'public');
            $data['animasi_url'] = Storage::url($animasiPath);

            if (empty($data['animasi_type'])) {
                $mime = $animasiFile->getMimeType();
                if ($mime && str_contains($mime, 'gif')) {
                    $data['animasi_type'] = 'gif';
                } else {
                    $data['animasi_type'] = 'video';
                }
            }
        }

        if (!empty($data['video_url'])) {
            $data['video_url'] = $this->normalizeYoutubeUrl($data['video_url']);
        }

        $materi->update($data);

        return response()->json([
            'status' => true,
            'message' => 'Material updated successfully',
            'data' => $materi
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $materi = Materi::find($id);

        if (!$materi) {
            return response()->json([
                'status' => false,
                'message' => 'Material not found'
            ], 404);
        }

        $lkpd = $materi->lkpd;
        if ($user->role !== 'guru' || $lkpd->guru_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $materi->delete();

        return response()->json([
            'status' => true,
            'message' => 'Material deleted successfully'
        ]);
    }

    private function normalizeYoutubeUrl($url)
    {
        if (!$url) {
            return $url;
        }

        $trimmed = trim($url);

        if ($trimmed === '') {
            return $trimmed;
        }

        $parsed = parse_url($trimmed);

        if (!isset($parsed['host'])) {
            return $trimmed;
        }

        $host = strtolower($parsed['host']);
        $videoId = null;

        if (str_contains($host, 'youtu.be')) {
            $path = $parsed['path'] ?? '';
            $videoId = ltrim($path, '/');
        } elseif (str_contains($host, 'youtube.com')) {
            $path = $parsed['path'] ?? '';

            if (strpos($path, '/watch') === 0) {
                if (!isset($parsed['query'])) {
                    return $trimmed;
                }

                parse_str($parsed['query'], $query);
                if (empty($query['v'])) {
                    return $trimmed;
                }

                $videoId = $query['v'];
            } elseif (strpos($path, '/embed/') === 0) {
                return $trimmed;
            } elseif (strpos($path, '/shorts/') === 0) {
                $videoId = substr($path, strlen('/shorts/'));
            } else {
                return $trimmed;
            }
        } else {
            return $trimmed;
        }

        if (!$videoId) {
            return $trimmed;
        }

        return 'https://www.youtube.com/embed/' . $videoId;
    }
}
