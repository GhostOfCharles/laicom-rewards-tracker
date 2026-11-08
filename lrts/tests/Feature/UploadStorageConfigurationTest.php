<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadStorageConfigurationTest extends TestCase
{
    public function test_production_local_storage_fails_verification(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['filesystems.disks.private.driver' => 'local']);
        $this->artisan('lrts:check-upload-storage')->assertExitCode(1);
    }

    public function test_probe_round_trip_leaves_no_files(): void
    {
        Storage::fake('private');
        $this->artisan('lrts:check-upload-storage --probe')->assertExitCode(0);
        $this->assertSame([], Storage::disk('private')->allFiles());
    }

    public function test_cloud_disk_upload_failures_are_not_silently_ignored(): void
    {
        config(['filesystems.disks.private.throw' => false]);
        (new AppServiceProvider($this->app))->boot();
        $this->assertTrue(config('filesystems.disks.private.throw'));
    }
}
