<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JawabanSiswa extends Model
{
    use HasFactory;

    protected $table = 'jawaban_siswa';

    protected $fillable = [
        'soal_id',
        'user_id',
        'jawaban',
        'jawaban_foto_url',
        'jawaban_video_url',
        'tipe_jawaban',
        'nilai',
        'feedback',
        'is_graded',
    ];

    protected $casts = [
        'is_graded' => 'boolean',
    ];

    public function soal()
    {
        return $this->belongsTo(SoalLkpd::class, 'soal_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
