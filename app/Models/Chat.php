<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function userAsClient()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function userAsMitra()
    {
        return $this->belongsTo(User::class,'mitra_id');
    }
}