<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    protected static function booted(): void
    {
        static::saving(function (self $record) {
            if (! $record->inventory_id || $record->isDirty('buy_product_name')) {
                $record->inventory_id = Inventory::where('name_key', Inventory::nameKey($record->buy_product_name))->value('id');
            }
        });
    }

    protected $fillable = ['inventory_id',
        'title', 'description', 'buy_product_name', 'premium_product_id',
        'required_quantity', 'reward_quantity', 'start_date', 'end_date', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function premiumProduct()
    {
        return $this->belongsTo(PremiumProduct::class);
    }
}
