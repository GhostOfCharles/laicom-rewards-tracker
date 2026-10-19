<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'home_care' => 'Home Care',
        'food' => 'Food',
        'personal_care' => 'Personal Care',
    ];

    protected $fillable = [
        'name', 'name_key', 'category', 'stock_balance', 'image_path', 'entry_date', 'expiry_date',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public static function nameKey(string $name): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($name)));
    }

    protected static function booted(): void
    {
        static::saving(function (self $item) {
            $item->name = preg_replace('/\s+/u', ' ', trim($item->name));
            $item->name_key = self::nameKey($item->name);
        });
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ($this->category ? 'Uncategorized ('.$this->category.')' : 'Uncategorized');
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }
}
