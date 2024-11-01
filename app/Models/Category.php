<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    public function helpers()

    {
        return $this->hasMany(Helper::class);
    }

    public function mitras()
    {
        return $this->hasMany(Mitra::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
