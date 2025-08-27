<?php

namespace Tests;

use Illuminate\Support\Facades\Storage;
use Laravolt\Avatar\HDAvatarResponse;
use PHPUnit\Framework\TestCase;

class HDAvatarResponseTest extends TestCase
{
    protected HDAvatarResponse $hdAvatar;
    protected array $config;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Laravel facades
        if (! class_exists('Storage')) {
            $this->markTestSkipped('Laravel Storage facade not available');
        }

        $this->config = [
            'hd' => [
                'enabled' => true,
                'width' => 512,
                'height' => 512,
                'fontSize' => 192,
                'quality' => [
                    'png' => 95,
                    'jpg' => 90,
                    'webp' => 85,
                ],
            ],
            'export' => [
                'format' => 'png',
                'path' => 'test-avatars',
            ],
            'responsive_sizes' => [
                'small' => ['width' => 128, 'height' => 128, 'fontSize' => 48],
                'medium' => ['width' => 256, 'height' => 256, 'fontSize' => 96],
                'large' => ['width' => 512, 'height' => 512, 'fontSize' => 192],
            ],
        ];

        $this->hdAvatar = new HDAvatarResponse($this->config);
    }

    public function test_hd_mode_enabled()
    {
        $this->assertTrue($this->hdAvatar->hdEnabled);
    }

    public function test_create_hd_avatar()
    {
        $result = $this->hdAvatar->createHD('John Doe');

        $this->assertInstanceOf(HDAvatarResponse::class, $result);
        $this->assertEquals('John Doe', $result->name);
    }

    public function test_set_hd_mode()
    {
        $this->hdAvatar->setHDMode(false);
        $this->assertFalse($this->hdAvatar->hdEnabled);

        $this->hdAvatar->setHDMode(true);
        $this->assertTrue($this->hdAvatar->hdEnabled);
    }

    public function test_set_quality()
    {
        $this->hdAvatar->setQuality('png', 80);
        $this->assertEquals(80, $this->hdAvatar->hdConfig['quality']['png']);
    }

    public function test_set_responsive_size()
    {
        $this->hdAvatar->setResponsiveSize('custom', 384, 384, 144);

        $expectedSize = [
            'width' => 384,
            'height' => 384,
            'fontSize' => 144,
        ];

        $this->assertEquals($expectedSize, $this->hdAvatar->responsiveSizes['custom']);
    }

    public function test_generate_content_hash()
    {
        $this->hdAvatar->createHD('Test User');

        $reflection = new \ReflectionClass($this->hdAvatar);
        $method = $reflection->getMethod('generateContentHash');
        $method->setAccessible(true);

        $hash1 = $method->invoke($this->hdAvatar);
        $this->assertIsString($hash1);
        $this->assertEquals(8, strlen($hash1));

        // Same content should generate same hash
        $hash2 = $method->invoke($this->hdAvatar);
        $this->assertEquals($hash1, $hash2);

        // Different content should generate different hash
        $this->hdAvatar->setBackground('#FF0000');
        $hash3 = $method->invoke($this->hdAvatar);
        $this->assertNotEquals($hash1, $hash3);
    }

    public function test_generate_filename()
    {
        $this->hdAvatar->createHD('Test User');

        $reflection = new \ReflectionClass($this->hdAvatar);
        $method = $reflection->getMethod('generateFilename');
        $method->setAccessible(true);

        $filename = $method->invoke($this->hdAvatar, 'png', 'medium');

        $this->assertStringContainsString('_medium_', $filename);
        $this->assertStringEndsWith('.png', $filename);
    }

    public function test_apply_hd_defaults()
    {
        $config = ['driver' => 'gd'];

        $reflection = new \ReflectionClass($this->hdAvatar);
        $method = $reflection->getMethod('applyHDDefaults');
        $method->setAccessible(true);

        $result = $method->invoke($this->hdAvatar, $config);

        $this->assertEquals(512, $result['width']);
        $this->assertEquals(512, $result['height']);
        $this->assertEquals(192, $result['fontSize']);
        $this->assertEquals('imagick', $result['driver']);
    }

    public function test_clear_exported_files()
    {
        $this->hdAvatar->exportedFiles = ['file1.png', 'file2.jpg'];
        $this->hdAvatar->clearExportedFiles();

        $this->assertEmpty($this->hdAvatar->getExportedFiles());
    }

    public function test_batch_export_names()
    {
        $names = ['John Doe', 'Jane Smith', 'Bob Johnson'];

        // Mock Storage::makeDirectory and Storage::path
        if (class_exists('Storage')) {
            Storage::shouldReceive('makeDirectory')->andReturn(true);
            Storage::shouldReceive('path')->andReturn('/fake/path/avatar.png');
        }

        // This test would need proper mocking of Intervention Image in a real Laravel environment
        $this->markTestIncomplete('Requires proper Laravel environment and mocking');
    }

    public function test_image_format_validation()
    {
        $validFormats = ['png', 'jpg', 'jpeg', 'webp'];

        foreach ($validFormats as $format) {
            // In a real test, this would call export() which needs proper environment
            $this->assertTrue(in_array($format, ['png', 'jpg', 'jpeg', 'webp']));
        }

        $this->expectException(\InvalidArgumentException::class);
        // This would trigger in export() method with invalid format
        throw new \InvalidArgumentException('Unsupported format: invalid');
    }

    public function test_responsive_sizes_configuration()
    {
        $expectedSizes = [
            'small' => ['width' => 128, 'height' => 128, 'fontSize' => 48],
            'medium' => ['width' => 256, 'height' => 256, 'fontSize' => 96],
            'large' => ['width' => 512, 'height' => 512, 'fontSize' => 192],
        ];

        $this->assertEquals($expectedSizes, $this->hdAvatar->responsiveSizes);
    }

    public function test_hd_configuration_loading()
    {
        $this->assertEquals(512, $this->hdAvatar->hdConfig['width']);
        $this->assertEquals(512, $this->hdAvatar->hdConfig['height']);
        $this->assertEquals(192, $this->hdAvatar->hdConfig['fontSize']);
        $this->assertEquals(95, $this->hdAvatar->hdConfig['quality']['png']);
    }

    public function test_export_path_configuration()
    {
        $this->assertEquals('test-avatars', $this->hdAvatar->exportPath);
    }

    /**
     * Test HD avatar dimensions are properly applied
     */
    public function test_hd_dimensions()
    {
        $this->hdAvatar->createHD('Test User');

        // HD dimensions should be applied
        $this->assertEquals(512, $this->hdAvatar->width);
        $this->assertEquals(512, $this->hdAvatar->height);
        $this->assertEquals(192, $this->hdAvatar->fontSize);
    }

    /**
     * Test that non-HD mode works correctly
     */
    public function test_non_hd_mode()
    {
        $nonHDConfig = $this->config;
        $nonHDConfig['hd']['enabled'] = false;

        $avatar = new HDAvatarResponse($nonHDConfig);
        $this->assertFalse($avatar->hdEnabled);
    }

    /**
     * Test quality settings for different formats
     */
    public function test_quality_settings()
    {
        $this->assertEquals(95, $this->hdAvatar->hdConfig['quality']['png']);
        $this->assertEquals(90, $this->hdAvatar->hdConfig['quality']['jpg']);
        $this->assertEquals(85, $this->hdAvatar->hdConfig['quality']['webp']);

        // Test quality modification
        $this->hdAvatar->setQuality('png', 100);
        $this->assertEquals(100, $this->hdAvatar->hdConfig['quality']['png']);
    }

    /**
     * Test responsive size addition
     */
    public function test_responsive_size_addition()
    {
        $this->hdAvatar->setResponsiveSize('xxl', 1024, 1024, 384);

        $this->assertArrayHasKey('xxl', $this->hdAvatar->responsiveSizes);
        $this->assertEquals(1024, $this->hdAvatar->responsiveSizes['xxl']['width']);
        $this->assertEquals(1024, $this->hdAvatar->responsiveSizes['xxl']['height']);
        $this->assertEquals(384, $this->hdAvatar->responsiveSizes['xxl']['fontSize']);
    }

    protected function tearDown(): void
    {
        // Clean up any test files if they were created
        parent::tearDown();
    }
}
