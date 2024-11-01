<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Helper extends Model
{
    use HasFactory;

    public function category() {
        return $this->belongsTo(Category::class);
    }

    public function problems() {
        return $this->hasMany(Problem::class);
    }

    public function mitras()
    {
        return $this->belongsToMany(Mitra::class, 'mitra_helper');
    }
}
