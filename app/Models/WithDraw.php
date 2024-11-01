<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WithDraw extends Model
{
    use HasFactory;

    public function mitra()
    {
        return $this->belongsTo(Mitra::class);
    }
}
