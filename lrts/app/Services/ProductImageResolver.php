<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductImageResolver
{
    /**
     * Resolve a product image URL and a text fallback for products without images.
     *
     * @return array{src: string|null, label: string, initials: string}
     */
    public function resolve(?object $product = null, ?string $name = null): array
    {
        $productName = trim((string) data_get($product, 'name', ''));
        $label = $productName !== '' ? $productName : trim((string) $name);
        $imagePath = trim((string) data_get($product, 'image_path', ''));
        $hasStoredImage = $imagePath !== '' && ! str_contains($imagePath, '..');

        $words = preg_split('/\s+/u', $label, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $initials = collect(array_slice($words, 0, 2))
            ->map(fn (string $word) => Str::substr($word, 0, 1))
            ->implode('');

        return [
            'src' => $hasStoredImage ? Storage::disk('public')->url(ltrim($imagePath, '/')) : null,
            'label' => $label !== '' ? $label : 'Product',
            'initials' => $initials !== '' ? Str::upper($initials) : '?',
        ];
    }
}
