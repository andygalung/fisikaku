<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Lkpd;
use App\Models\User;
use App\Models\JawabanSiswa;
use App\Models\ProgressLkpd;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Get summary of all LKPDs for the teacher
     */
    public function getLkpdSummary(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'guru') {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $lkpds = Lkpd::where('guru_id', $user->id)->get();
        $summary = [];

        foreach ($lkpds as $lkpd) {
            $totalStudents = User::where('role', 'siswa')->count();
            $finishedStudents = ProgressLkpd::where('lkpd_id', $lkpd->id)
                ->where('status', 'selesai')
                ->count();
            
            $avgScore = JawabanSiswa::whereHas('soal', function($query) use ($lkpd) {
                $query->where('lkpd_id', $lkpd->id);
            })->avg('nilai') ?: 0;

            $summary[] = [
                'id' => $lkpd->id,
                'judul' => $lkpd->judul,
                'total_siswa' => $totalStudents,
                'siswa_selesai' => $finishedStudents,
                'rata_rata_nilai' => round($avgScore, 2)
            ];
        }

        return response()->json([
            'status' => true,
            'data' => $summary
        ]);
    }

    /**
     * Get summary of all students for the teacher
     */
    public function getStudentSummary(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'guru') {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $students = User::where('role', 'siswa')->orderBy('nama')->get();
        $summary = [];

        foreach ($students as $student) {
            $completedLkpd = ProgressLkpd::where('user_id', $student->id)
                ->where('status', 'selesai')
                ->count();
            
            $avgScore = JawabanSiswa::where('user_id', $student->id)->avg('nilai') ?: 0;

            $summary[] = [
                'id' => $student->id,
                'nama' => $student->nama,
                'email' => $student->email,
                'kelas' => $student->kelas,
                'lkpd_selesai' => $completedLkpd,
                'rata_rata_nilai' => round($avgScore, 2)
            ];
        }

        return response()->json([
            'status' => true,
            'data' => $summary
        ]);
    }
}
