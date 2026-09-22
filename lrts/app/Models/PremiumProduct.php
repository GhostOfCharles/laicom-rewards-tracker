<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PremiumProduct extends Model
{
    protected $fillable = ['item_code', 'name', 'description', 'category', 'image_path'];

    public function inventory() {
        return $this->hasOne(Inventory::class);
    }

    public function promotions() {
        return $this->hasMany(Promotion::class);
    }
}