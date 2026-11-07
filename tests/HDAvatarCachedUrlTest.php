<?php

namespace Tests;

use Illuminate\Support\Facades\Storage;
use Laravolt\Avatar\HDAvatarResponse;
use Mockery;
use PHPUnit\Framework\TestCase;

class HDAvatarCachedUrlTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_cached_url_reuses_the_file_it_exported()
    {
        $existing = [];
        $storage = Mockery::mock();
        $storage->shouldReceive('exists')->andReturnUsing(function (string $path) use (&$existing) {
            return in_array($path, $existing, true);
        });
        $storage->shouldReceive('url')->andReturnUsing(fn (string $path) => "https://cdn.test/{$path}");
        Storage::swap($storage);

        $avatar = new class(['driver' => 'gd', 'responsive_sizes' => ['small' => ['width' => 64, 'height' => 64, 'fontSize' => 24]]]) extends HDAvatarResponse {
            public array $exports = [];

            protected function exportTo(string $filepath, string $format, int $quality = 95): string
            {
                $this->exports[] = $filepath;

                return $filepath;
            }
        };
        $avatar->createHD('Jane Doe');

        $first = $avatar->getCachedUrl('png', 'small');
        $existing = $avatar->exports;
        $second = $avatar->getCachedUrl('png', 'small');

        $this->assertCount(1, $avatar->exports, 'the second call must reuse the stored file');
        $this->assertSame($first, $second);
        $this->assertSame("https://cdn.test/{$avatar->exports[0]}", $first);
        $this->assertStringEndsWith('_small.png', $avatar->exports[0]);
    }
}
