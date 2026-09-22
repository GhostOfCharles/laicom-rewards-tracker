<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $fillable = ['premium_product_id', 'stock_balance', 'reserved_stock', 'image_path'];

    public function premiumProduct() {
        return $this->belongsTo(PremiumProduct::class);
    }
}