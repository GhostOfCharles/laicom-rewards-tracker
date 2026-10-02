<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    public const TYPES = ['inflow', 'outflow'];

    protected $fillable = ['inventory_id', 'type', 'quantity', 'reference', 'notes'];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }
}
