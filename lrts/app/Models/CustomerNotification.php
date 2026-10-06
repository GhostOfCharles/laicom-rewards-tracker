<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerNotification extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'type', 'title', 'body', 'receipt_id', 'read_at', 'created_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime', 'created_at' => 'datetime'];
    }

    public function receipt()
    {
        return $this->belongsTo(Receipt::class);
    }
}
