<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ReceiptSubmissionService
{
    private const MAX_SLIP_EDGE = 1600;

    private const MAX_COMPRESSED_SLIP_BYTES = 1_048_576;

    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function createFromRequest(Request $request, User $user): Receipt
    {
        $minimumDate = today()->subDays((int) config('lrts.order_max_age_days', 60))->toDateString();
        $slipLimit = (int) config('lrts.slip_max_kb', 10240);

        $validated = $request->validate([
            'salesman_order_number' => ['required', 'string', 'max:100'],
            'order_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:'.$minimumDate],
            'slip' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.$slipLimit],
            'customer_note' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ], [
            'items.required' => 'You must add at least one product to your item listbox.',
            'items.max' => 'An order can contain no more than 50 product lines.',
            'items.*.quantity.max' => 'Each product quantity must be 9,999 or less.',
        ]);

        $orderNumber = $this->normalizeOrderNumber($validated['salesman_order_number']);
        if ($orderNumber === '') {
            throw ValidationException::withMessages(['salesman_order_number' => 'Enter a valid order number.']);
        }

        $slip = $request->file('slip');
        $compressedSlip = $slip ? $this->compressSlip($slip) : null;
        $slipPath = null;
        $duplicateBlocked = false;

        try {
            return DB::transaction(function () use ($validated, $orderNumber, $compressedSlip, $user, &$slipPath, &$duplicateBlocked) {
                $orderHash = hash('sha256', $orderNumber);
                DB::table('receipt_order_guards')->insertOrIgnore([
                    'order_number_hash' => $orderHash,
                    'created_at' => now(),
                ]);
                DB::table('receipt_order_guards')->where('order_number_hash', $orderHash)->lockForUpdate()->first();

                $duplicate = Receipt::query()
                    ->where('salesman_order_number', $orderNumber)
                    ->whereIn('status', ['pending', 'approved'])
                    ->lockForUpdate()
                    ->exists();

                if ($duplicate) {
                    $duplicateBlocked = true;
                    throw ValidationException::withMessages([
                        'salesman_order_number' => 'This order number was already submitted. If you think this is a mistake, contact support.',
                    ]);
                }

                $slipPath = $compressedSlip !== null ? $this->storeCompressedSlip($compressedSlip) : null;
                $receipt = Receipt::create([
                    'user_id' => $user->id,
                    'salesman_order_number' => $orderNumber,
                    'status' => 'pending',
                    'submitted_at' => now(),
                    'order_date' => $validated['order_date'],
                    'slip_path' => $slipPath,
                    'slip_hash' => $compressedSlip !== null ? hash('sha256', $compressedSlip) : null,
                    'customer_note' => $validated['customer_note'] ?? null,
                ]);

                foreach ($validated['items'] as $item) {
                    $inventory = Inventory::where('name_key', Inventory::nameKey($item['product_name']))->lockForUpdate()->first();
                    if (! $inventory) {
                        throw ValidationException::withMessages(['items' => 'Choose a product from the current catalog.']);
                    }
                    $receipt->items()->create([
                        'product_name' => $inventory->name,
                        'inventory_id' => $inventory->id,
                        'quantity' => (int) $item['quantity'],
                        'unit_price' => $item['unit_price'] ?? 0,
                    ]);
                }

                $this->activityLogger->record('receipt.submitted', 'Receipt '.$orderNumber.' submitted for review.', $receipt, [
                    'order_number' => $orderNumber,
                    'order_date' => $validated['order_date'],
                    'item_lines' => count($validated['items']),
                    'slip_hash' => $receipt->slip_hash,
                ]);

                return $receipt->load('items');
            });
        } catch (Throwable $exception) {
            if ($duplicateBlocked) {
                $this->activityLogger->record('receipt.duplicate_blocked', 'Duplicate order submission blocked.', null, ['order_number_hash' => hash('sha256', $orderNumber)]);
            }
            if ($slipPath) {
                Storage::disk('private')->delete($slipPath);
            }
            throw $exception;
        }
    }

    private function compressSlip(UploadedFile $slip): string
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagejpeg')) {
            throw new RuntimeException('GD is required to process receipt photos.');
        }

        $sourceBytes = file_get_contents($slip->getRealPath());
        $dimensions = $sourceBytes === false ? false : @getimagesizefromstring($sourceBytes);
        if (! $dimensions || $dimensions[0] < 1 || $dimensions[1] < 1 || $dimensions[0] * $dimensions[1] > 40_000_000) {
            throw ValidationException::withMessages(['slip' => 'Choose a valid receipt photo smaller than 40 megapixels.']);
        }
        $memoryLimit = ini_parse_quantity(ini_get('memory_limit'));
        if ($memoryLimit > 0 && $memoryLimit - memory_get_usage(true) < $dimensions[0] * $dimensions[1] * 8 + strlen($sourceBytes) * 2 + 16777216) {
            throw ValidationException::withMessages(['slip' => 'This photo is too large to process. Choose a smaller image.']);
        }
        $image = $sourceBytes === false ? false : @imagecreatefromstring($sourceBytes);
        unset($sourceBytes);

        if ($image === false) {
            Log::warning('GD could not decode a validated receipt image.', ['width' => $dimensions[0], 'height' => $dimensions[1]]);
            throw ValidationException::withMessages(['slip' => 'Upload a valid receipt image.']);
        }

        $image = $this->orientImage($image, $slip->getRealPath());
        $sourceWidth = imagesx($image);
        $sourceHeight = imagesy($image);
        $scale = min(1, self::MAX_SLIP_EDGE / max($sourceWidth, $sourceHeight));
        $width = max(1, (int) round($sourceWidth * $scale));
        $height = max(1, (int) round($sourceHeight * $scale));
        $canvas = imagecreatetruecolor($width, $height);

        if ($canvas === false) {
            imagedestroy($image);
            throw new RuntimeException('Unable to prepare the receipt photo for storage.');
        }

        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);
        imagedestroy($image);

        $compressedBytes = null;
        while ($compressedBytes === null) {
            for ($quality = 75; $quality >= 40; $quality -= 5) {
                ob_start();
                $encoded = imagejpeg($canvas, null, $quality);
                $jpegBytes = ob_get_clean();

                if (! $encoded || $jpegBytes === false) {
                    imagedestroy($canvas);
                    throw new RuntimeException('Unable to encode the receipt photo.');
                }

                if (strlen($jpegBytes) < self::MAX_COMPRESSED_SLIP_BYTES) {
                    $compressedBytes = $jpegBytes;
                    break;
                }
            }

            if ($compressedBytes !== null) {
                break;
            }

            $nextWidth = max(1, (int) floor($width * .9));
            $nextHeight = max(1, (int) floor($height * .9));
            $smallerCanvas = imagecreatetruecolor($nextWidth, $nextHeight);

            if ($smallerCanvas === false) {
                imagedestroy($canvas);
                throw new RuntimeException('Unable to prepare the receipt photo for storage.');
            }

            $white = imagecolorallocate($smallerCanvas, 255, 255, 255);
            imagefill($smallerCanvas, 0, 0, $white);
            imagecopyresampled($smallerCanvas, $canvas, 0, 0, 0, 0, $nextWidth, $nextHeight, $width, $height);
            imagedestroy($canvas);
            $canvas = $smallerCanvas;
            $width = $nextWidth;
            $height = $nextHeight;
        }

        imagedestroy($canvas);

        return $compressedBytes;
    }

    private function orientImage($image, string $path)
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $metadata = @exif_read_data($path, 'IFD0', true);
        $orientation = (int) ($metadata['IFD0']['Orientation'] ?? 1);

        if (in_array($orientation, [2, 4, 5, 7], true)) {
            imageflip($image, $orientation === 4 ? IMG_FLIP_VERTICAL : IMG_FLIP_HORIZONTAL);
        }

        $angle = match ($orientation) {
            3, 4 => 180,
            5, 6 => -90,
            7, 8 => 90,
            default => 0,
        };

        if ($angle !== 0) {
            $rotated = imagerotate($image, $angle, 0);
            if ($rotated !== false) {
                imagedestroy($image);

                return $rotated;
            }
        }

        return $image;
    }

    private function storeCompressedSlip(string $bytes): string
    {
        $path = 'receipt-slips/'.Str::uuid().'.jpg';
        try {
            if (! Storage::disk('private')->put($path, $bytes)) {
                throw new RuntimeException('Receipt write failed.');
            }
        } catch (Throwable $exception) {
            Log::warning('Receipt storage unavailable.', ['exception_type' => get_class($exception)]);
            throw ValidationException::withMessages(['slip' => 'Receipt storage is temporarily unavailable. Please try again.']);
        }

        return $path;
    }

    public function normalizeOrderNumber(string $orderNumber): string
    {
        return mb_strtoupper((string) preg_replace('/\s+/u', '', trim($orderNumber)));
    }
}
