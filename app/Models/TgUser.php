<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TgUser extends Model
{
    use HasFactory;
    protected $fillable = [
      'chat_id',
        'username'
    ];
}
