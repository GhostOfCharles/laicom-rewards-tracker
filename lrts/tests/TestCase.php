<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;

abstract class TestCase extends BaseTestCase
{
    protected function fakePng(string $name): UploadedFile
    {
        return UploadedFile::fake()->image($name, 20, 20);
    }
}
