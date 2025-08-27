<?php

declare(strict_types=1);

namespace Laravolt\Avatar\Concerns;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Interfaces\ImageInterface;

/**
 * Image Export Trait
 *
 * Provides high-quality image export functionality with various optimizations
 */
trait ImageExport
{
    protected array $exportFormats = ['png', 'jpg', 'jpeg', 'webp'];

    protected array $exportOptions = [];

    /**
     * Export image with format-specific optimizations
     */
    public function exportImage(string $path, string $format = 'png', array $options = []): ImageInterface
    {
        $this->buildAvatar();

        $format = strtolower($format);
        $this->validateExportFormat($format);

        $mergedOptions = array_merge($this->getDefaultExportOptions($format), $options);

        return match ($format) {
            'png' => $this->exportPNG($path, $mergedOptions),
            'jpg', 'jpeg' => $this->exportJPEG($path, $mergedOptions),
            'webp' => $this->exportWebP($path, $mergedOptions),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}")
        };
    }

    /**
     * Export as PNG with optimizations
     */
    protected function exportPNG(string $path, array $options): ImageInterface
    {
        $quality = $options['quality'] ?? 95;
        $compression = $options['compression'] ?? 6;
        $interlaced = $options['interlaced'] ?? false;

        // Apply PNG-specific optimizations
        if ($interlaced) {
            $this->image->interlace();
        }

        return $this->image->toPng($quality)->save($path);
    }

    /**
     * Export as JPEG with optimizations
     */
    protected function exportJPEG(string $path, array $options): ImageInterface
    {
        $quality = $options['quality'] ?? 90;
        $progressive = $options['progressive'] ?? true;

        // Apply JPEG-specific optimizations
        if ($progressive) {
            $this->image->interlace();
        }

        return $this->image->toJpeg($quality)->save($path);
    }

    /**
     * Export as WebP with optimizations
     */
    protected function exportWebP(string $path, array $options): ImageInterface
    {
        $quality = $options['quality'] ?? 85;
        $lossless = $options['lossless'] ?? false;

        // Apply WebP-specific optimizations
        $webpQuality = $lossless ? 100 : $quality;

        return $this->image->toWebp($webpQuality)->save($path);
    }

    /**
     * Export with multiple sizes for responsive design
     */
    public function exportResponsiveSizes(string $basePath, array $sizes, string $format = 'png'): array
    {
        $exported = [];
        $originalWidth = $this->width;
        $originalHeight = $this->height;
        $originalFontSize = $this->fontSize;

        foreach ($sizes as $sizeName => $dimensions) {
            // Update dimensions
            $this->setDimension($dimensions['width'], $dimensions['height'] ?? $dimensions['width']);
            if (isset($dimensions['fontSize'])) {
                $this->setFontSize($dimensions['fontSize']);
            } else {
                // Auto-calculate font size based on width
                $this->setFontSize(intval($dimensions['width'] * 0.375)); // ~37.5% of width
            }

            // Generate filename with size suffix
            $pathInfo = pathinfo($basePath);
            $filename = $pathInfo['filename'].'_'.$sizeName.'.'.$format;
            $fullPath = ($pathInfo['dirname'] !== '.' ? $pathInfo['dirname'].'/' : '').$filename;

            // Export the sized image
            $this->exportImage($fullPath, $format);
            $exported[$sizeName] = $fullPath;
        }

        // Restore original dimensions
        $this->setDimension($originalWidth, $originalHeight);
        $this->setFontSize($originalFontSize);

        return $exported;
    }

    /**
     * Bulk export multiple avatars efficiently
     */
    public function bulkExport(array $names, string $directory, string $format = 'png', array $options = []): array
    {
        $exported = [];

        // Ensure directory exists
        Storage::makeDirectory($directory);

        foreach ($names as $name) {
            $this->create($name);

            // Generate filename
            $sanitizedName = $this->sanitizeFilename($name);
            $filename = $sanitizedName.'_'.$this->generateContentHash().'.'.$format;
            $path = $directory.'/'.$filename;
            $fullPath = Storage::path($path);

            // Export the avatar
            $this->exportImage($fullPath, $format, $options);
            $exported[$name] = $path;
        }

        return $exported;
    }

    /**
     * Export with watermark
     */
    public function exportWithWatermark(string $path, string $watermarkText, string $format = 'png', array $options = []): ImageInterface
    {
        $this->buildAvatar();

        // Add watermark
        $this->addWatermark($watermarkText, $options['watermark'] ?? []);

        return $this->exportImage($path, $format, $options);
    }

    /**
     * Add watermark to the image
     */
    protected function addWatermark(string $text, array $options = []): void
    {
        $position = $options['position'] ?? 'bottom-right';
        $opacity = $options['opacity'] ?? 0.3;
        $fontSize = $options['fontSize'] ?? intval($this->width * 0.08);
        $color = $options['color'] ?? '#FFFFFF';

        // Calculate position coordinates
        [$x, $y] = $this->calculateWatermarkPosition($position, $text, $fontSize);

        // Apply watermark
        $this->image->text(
            $text,
            $x,
            $y,
            function ($font) use ($fontSize, $color, $opacity) {
                $font->file($this->font);
                $font->size($fontSize);
                $font->color($color);
                $font->alpha($opacity);
                $font->align('left');
                $font->valign('bottom');
            }
        );
    }

    /**
     * Calculate watermark position
     */
    protected function calculateWatermarkPosition(string $position, string $text, int $fontSize): array
    {
        $margin = intval($this->width * 0.05); // 5% margin

        return match ($position) {
            'top-left' => [$margin, $margin + $fontSize],
            'top-right' => [$this->width - $margin, $margin + $fontSize],
            'bottom-left' => [$margin, $this->height - $margin],
            'bottom-right' => [$this->width - $margin, $this->height - $margin],
            'center' => [$this->width / 2, $this->height / 2],
            default => [$this->width - $margin, $this->height - $margin],
        };
    }

    /**
     * Export as sprite sheet for animations
     */
    public function exportSpriteSheet(array $variations, string $path, string $format = 'png', array $options = []): ImageInterface
    {
        $spriteWidth = ($options['sprite_width'] ?? count($variations)) * $this->width;
        $spriteHeight = $this->height;

        // Create sprite canvas
        $driver = $this->driver === 'gd' ? new \Intervention\Image\Drivers\Gd\Driver : new \Intervention\Image\Drivers\Imagick\Driver;
        $manager = new \Intervention\Image\ImageManager($driver);
        $sprite = $manager->create($spriteWidth, $spriteHeight);

        // Add each variation to the sprite
        $x = 0;
        foreach ($variations as $variation) {
            // Apply variation (e.g., different colors, effects)
            $this->applyVariation($variation);
            $this->buildAvatar();

            // Copy to sprite at current x position
            $sprite->place($this->image, 'top-left', $x, 0);
            $x += $this->width;
        }

        // Export sprite sheet
        return match (strtolower($format)) {
            'png' => $sprite->toPng()->save($path),
            'jpg', 'jpeg' => $sprite->toJpeg()->save($path),
            'webp' => $sprite->toWebp()->save($path),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}")
        };
    }

    /**
     * Apply variation to avatar (override in subclasses)
     */
    protected function applyVariation(array $variation): void
    {
        if (isset($variation['background'])) {
            $this->setBackground($variation['background']);
        }
        if (isset($variation['foreground'])) {
            $this->setForeground($variation['foreground']);
        }
        if (isset($variation['shape'])) {
            $this->setShape($variation['shape']);
        }
    }

    /**
     * Get default export options for format
     */
    protected function getDefaultExportOptions(string $format): array
    {
        return match ($format) {
            'png' => [
                'quality' => 95,
                'compression' => 6,
                'interlaced' => false,
            ],
            'jpg', 'jpeg' => [
                'quality' => 90,
                'progressive' => true,
            ],
            'webp' => [
                'quality' => 85,
                'lossless' => false,
            ],
            default => [],
        };
    }

    /**
     * Validate export format
     */
    protected function validateExportFormat(string $format): void
    {
        if (! in_array($format, $this->exportFormats)) {
            throw new \InvalidArgumentException(
                "Unsupported format '{$format}'. Supported formats: ".implode(', ', $this->exportFormats)
            );
        }
    }

    /**
     * Sanitize filename for safe file system usage
     */
    protected function sanitizeFilename(string $filename): string
    {
        // Remove or replace unsafe characters
        $unsafe = ['/', '\\', ':', '*', '?', '"', '<', '>', '|'];
        $safe = str_replace($unsafe, '_', $filename);

        // Remove multiple underscores and trim
        $safe = preg_replace('/_+/', '_', $safe);
        $safe = trim($safe, '_');

        // Limit length
        return substr($safe, 0, 100);
    }

    /**
     * Set export options
     */
    public function setExportOptions(array $options): static
    {
        $this->exportOptions = array_merge($this->exportOptions, $options);

        return $this;
    }

    /**
     * Get export statistics
     */
    public function getExportStats(): array
    {
        return [
            'supported_formats' => $this->exportFormats,
            'current_options' => $this->exportOptions,
            'image_dimensions' => [
                'width' => $this->width,
                'height' => $this->height,
            ],
            'estimated_file_sizes' => $this->estimateFileSizes(),
        ];
    }

    /**
     * Estimate file sizes for different formats
     */
    protected function estimateFileSizes(): array
    {
        $pixelCount = $this->width * $this->height;

        return [
            'png' => intval($pixelCount * 3.5).' bytes (estimated)', // ~3.5 bytes per pixel for PNG
            'jpg' => intval($pixelCount * 0.5).' bytes (estimated)', // ~0.5 bytes per pixel for JPEG
            'webp' => intval($pixelCount * 0.4).' bytes (estimated)', // ~0.4 bytes per pixel for WebP
        ];
    }

    /**
     * Get export formats (for testing purposes)
     */
    public function getExportFormats(): array
    {
        return $this->exportFormats;
    }

    /**
     * Get export options (for testing purposes)
     */
    public function getExportOptions(): array
    {
        return $this->exportOptions;
    }
}
