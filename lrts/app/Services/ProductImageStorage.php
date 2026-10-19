<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductImageStorage
{
    public function store(UploadedFile $file, string $folder): string
    {
        $bytes = file_get_contents($file->getRealPath());
        $size = $bytes === false ? false : @getimagesizefromstring($bytes);
        $limit = ini_parse_quantity(ini_get('memory_limit'));
        if (! $size || $size[0] * $size[1] > 40_000_000 || ($limit > 0 && $limit - memory_get_usage(true) < $size[0] * $size[1] * 8 + 16777216)) {
            throw ValidationException::withMessages(['image' => 'Choose a smaller, valid product image (maximum 40 megapixels).']);
        }
        $image = @imagecreatefromstring($bytes);
        unset($bytes);
        if (! $image) {
            throw ValidationException::withMessages(['image' => 'This product image could not be decoded.']);
        }
        $scale = min(1, 400 / max($size[0], $size[1]));
        $width = max(1, (int) round($size[0] * $scale));
        $height = max(1, (int) round($size[1] * $scale));
        $thumbnail = imagecreatetruecolor($width, $height);
        imagefill($thumbnail, 0, 0, imagecolorallocate($thumbnail, 255, 255, 255));
        imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $width, $height, $size[0], $size[1]);
        ob_start();
        imagejpeg($thumbnail, null, 85);
        $encoded = ob_get_clean();
        imagedestroy($image);
        imagedestroy($thumbnail);
        $path = $folder.'/'.Str::uuid().'.jpg';
        try {
            if (! Storage::disk('public')->put($path, $encoded)) {
                throw new \RuntimeException('Image write failed.');
            }
        } catch (\Throwable $exception) {
            Log::warning('Product storage unavailable.', ['exception_type' => get_class($exception)]);
            throw ValidationException::withMessages(['image' => 'Product image storage is unavailable. Try again.']);
        }

        return $path;
    }
}
