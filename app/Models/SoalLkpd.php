<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Storage;

class SoalLkpd extends Model
{
    use HasFactory;

    protected $table = 'soal_lkpd';

    protected $fillable = [
        'lkpd_id',
        'tipe_soal',
        'pertanyaan',
        'foto',
        'opsi_jawaban',
        'jawaban_benar',
        'urutan',
    ];

    protected $casts = [
        'opsi_jawaban' => 'array',
    ];

    protected $appends = ['foto_url'];

    public function getFotoUrlAttribute()
    {
        if ($this->foto) {
            /** @var \Illuminate\Filesystem\FilesystemAdapter $storage */
            $storage = Storage::disk('public');
            return $storage->url($this->foto);
        }
        return null;
    }

    public function lkpd()
    {
        return $this->belongsTo(Lkpd::class);
    }

    public function jawaban()
    {
        return $this->hasMany(JawabanSiswa::class, 'soal_id');
    }
}
