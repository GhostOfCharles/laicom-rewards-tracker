<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $fillable = ['name', 'category', 'stock_balance', 'reserved_stock', 'image_path'];
}