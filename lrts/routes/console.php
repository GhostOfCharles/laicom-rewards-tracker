<?php

use App\Models\EarnedReward;
use App\Models\PremiumProduct;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ClaimService;
use App\Services\PremiumStockLedger;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('lrts:expire-rewards', function (ClaimService $claims, PremiumStockLedger $ledger) {
    $expiredClaims = 0;
    EarnedReward::query()->whereIn('claim_status', ['unclaimed', 'claim_requested'])
        ->whereNotNull('expires_at')->where('expires_at', '<=', now())
        ->orderBy('id')->chunkById(100, function ($rewards) use ($claims, &$expiredClaims) {
            foreach ($rewards as $reward) {
                if ($claims->expire($reward)) {
                    $expiredClaims++;
                }
            }
        });
    EarnedReward::query()->whereIn('claim_status', ['unclaimed', 'claim_requested'])
        ->whereHas('premiumProduct', fn ($query) => $query->where('is_perishable', true)->whereDate('expiry_date', '<', today()))
        ->orderBy('id')->chunkById(100, function ($rewards) use ($claims, &$expiredClaims) {
            foreach ($rewards as $reward) {
                if ($claims->expireForProduct($reward)) {
                    $expiredClaims++;
                }
            }
        });

    $expiredStock = 0;
    PremiumProduct::query()->where('is_perishable', true)->whereDate('expiry_date', '<', today())
        ->where('stock', '>', 0)->orderBy('id')->chunkById(100, function ($products) use ($ledger, &$expiredStock) {
            foreach ($products as $product) {
                $quantity = (int) $product->stock;
                $ledger->move($product, -$quantity, 'expired_writeoff', $product, 'On-hand stock written off after product expiry.');
                $expiredStock += $quantity;
            }
        });

    $this->info("Expired {$expiredClaims} reward claim(s) and wrote off {$expiredStock} expired premium item(s).");
})->purpose('Expire reward claim windows and write off expired premium stock');

Schedule::command('lrts:expire-rewards')->dailyAt('00:10');

Artisan::command('lrts:make-admin {email} {name}', function () {
    $data = ['email' => $this->argument('email'), 'name' => $this->argument('name'), 'password' => $this->secret('Strong password (not displayed)')];
    $data['password_confirmation'] = $this->secret('Confirm password');
    validator($data, [
        'email' => 'required|email|unique:users,email', 'name' => 'required|string|max:255',
        'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
    ])->validate();
    $user = User::create(['email' => $data['email'], 'name' => $data['name'], 'password' => Hash::make($data['password']), 'role' => 'admin']);
    app(ActivityLogger::class)->recordAs($user, 'auth.admin_created', 'Administrator created through console.', $user);
    $this->info('Administrator created.');
})->purpose('Create an administrator without seeded credentials');

Artisan::command('lrts:migrate-uploads {--from=legacy-private} {--to=private} {--dry-run}', function () {
    if ($this->option('from') === $this->option('to')) {
        $this->error('Source and target disks must differ.');

        return 1;
    }
    $source = Storage::disk($this->option('from'));
    $target = Storage::disk($this->option('to'));
    $copied = 0;
    foreach ($source->allFiles() as $path) {
        if (str_starts_with(basename($path), '.') || $target->exists($path)) {
            continue;
        }
        if (! $this->option('dry-run')) {
            $stream = $source->readStream($path);
            try {
                if (! $target->writeStream($path, $stream)) {
                    throw new RuntimeException('Upload copy failed.');
                }
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }
        $copied++;
    }
    $this->info($copied.' upload(s) copied or eligible. Source files retained.');
})->purpose('Copy uploads between disks, retain originals, skip existing destination keys');
