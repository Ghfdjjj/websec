<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'model',
        'description',
        'price',
        'stock',
        'photo'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
    ];

    /**
     * Check if product has sufficient stock
     *
     * @param int $quantity
     * @return bool
     */
    public function hasSufficientStock(int $quantity = 1): bool
    {
        return $this->stock >= $quantity;
    }

    /**
     * Reduce product stock
     *
     * @param int $quantity
     * @return bool
     */
    public function reduceStock(int $quantity = 1): bool
    {
        if (!$this->hasSufficientStock($quantity)) {
            return false;
        }

        $this->stock -= $quantity;
        return $this->save();
    }
}