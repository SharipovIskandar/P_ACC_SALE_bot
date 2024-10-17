<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TgUser extends Model
{
    protected $fillable = ['chat_id', 'username'];

    public function submissions()
    {
        return $this->hasMany(Submission::class, 'user_id', 'id');
    }

    public function balans()
    {
        return $this->hasOne(UserBalans::class, 'user_id', 'id');
    }
}
