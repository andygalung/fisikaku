<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /**
     * Get all students (role=siswa), accessible by guru only.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'guru') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Only guru can access this endpoint.'
            ], 403);
        }

        $students = User::where('role', 'siswa')
            ->orderBy('kelas')
            ->orderBy('nama')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Daftar siswa berhasil diambil',
            'data' => $students
        ]);
    }
}
