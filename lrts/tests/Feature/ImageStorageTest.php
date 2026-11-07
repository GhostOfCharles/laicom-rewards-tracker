<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageStorageTest extends TestCase
{
    use DatabaseMigrations;

    public function test_product_upload_is_bounded_and_replacements_and_failures_are_cleaned_up(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('admin.inventory.store'), ['name' => 'Image product', 'category' => 'home_care', 'stock_balance' => 5, 'image' => UploadedFile::fake()->image('product.png', 1200, 800)])->assertSessionHasNoErrors();
        $item = Inventory::sole();
        $old = $item->image_path;
        $size = getimagesize(Storage::disk('public')->path($old));
        $this->assertSame(400, $size[0]);
        $this->put(route('admin.inventory.update', $item), ['name' => $item->name, 'category' => 'home_care', 'stock_balance' => 5, 'expected_stock' => 5, 'image' => $this->fakePng('replacement.png')])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($old);
        $current = $item->fresh()->image_path;
        $this->put(route('admin.inventory.update', $item), ['name' => $item->name, 'category' => 'home_care', 'stock_balance' => 6, 'expected_stock' => 99, 'image' => $this->fakePng('stale.png')])->assertSessionHasErrors('stock_balance');
        $this->assertCount(1, Storage::disk('public')->allFiles());
        $this->delete(route('admin.inventory.destroy', $item))->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($current);
    }
}
