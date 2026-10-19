<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptItem extends Model
{
    use HasFactory;

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    protected static function booted(): void
    {
        static::saving(function (self $record) {
            if (! $record->inventory_id || $record->isDirty('product_name')) {
                $record->inventory_id = Inventory::where('name_key', Inventory::nameKey($record->product_name))->value('id');
            }
        });
    }

    protected $fillable = ['inventory_id', 'receipt_id', 'product_name', 'quantity', 'unit_price'];

    public function receipt()
    {
        return $this->belongsTo(Receipt::class);
    }
}
