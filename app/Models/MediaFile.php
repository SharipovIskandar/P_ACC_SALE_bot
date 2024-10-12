<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaFile extends Model
{
    protected $fillable = ['account_id', 'media_file_path'];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}

