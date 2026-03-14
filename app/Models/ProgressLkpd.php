<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgressLkpd extends Model
{
    use HasFactory;

    protected $table = 'progress_lkpd';

    protected $fillable = [
        'user_id',
        'lkpd_id',
        'status',
        'progress_percentage',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lkpd()
    {
        return $this->belongsTo(Lkpd::class);
    }
}
