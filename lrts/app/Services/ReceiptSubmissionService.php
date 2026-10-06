<?php

namespace App\Services;

use App\Models\Receipt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ReceiptSubmissionService
{
    public function __construct(private readonly ActivityLogger $activityLogger)
    {
    }

    public function createFromRequest(Request $request, User $user): Receipt
    {
        $minimumDate = today()->subDays((int) config('lrts.order_max_age_days', 60))->toDateString();
        $slipLimit = (int) config('lrts.slip_max_kb', 5120);

        $validated = $request->validate([
            'salesman_order_number' => ['required', 'string', 'max:100'],
            'order_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:' . $minimumDate],
            'slip' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:' . $slipLimit],
            'customer_note' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_name' => ['required', 'string', 'exists:inventories,name'],
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
        $slipPath = null;

        try {
            return DB::transaction(function () use ($validated, $orderNumber, $slip, $user, &$slipPath) {
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
                    throw ValidationException::withMessages([
                        'salesman_order_number' => 'This order number was already submitted. If you think this is a mistake, contact support.',
                    ]);
                }

                $slipPath = $slip->store('receipt-slips', 'private');
                $receipt = Receipt::create([
                    'user_id' => $user->id,
                    'salesman_order_number' => $orderNumber,
                    'status' => 'pending',
                    'submitted_at' => now(),
                    'order_date' => $validated['order_date'],
                    'slip_path' => $slipPath,
                    'slip_hash' => hash_file('sha256', $slip->getRealPath()),
                    'customer_note' => $validated['customer_note'] ?? null,
                ]);

                foreach ($validated['items'] as $item) {
                    $receipt->items()->create([
                        'product_name' => $item['product_name'],
                        'quantity' => (int) $item['quantity'],
                        'unit_price' => $item['unit_price'] ?? 0,
                    ]);
                }

                $this->activityLogger->record('receipt.submitted', 'Receipt ' . $orderNumber . ' submitted for review.', $receipt, [
                    'order_number' => $orderNumber,
                    'order_date' => $validated['order_date'],
                    'item_lines' => count($validated['items']),
                    'slip_hash' => $receipt->slip_hash,
                ]);

                return $receipt->load('items');
            });
        } catch (Throwable $exception) {
            if ($slipPath) {
                Storage::disk('private')->delete($slipPath);
            }
            throw $exception;
        }
    }

    public function normalizeOrderNumber(string $orderNumber): string
    {
        return mb_strtoupper((string) preg_replace('/\s+/u', '', trim($orderNumber)));
    }
}
