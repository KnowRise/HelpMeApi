<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderAttachments()
    {
        return $this->hasMany(OrderAttachment::class);
    }

    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function problem()
    {
        return $this->belongsTo(Problem::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function rating()
    {
        return $this->hasOne(Rating::class);
    }

    public function mitra()
    {
        return $this->belongsTo(Mitra::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    public function acceptedOffer()
    {
        return $this->belongsTo(Offer::class, 'offer_id');
    }
}
