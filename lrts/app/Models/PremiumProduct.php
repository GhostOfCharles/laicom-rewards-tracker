<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PremiumProduct extends Model
{
    public const CATEGORIES = Inventory::CATEGORIES;

    protected $fillable = ['item_code', 'name', 'stock', 'description', 'category', 'image_path'];

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ($this->category ? 'Uncategorized (' . $this->category . ')' : 'Uncategorized');
    }

    public function promotions() {
        return $this->hasMany(Promotion::class);
    }

    public function earnedRewards() {
        return $this->hasMany(EarnedReward::class);
    }
}
