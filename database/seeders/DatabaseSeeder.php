<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Lkpd;
use App\Models\Materi;
use App\Models\SoalLkpd;
use App\Models\Gamifikasi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Teacher
        $guru = User::create([
            'nama' => 'Pak Budi',
            'email' => 'guru@fisikaku.com',
            'password' => Hash::make('password'),
            'role' => 'guru',
            'kelas' => null,
        ]);

        // Create Students
        $siswa1 = User::create([
            'nama' => 'Andi',
            'email' => 'andi@siswa.com',
            'password' => Hash::make('password'),
            'role' => 'siswa',
            'kelas' => 'X MIPA 1',
        ]);

        $siswa2 = User::create([
            'nama' => 'Siti',
            'email' => 'siti@siswa.com',
            'password' => Hash::make('password'),
            'role' => 'siswa',
            'kelas' => 'X MIPA 1',
        ]);

        // Create Gamifikasi for students
        Gamifikasi::create([
            'user_id' => $siswa1->id,
            'poin' => 0,
            'badge' => [],
        ]);

        Gamifikasi::create([
            'user_id' => $siswa2->id,
            'poin' => 0,
            'badge' => [],
        ]);

        // Create LKPD
        $lkpd = Lkpd::create([
            'judul' => 'Hukum Newton',
            'deskripsi' => 'LKPD tentang Hukum Newton I, II, dan III',
            'guru_id' => $guru->id,
        ]);

        // Create Materi
        Materi::create([
            'lkpd_id' => $lkpd->id,
            'ringkasan_materi' => 'Hukum Newton menjelaskan hubungan antara gaya dan gerak benda.',
            'file_url' => null,
            'video_url' => 'https://www.youtube.com/watch?v=example',
            'animasi_url' => null,
            'animasi_type' => null,
        ]);

        // Create Questions
        SoalLkpd::create([
            'lkpd_id' => $lkpd->id,
            'tipe_soal' => 'multiple_choice',
            'pertanyaan' => 'Apa bunyi Hukum Newton I?',
            'opsi_jawaban' => json_encode([
                'A' => 'Benda diam akan tetap diam',
                'B' => 'F = m x a',
                'C' => 'Aksi = Reaksi',
                'D' => 'Semua salah',
            ]),
            'jawaban_benar' => 'A',
            'urutan' => 1,
        ]);

        SoalLkpd::create([
            'lkpd_id' => $lkpd->id,
            'tipe_soal' => 'text',
            'pertanyaan' => 'Jelaskan penerapan Hukum Newton II dalam kehidupan sehari-hari!',
            'opsi_jawaban' => null,
            'jawaban_benar' => null,
            'urutan' => 2,
        ]);

        $this->command->info('Database seeded successfully!');
        $this->command->info('Teacher: guru@fisikaku.com / password');
        $this->command->info('Student 1: andi@siswa.com / password');
        $this->command->info('Student 2: siti@siswa.com / password');
    }
}
