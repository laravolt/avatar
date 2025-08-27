<?php

namespace Tests;

use Laravolt\Avatar\Avatar;
use Laravolt\Avatar\Concerns\StorageOptimization;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Mockery;

class StorageOptimizationTest extends TestCase
{
    protected $avatar;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Laravel facades
        $this->mockStorage();
        $this->mockCache();

        // Create a test avatar class with StorageOptimization trait
        $this->avatar = new class extends Avatar {
            use StorageOptimization;

            public function __construct()
            {
                $config = [
                    'width' => 256,
                    'height' => 256,
                    'fontSize' => 96,
                    'driver' => 'gd',
                ];
                parent::__construct($config);

                $this->storageDisk = 'local';
                $this->storageDirectory = 'test-avatars';
                $this->maxStorageSize = 100; // 100MB for testing
                $this->maxFileAge = 7; // 7 days for testing
            }
        };
    }

    protected function mockStorage()
    {
        $storageMock = Mockery::mock();
        $storageMock->shouldReceive('disk')->andReturnSelf();
        $storageMock->shouldReceive('exists')->andReturn(true);
        $storageMock->shouldReceive('makeDirectory')->andReturn(true);
        $storageMock->shouldReceive('path')->andReturn('/tmp/test-path');
        $storageMock->shouldReceive('allFiles')->andReturn([]);
        $storageMock->shouldReceive('size')->andReturn(1024);
        $storageMock->shouldReceive('lastModified')->andReturn(time());
        $storageMock->shouldReceive('delete')->andReturn(true);
        $storageMock->shouldReceive('url')->andReturn('http://example.com/test');
        $storageMock->shouldReceive('get')->andReturn('mock file content');
        $storageMock->shouldReceive('put')->andReturn(true);

        Storage::swap($storageMock);
    }

    protected function mockCache()
    {
        $cacheMock = Mockery::mock();
        $cacheMock->shouldReceive('get')->andReturn(null);
        $cacheMock->shouldReceive('put')->andReturn(true);
        $cacheMock->shouldReceive('forever')->andReturn(true);

        Cache::swap($cacheMock);
    }

    public function testStorageConfiguration()
    {
        $this->assertEquals('local', $this->avatar->getStorageDisk());
        $this->assertEquals('test-avatars', $this->avatar->getStorageDirectory());
        $this->assertEquals(100, $this->avatar->getMaxStorageSize());
        $this->assertEquals(7, $this->avatar->getMaxFileAge());
    }

    public function testConfigureStorage()
    {
        $result = $this->avatar->configureStorage('s3', 'avatars', 500);

        $this->assertInstanceOf(get_class($this->avatar), $result);
        $this->assertEquals('s3', $this->avatar->getStorageDisk());
        $this->assertEquals('avatars', $this->avatar->getStorageDirectory());
        $this->assertEquals(500, $this->avatar->getMaxStorageSize());
    }

    public function testSetCompressionEnabled()
    {
        $result = $this->avatar->setCompressionEnabled(false);

        $this->assertInstanceOf(get_class($this->avatar), $result);
        $this->assertFalse($this->avatar->getCompressionEnabled());

        $this->avatar->setCompressionEnabled(true);
        $this->assertTrue($this->avatar->getCompressionEnabled());
    }

    public function testSetMaxFileAge()
    {
        $result = $this->avatar->setMaxFileAge(30);

        $this->assertInstanceOf(get_class($this->avatar), $result);
        $this->assertEquals(30, $this->avatar->getMaxFileAge());
    }

    public function testGenerateOptimizedFilename()
    {
        $this->avatar->create('John Doe');

        $reflection = new \ReflectionClass($this->avatar);
        $method = $reflection->getMethod('generateOptimizedFilename');
        $method->setAccessible(true);

        $filename = $method->invoke($this->avatar, 'John Doe', 'png');

        $this->assertStringContainsString('John_Doe', $filename);
        $this->assertStringEndsWith('.png', $filename);
        $this->assertStringContainsString(date('Y-m-d'), $filename);
    }

    public function testGenerateCacheKey()
    {
        $reflection = new \ReflectionClass($this->avatar);
        $method = $reflection->getMethod('generateCacheKey');
        $method->setAccessible(true);

        $key1 = $method->invoke($this->avatar, 'John Doe', 'png', []);
        $key2 = $method->invoke($this->avatar, 'John Doe', 'png', []);
        $key3 = $method->invoke($this->avatar, 'Jane Smith', 'png', []);

        // Same parameters should generate same key
        $this->assertEquals($key1, $key2);

        // Different parameters should generate different key
        $this->assertNotEquals($key1, $key3);

        // Key should start with avatar_url_
        $this->assertStringStartsWith('avatar_url_', $key1);
    }

    public function testGetMetricsCacheKey()
    {
        $reflection = new \ReflectionClass($this->avatar);
        $method = $reflection->getMethod('getMetricsCacheKey');
        $method->setAccessible(true);

        $key = $method->invoke($this->avatar);

        $this->assertEquals('avatar_storage_metrics', $key);
    }

    public function testLoadStorageMetrics()
    {
        // Mock empty metrics initially
        $reflection = new \ReflectionClass($this->avatar);
        $method = $reflection->getMethod('loadStorageMetrics');
        $method->setAccessible(true);

        $method->invoke($this->avatar);

        $this->assertIsArray($this->avatar->getStorageMetrics());
        $this->assertArrayHasKey('total_files', $this->avatar->getStorageMetrics());
        $this->assertArrayHasKey('total_size', $this->avatar->getStorageMetrics());
        $this->assertArrayHasKey('formats', $this->avatar->getStorageMetrics());
        $this->assertArrayHasKey('last_updated', $this->avatar->getStorageMetrics());
    }

    public function testGetStorageStatistics()
    {
        $stats = $this->avatar->getStorageStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_files', $stats);
        $this->assertArrayHasKey('total_size_bytes', $stats);
        $this->assertArrayHasKey('total_size_mb', $stats);
        $this->assertArrayHasKey('formats', $stats);
        $this->assertArrayHasKey('storage_limit_mb', $stats);
        $this->assertArrayHasKey('usage_percentage', $stats);
        $this->assertArrayHasKey('last_updated', $stats);
        $this->assertArrayHasKey('disk', $stats);
        $this->assertArrayHasKey('directory', $stats);

        $this->assertEquals('local', $stats['disk']);
        $this->assertEquals('test-avatars', $stats['directory']);
        $this->assertEquals(100, $stats['storage_limit_mb']);
    }

    public function testApplyCompressionPNG()
    {
        $this->avatar->setDimension(1024, 1024); // Large size to trigger compression

        $reflection = new \ReflectionClass($this->avatar);
        $method = $reflection->getMethod('applyCompression');
        $method->setAccessible(true);

        // This would normally affect the image, but we're just testing the method exists
        $method->invoke($this->avatar, 'png', []);

        // Test with preserve_quality option
        $method->invoke($this->avatar, 'png', ['preserve_quality' => true]);

        $this->assertTrue(true); // Test passes if no exceptions thrown
    }

    public function testApplyCompressionJPEG()
    {
        $reflection = new \ReflectionClass($this->avatar);
        $method = $reflection->getMethod('applyCompression');
        $method->setAccessible(true);

        $method->invoke($this->avatar, 'jpg', ['progressive' => true]);
        $method->invoke($this->avatar, 'jpeg', ['progressive' => false]);

        $this->assertTrue(true); // Test passes if no exceptions thrown
    }

    public function testApplyCompressionWebP()
    {
        $reflection = new \ReflectionClass($this->avatar);
        $method = $reflection->getMethod('applyCompression');
        $method->setAccessible(true);

        $method->invoke($this->avatar, 'webp', []);

        $this->assertTrue(true); // Test passes if no exceptions thrown
    }

    public function testCompressionDisabled()
    {
        $this->avatar->setCompressionEnabled(false);

        $reflection = new \ReflectionClass($this->avatar);
        $method = $reflection->getMethod('applyCompression');
        $method->setAccessible(true);

        // Should return early when compression is disabled
        $method->invoke($this->avatar, 'png', []);

        $this->assertTrue(true); // Test passes if no exceptions thrown
    }

    public function testGetFilesSortedBySize()
    {
        // This test would require actual files in a real Laravel environment
        $this->markTestIncomplete('Requires actual file system setup');
    }

    public function testCleanupOldFiles()
    {
        // This test would require actual files in a real Laravel environment
        $this->markTestIncomplete('Requires actual file system setup');
    }

    public function testPerformCleanup()
    {
        $result = $this->avatar->performCleanup();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('old_files', $result);
        $this->assertArrayHasKey('large_files', $result);
        $this->assertArrayHasKey('duplicate_files', $result);
    }

    public function testLogBatchMetrics()
    {
        $reflection = new \ReflectionClass($this->avatar);
        $method = $reflection->getMethod('logBatchMetrics');
        $method->setAccessible(true);

        // Should not throw exceptions
        $method->invoke($this->avatar, 10, 5.5, 'png');

        $this->assertTrue(true);
    }

    public function testUpdateStorageMetrics()
    {
        $reflection = new \ReflectionClass($this->avatar);
        $method = $reflection->getMethod('updateStorageMetrics');
        $method->setAccessible(true);

        $initialFiles = $this->avatar->getStorageMetrics()['total_files'] ?? 0;

        // Mock a file update
        $method->invoke($this->avatar, 'test/path.png', 'png');

        $this->assertEquals($initialFiles + 1, $this->avatar->getStorageMetrics()['total_files']);
        $this->assertArrayHasKey('png', $this->avatar->getStorageMetrics()['formats']);
        $this->assertNotNull($this->avatar->getStorageMetrics()['last_updated']);
    }

    /**
     * Test trait integration
     */
    public function testTraitIntegration()
    {
        $this->assertTrue(method_exists($this->avatar, 'storeOptimized'));
        $this->assertTrue(method_exists($this->avatar, 'getCachedOrGenerate'));
        $this->assertTrue(method_exists($this->avatar, 'batchStoreOptimized'));
        $this->assertTrue(method_exists($this->avatar, 'performCleanup'));
        $this->assertTrue(method_exists($this->avatar, 'getStorageStatistics'));
        $this->assertTrue(method_exists($this->avatar, 'configureStorage'));
    }

    /**
     * Test storage limits validation
     */
    public function testStorageLimitsValidation()
    {
        $reflection = new \ReflectionClass($this->avatar);
        $method = $reflection->getMethod('checkStorageLimits');
        $method->setAccessible(true);

        // Should not throw exceptions even if cleanup is triggered
        $method->invoke($this->avatar);

        $this->assertTrue(true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}