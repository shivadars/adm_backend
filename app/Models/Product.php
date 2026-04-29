<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'collection',
        'sub_category',
        'name',
        'description',
        'tags',
        'mrp',
        'price',
        'materials',
        'colors',
        'image',
        'status',
        'featured',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
