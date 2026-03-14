<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Materi extends Model
{
    use HasFactory;

    protected $table = 'materi';

    protected $fillable = [
        'lkpd_id',
        'ringkasan_materi',
        'file_url',
        'video_url',
        'animasi_url',
        'animasi_type',
    ];

    public function lkpd()
    {
        return $this->belongsTo(Lkpd::class);
    }
}
