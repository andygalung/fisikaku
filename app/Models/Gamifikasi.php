<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gamifikasi extends Model
{
    protected $table = 'gamifikasi';
    
    protected $fillable = [
        'user_id',
        'poin',
        'badge'
    ];
    
    protected $casts = [
        'badge' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
