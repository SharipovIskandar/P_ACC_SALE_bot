<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = ['tg_user_id', 'properties', 'is_approved'];

    protected $casts = [
        'properties' => 'array', // properties ustuni JSON bo'lgani uchun array formatida saqlanadi
    ];

    public function mediaFiles()
    {
        return $this->hasMany(MediaFile::class);
    }
}

