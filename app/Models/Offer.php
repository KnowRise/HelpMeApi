<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory;

    public function mitra()
    {
        return $this->belongsTo(Mitra::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
