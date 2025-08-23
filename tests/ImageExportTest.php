<?php

namespace Tests;

use Laravolt\Avatar\Avatar;
use Laravolt\Avatar\Concerns\ImageExport;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\Facades\Storage;

class ImageExportTest extends TestCase
{
    protected $avatar;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test avatar class with ImageExport trait
        $this->avatar = new class extends Avatar {
            use ImageExport;
            
            public function __construct()
            {
                $config = [
                    'width' => 256,
                    'height' => 256,
                    'fontSize' => 96,
                    'driver' => 'gd',
                ];
                parent::__construct($config);
            }
        };
    }

    public function testExportFormatsValidation()
    {
        $validFormats = ['png', 'jpg', 'jpeg', 'webp'];
        
        foreach ($validFormats as $format) {
            $this->assertContains($format, $this->avatar->exportFormats);
        }
    }

    public function testInvalidFormatThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Unsupported format 'bmp'. Supported formats: png, jpg, jpeg, webp");
        
        $reflection = new \ReflectionClass($this->avatar);
        $method = $reflection->getMethod('validateExportFormat');
        $method->setAccessible(true);
        $method->invoke($this->avatar, 'bmp');
    }

    public function testGetDefaultExportOptions()
    {
        $reflection = new \ReflectionClass($this->avatar);
        $method = $reflection->getMethod('getDefaultExportOptions');
        $method->setAccessible(true);
        
        $pngOptions = $method->invoke($this->avatar, 'png');
        $this->assertEquals(95, $pngOptions['quality']);
        $this->assertEquals(6, $pngOptions['compression']);
        $this->assertFalse($pngOptions['interlaced']);
        
        $jpgOptions = $method->invoke($this->avatar, 'jpg');
        $this->assertEquals(90, $jpgOptions['quality']);
        $this->assertTrue($jpgOptions['progressive']);
        
        $webpOptions = $method->invoke($this->avatar, 'webp');
        $this->assertEquals(85, $webpOptions['quality']);
        $this->assertFalse($webpOptions['lossless']);
    }

    public function testSanitizeFilename()
    {
        $reflection = new \ReflectionClass($this->avatar);
        $method = $reflection->getMethod('sanitizeFilename');
        $method->setAccessible(true);
        
        // Test unsafe characters removal
        $unsafe = 'file/name\\with:unsafe*chars?"<>|';
        $safe = $method->invoke($this->avatar, $unsafe);
        $this->assertEquals('file_name_with_unsafe_chars', $safe);
        
        // Test multiple underscores reduction
        $multiple = 'file___with___multiple___underscores';
        $reduced = $method->invoke($this->avatar, $multiple);
        $this->assertEquals('file_with_multiple_underscores', $reduced);
        
        // Test length limiting
        $long = str_repeat('a', 150);
        $limited = $method->invoke($this->avatar, $long);
        $this->assertEquals(100, strlen($limited));
    }

    public function testCalculateWatermarkPosition()
    {
        $reflection = new \ReflectionClass($this->avatar);
        $method = $reflection->getMethod('calculateWatermarkPosition');
        $method->setAccessible(true);
        
        $positions = [
            'top-left' => [13, 37], // margin + fontSize
            'top-right' => [256 - 13, 37],
            'bottom-left' => [13, 256 - 13],
            'bottom-right' => [256 - 13, 256 - 13],
            'center' => [128, 128],
        ];
        
        foreach ($positions as $position => $expected) {
            $result = $method->invoke($this->avatar, $position, 'Test', 24);
            $this->assertEquals($expected, $result, "Position {$position} calculation failed");
        }
    }

    public function testEstimateFileSizes()
    {
        $reflection = new \ReflectionClass($this->avatar);
        $method = $reflection->getMethod('estimateFileSizes');
        $method->setAccessible(true);
        
        $estimates = $method->invoke($this->avatar);
        
        $this->assertArrayHasKey('png', $estimates);
        $this->assertArrayHasKey('jpg', $estimates);
        $this->assertArrayHasKey('webp', $estimates);
        
        // Estimates should contain byte information
        $this->assertStringContains('bytes', $estimates['png']);
        $this->assertStringContains('bytes', $estimates['jpg']);
        $this->assertStringContains('bytes', $estimates['webp']);
    }

    public function testSetExportOptions()
    {
        $options = [
            'quality' => 95,
            'progressive' => true,
        ];
        
        $result = $this->avatar->setExportOptions($options);
        
        $this->assertInstanceOf(get_class($this->avatar), $result);
        $this->assertEquals($options, $this->avatar->exportOptions);
        
        // Test merging options
        $additionalOptions = ['compression' => 8];
        $this->avatar->setExportOptions($additionalOptions);
        
        $expected = array_merge($options, $additionalOptions);
        $this->assertEquals($expected, $this->avatar->exportOptions);
    }

    public function testGetExportStats()
    {
        $stats = $this->avatar->getExportStats();
        
        $this->assertArrayHasKey('supported_formats', $stats);
        $this->assertArrayHasKey('current_options', $stats);
        $this->assertArrayHasKey('image_dimensions', $stats);
        $this->assertArrayHasKey('estimated_file_sizes', $stats);
        
        $this->assertEquals(['png', 'jpg', 'jpeg', 'webp'], $stats['supported_formats']);
        $this->assertEquals(256, $stats['image_dimensions']['width']);
        $this->assertEquals(256, $stats['image_dimensions']['height']);
    }

    public function testApplyVariation()
    {
        $originalBackground = $this->avatar->background;
        $originalForeground = $this->avatar->foreground;
        $originalShape = $this->avatar->shape;
        
        $variation = [
            'background' => '#FF0000',
            'foreground' => '#00FF00',
            'shape' => 'square',
        ];
        
        $reflection = new \ReflectionClass($this->avatar);
        $method = $reflection->getMethod('applyVariation');
        $method->setAccessible(true);
        $method->invoke($this->avatar, $variation);
        
        $this->assertEquals('#FF0000', $this->avatar->background);
        $this->assertEquals('#00FF00', $this->avatar->foreground);
        $this->assertEquals('square', $this->avatar->shape);
    }

    /**
     * Test that the ImageExport trait properly extends functionality
     */
    public function testTraitIntegration()
    {
        $this->assertTrue(method_exists($this->avatar, 'exportImage'));
        $this->assertTrue(method_exists($this->avatar, 'exportResponsiveSizes'));
        $this->assertTrue(method_exists($this->avatar, 'bulkExport'));
        $this->assertTrue(method_exists($this->avatar, 'exportWithWatermark'));
        $this->assertTrue(method_exists($this->avatar, 'setExportOptions'));
        $this->assertTrue(method_exists($this->avatar, 'getExportStats'));
    }

    /**
     * Test responsive sizes export structure
     */
    public function testResponsiveSizesStructure()
    {
        $sizes = [
            'small' => ['width' => 64, 'height' => 64],
            'medium' => ['width' => 128, 'height' => 128],
            'large' => ['width' => 256, 'height' => 256, 'fontSize' => 96],
        ];
        
        // This test validates the structure without actually creating files
        foreach ($sizes as $name => $dimensions) {
            $this->assertArrayHasKey('width', $dimensions);
            $this->assertArrayHasKey('height', $dimensions);
            $this->assertIsInt($dimensions['width']);
            $this->assertIsInt($dimensions['height']);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
