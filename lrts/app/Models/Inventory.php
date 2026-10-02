<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    public const CATEGORIES = [
        'home_care' => 'Home Care',
        'food' => 'Food',
        'personal_care' => 'Personal Care',
    ];

    protected $fillable = [
        'name', 'category', 'stock_balance', 'reserved_stock', 'image_path', 'entry_date', 'expiry_date',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ($this->category ? 'Uncategorized (' . $this->category . ')' : 'Uncategorized');
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }
}
