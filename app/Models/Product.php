<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id',
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

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the order items associated with the product.
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
