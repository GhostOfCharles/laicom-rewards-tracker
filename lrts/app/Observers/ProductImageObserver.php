<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductImageObserver
{
    public function updated(Model $model): void
    {
        if ($model->wasChanged('image_path')) {
            $this->removeAfterCommit($model->getRawOriginal('image_path'));
        }
    }

    public function deleted(Model $model): void
    {
        $this->removeAfterCommit($model->image_path);
    }

    private function removeAfterCommit(?string $path): void
    {
        if ($path) {
            DB::afterCommit(fn () => Storage::disk('public')->delete($path));
        }
    }
}
