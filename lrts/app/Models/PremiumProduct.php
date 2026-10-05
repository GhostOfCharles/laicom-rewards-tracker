<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PremiumProduct extends Model
{
    public const CATEGORIES = Inventory::CATEGORIES;

    protected $fillable = [
        'item_code', 'name', 'stock', 'description', 'category', 'image_path',
        'is_perishable', 'entry_date', 'expiry_date',
    ];

    protected function casts(): array
    {
        return [
            'is_perishable' => 'boolean',
            'entry_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function isExpired(): bool
    {
        return $this->is_perishable && $this->expiry_date !== null && $this->expiry_date->lt(today());
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->is_perishable
            && $this->expiry_date !== null
            && ! $this->isExpired()
            && $this->expiry_date->lte(today()->addDays($days));
    }

    public function expiryStatus(): string
    {
        if (! $this->is_perishable) {
            return 'non_perishable';
        }

        if ($this->isExpired()) {
            return 'expired';
        }

        return $this->isExpiringSoon() ? 'expiring_soon' : 'healthy';
    }

    public function daysUntilExpiry(): ?int
    {
        if (! $this->is_perishable || ! $this->expiry_date) {
            return null;
        }

        return (int) today()->diffInDays($this->expiry_date, false);
    }

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
