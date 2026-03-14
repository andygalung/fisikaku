<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lkpd extends Model
{
    use HasFactory;

    protected $table = 'lkpd';

    protected $fillable = [
        'judul',
        'deskripsi',
        'guru_id',
    ];

    public function guru()
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function materi()
    {
        return $this->hasMany(Materi::class);
    }

    public function soal()
    {
        return $this->hasMany(SoalLkpd::class)->orderBy('urutan');
    }

    public function progress()
    {
        return $this->hasMany(ProgressLkpd::class);
    }

    public function diskusi()
    {
        return $this->hasMany(Diskusi::class);
    }
}
