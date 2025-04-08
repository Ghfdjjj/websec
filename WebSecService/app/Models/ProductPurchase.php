<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPurchase extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'product_id',
        'price_paid',
        'purchased_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'purchased_at' => 'datetime',
    ];

    /**
     * Get the user that made the purchase.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product that was purchased.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
} 