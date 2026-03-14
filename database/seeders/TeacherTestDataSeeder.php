<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Lkpd;
use App\Models\Materi;
use App\Models\SoalLkpd;
use Illuminate\Support\Facades\Hash;

class TeacherTestDataSeeder extends Seeder
{
    public function run()
    {
        // Create a test teacher
        $teacher = User::firstOrCreate(
            ['email' => 'guru@test.com'],
            [
                'name' => 'Guru Test',
                'password' => Hash::make('password'),
                'role' => 'guru'
            ]
        );

        echo "Teacher created/found: {$teacher->name}\n";

        // Create test LKPDs
        $lkpd1 = Lkpd::firstOrCreate(
            [
                'judul' => 'Gerak Lurus',
                'guru_id' => $teacher->id
            ],
            [
                'deskripsi' => 'LKPD tentang gerak lurus beraturan dan gerak lurus berubah beraturan'
            ]
        );

        $lkpd2 = Lkpd::firstOrCreate(
            [
                'judul' => 'Hukum Newton',
                'guru_id' => $teacher->id
            ],
            [
                'deskripsi' => 'LKPD tentang hukum-hukum Newton dan penerapannya'
            ]
        );

        $lkpd3 = Lkpd::firstOrCreate(
            [
                'judul' => 'Usaha dan Energi',
                'guru_id' => $teacher->id
            ],
            [
                'deskripsi' => 'LKPD tentang konsep usaha, energi kinetik, dan energi potensial'
            ]
        );

        echo "LKPDs created: {$lkpd1->judul}, {$lkpd2->judul}, {$lkpd3->judul}\n";

        // Add materials to LKPD 1
        Materi::firstOrCreate(
            [
                'lkpd_id' => $lkpd1->id,
                'judul' => 'Pengantar Gerak Lurus'
            ],
            [
                'tipe' => 'pdf',
                'file_path' => 'materi/gerak_lurus_intro.pdf'
            ]
        );

        Materi::firstOrCreate(
            [
                'lkpd_id' => $lkpd1->id,
                'judul' => 'Video Demonstrasi GLB'
            ],
            [
                'tipe' => 'video',
                'file_path' => 'materi/glb_demo.mp4'
            ]
        );

        // Add materials to LKPD 2
        Materi::firstOrCreate(
            [
                'lkpd_id' => $lkpd2->id,
                'judul' => 'Hukum Newton I, II, III'
            ],
            [
                'tipe' => 'pdf',
                'file_path' => 'materi/hukum_newton.pdf'
            ]
        );

        // Add questions to LKPD 1
        SoalLkpd::firstOrCreate(
            [
                'lkpd_id' => $lkpd1->id,
                'urutan' => 1
            ],
            [
                'pertanyaan' => 'Jelaskan perbedaan antara gerak lurus beraturan dan gerak lurus berubah beraturan!',
                'tipe' => 'essay',
                'pilihan_jawaban' => null
            ]
        );

        SoalLkpd::firstOrCreate(
            [
                'lkpd_id' => $lkpd1->id,
                'urutan' => 2
            ],
            [
                'pertanyaan' => 'Sebuah mobil bergerak dengan kecepatan 20 m/s. Berapa jarak yang ditempuh dalam 10 detik?',
                'tipe' => 'pilihan_ganda',
                'pilihan_jawaban' => json_encode([
                    'A' => '100 m',
                    'B' => '200 m',
                    'C' => '300 m',
                    'D' => '400 m'
                ])
            ]
        );

        // Add questions to LKPD 2
        SoalLkpd::firstOrCreate(
            [
                'lkpd_id' => $lkpd2->id,
                'urutan' => 1
            ],
            [
                'pertanyaan' => 'Sebutkan dan jelaskan tiga hukum Newton!',
                'tipe' => 'essay',
                'pilihan_jawaban' => null
            ]
        );

        echo "Test data seeding completed!\n";
    }
}
