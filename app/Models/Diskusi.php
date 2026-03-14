<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Diskusi extends Model
{
    protected $table = 'diskusi';

    protected $fillable = [
        'lkpd_id',
        'user_id',
        'pesan',
        'is_important'
    ];

    protected $casts = [
        'is_important' => 'boolean',
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
