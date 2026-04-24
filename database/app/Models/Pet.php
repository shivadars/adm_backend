<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'breed',
        'dob',
        'image',
        'instagram_username',
        'status',
    ];

    /**
     * Get the user that owns the pet.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
