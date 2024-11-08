<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mitra extends Model
{
    use HasFactory;
    protected $casts = [
        'latitude' => 'decimal:6', // atau 'decimal:6' untuk presisi tertentu
        'longitude' => 'decimal:6',
    ];
    
       public function getLatitudeAttribute($value)
    {
        return (float) $value;
    }

    // Accessor untuk longitude
    public function getLongitudeAttribute($value)
    {
        return (float) $value;
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_identifier', 'identifier');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    public function helpers()
    {
        return $this->belongsToMany(Helper::class, 'mitra_helper', 'mitra_id', 'helper_id');
    }
}
