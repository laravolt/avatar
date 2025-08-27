<?php

namespace Laravolt\Avatar;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use Laravolt\Avatar\Avatar;

/**
 * HD Avatar Response Class
 *
 * Provides high-definition avatar generation with enhanced performance
 * through image export and storage optimization.
 */
class HDAvatarResponse extends Avatar
{
    protected array $hdConfig = [];
    protected string $exportPath = 'avatars';
    protected array $responsiveSizes = [];
    protected bool $hdEnabled = true;
    protected array $exportedFiles = [];

    public function __construct(array $config = [], ?Repository $cache = null)
    {
        // Load HD configuration
        $this->hdConfig = $config['hd'] ?? [];
        $this->responsiveSizes = $config['responsive_sizes'] ?? [];
        $this->exportPath = $config['export']['path'] ?? 'avatars';
        $this->hdEnabled = $config['hd']['enabled'] ?? true;

        // Apply HD defaults if enabled
        if ($this->hdEnabled) {
            $config = $this->applyHDDefaults($config);
        }

        parent::__construct($config, $cache);
    }

    /**
     * Apply HD defaults to configuration
     */
    protected function applyHDDefaults(array $config): array
    {
        $hdDefaults = [
            'width' => $this->hdConfig['width'] ?? 512,
            'height' => $this->hdConfig['height'] ?? 512,
            'fontSize' => $this->hdConfig['fontSize'] ?? 192,
            'driver' => 'imagick', // Prefer imagick for HD
        ];

        return array_merge($config, $hdDefaults);
    }

    /**
     * Create HD avatar with multiple export options
     */
    public function createHD(string $name): static
    {
        $this->name = $name;
        $this->initTheme();

        return $this;
    }

    /**
     * Export avatar as high-quality image file
     */
    public function export(string $format = 'png', int $quality = 95): string
    {
        $this->buildAvatar();

        $filename = $this->generateFilename($format);
        $filepath = $this->exportPath . '/' . $filename;

        // Ensure directory exists
        Storage::makeDirectory($this->exportPath);

        // Get full storage path
        $fullPath = Storage::path($filepath);

        // Apply format-specific optimizations
        switch (strtolower($format)) {
            case 'png':
                $this->image->toPng($this->hdConfig['quality']['png'] ?? $quality)->save($fullPath);
                break;

            case 'jpg':
            case 'jpeg':
                $this->image->toJpeg($this->hdConfig['quality']['jpg'] ?? $quality)->save($fullPath);
                break;

            case 'webp':
                $this->image->toWebp($this->hdConfig['quality']['webp'] ?? $quality)->save($fullPath);
                break;

            default:
                throw new \InvalidArgumentException("Unsupported format: {$format}");
        }

        $this->exportedFiles[] = $filepath;

        return $filepath;
    }

    /**
     * Export multiple formats simultaneously
     */
    public function exportMultiple(array $formats = ['png', 'jpg', 'webp']): array
    {
        $files = [];

        foreach ($formats as $format) {
            $files[$format] = $this->export($format);
        }

        return $files;
    }

    /**
     * Export responsive sizes
     */
    public function exportResponsive(string $format = 'png'): array
    {
        $files = [];
        $originalWidth = $this->width;
        $originalHeight = $this->height;
        $originalFontSize = $this->fontSize;

        foreach ($this->responsiveSizes as $size => $dimensions) {
            $this->setDimension($dimensions['width'], $dimensions['height']);
            $this->setFontSize($dimensions['fontSize']);

            $filename = $this->generateFilename($format, $size);
            $filepath = $this->exportPath . '/' . $filename;

            Storage::makeDirectory($this->exportPath);
            $fullPath = Storage::path($filepath);

            $this->buildAvatar();

            switch (strtolower($format)) {
                case 'png':
                    $this->image->toPng()->save($fullPath);
                    break;
                case 'jpg':
                case 'jpeg':
                    $this->image->toJpeg()->save($fullPath);
                    break;
                case 'webp':
                    $this->image->toWebp()->save($fullPath);
                    break;
            }

            $files[$size] = $filepath;
        }

        // Restore original dimensions
        $this->setDimension($originalWidth, $originalHeight);
        $this->setFontSize($originalFontSize);

        return $files;
    }

    /**
     * Get avatar as HTTP response with optimized headers
     */
    public function toResponse(string $format = 'png'): Response
    {
        $this->buildAvatar();

        $content = match (strtolower($format)) {
            'png' => $this->image->toPng()->toString(),
            'jpg', 'jpeg' => $this->image->toJpeg()->toString(),
            'webp' => $this->image->toWebp()->toString(),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}")
        };

        $mimeType = match (strtolower($format)) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
        };

        return new Response($content, 200, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000', // 1 year
            'Expires' => gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT',
            'Last-Modified' => gmdate('D, d M Y H:i:s') . ' GMT',
            'ETag' => '"' . md5($content) . '"',
        ]);
    }

    /**
     * Get cached file URL or generate new one
     */
    public function getCachedUrl(string $format = 'png', string $size = 'medium'): string
    {
        $filename = $this->generateFilename($format, $size);
        $filepath = $this->exportPath . '/' . $filename;

        // Check if file exists in storage
        if (Storage::exists($filepath)) {
            return Storage::url($filepath);
        }

        // Generate and cache the file
        if (isset($this->responsiveSizes[$size])) {
            $dimensions = $this->responsiveSizes[$size];
            $this->setDimension($dimensions['width'], $dimensions['height']);
            $this->setFontSize($dimensions['fontSize']);
        }

        $this->export($format);

        return Storage::url($filepath);
    }

    /**
     * Generate optimized filename
     */
    protected function generateFilename(string $format, ?string $size = null): string
    {
        $hash = $this->generateContentHash();
        $timestamp = time();
        $initials = $this->getInitial();

        $sizeSuffix = $size ? "_{$size}" : '';

        return "{$hash}{$sizeSuffix}_{$timestamp}.{$format}";
    }

    /**
     * Generate content-based hash for caching
     */
    protected function generateContentHash(): string
    {
        $content = [
            'name' => $this->name,
            'width' => $this->width,
            'height' => $this->height,
            'fontSize' => $this->fontSize,
            'background' => $this->background,
            'foreground' => $this->foreground,
            'shape' => $this->shape,
            'borderSize' => $this->borderSize,
            'borderColor' => $this->borderColor,
            'font' => $this->font,
        ];

        return substr(md5(serialize($content)), 0, 8);
    }

    /**
     * Clean up old cached files
     */
    public function cleanup(int $maxAgeDays = 30): array
    {
        $cleaned = [];
        $cutoffTime = time() - ($maxAgeDays * 24 * 60 * 60);

        $files = Storage::allFiles($this->exportPath);

        foreach ($files as $file) {
            $lastModified = Storage::lastModified($file);

            if ($lastModified < $cutoffTime) {
                Storage::delete($file);
                $cleaned[] = $file;
            }
        }

        return $cleaned;
    }

    /**
     * Get storage statistics
     */
    public function getStorageStats(): array
    {
        $files = Storage::allFiles($this->exportPath);
        $totalSize = 0;
        $fileCount = count($files);

        foreach ($files as $file) {
            $totalSize += Storage::size($file);
        }

        return [
            'file_count' => $fileCount,
            'total_size_bytes' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2),
            'average_file_size_kb' => $fileCount > 0 ? round(($totalSize / $fileCount) / 1024, 2) : 0,
        ];
    }

    /**
     * Generate placeholder image for lazy loading
     */
    public function toPlaceholder(int $width = 64, int $height = 64): string
    {
        $originalWidth = $this->width;
        $originalHeight = $this->height;
        $originalFontSize = $this->fontSize;

        $this->setDimension($width, $height);
        $this->setFontSize($width / 4);

        $this->buildAvatar();

        // Apply blur effect for placeholder
        $this->image->blur(5);

        $placeholder = $this->image->toPng()->toDataUri();

        // Restore original dimensions
        $this->setDimension($originalWidth, $originalHeight);
        $this->setFontSize($originalFontSize);

        return $placeholder;
    }

    /**
     * Set HD quality settings
     */
    public function setQuality(string $format, int $quality): static
    {
        $this->hdConfig['quality'][$format] = $quality;

        return $this;
    }

    /**
     * Enable/disable HD mode
     */
    public function setHDMode(bool $enabled): static
    {
        $this->hdEnabled = $enabled;

        return $this;
    }

    /**
     * Set responsive size configuration
     */
    public function setResponsiveSize(string $name, int $width, int $height, int $fontSize): static
    {
        $this->responsiveSizes[$name] = [
            'width' => $width,
            'height' => $height,
            'fontSize' => $fontSize,
        ];

        return $this;
    }

    /**
     * Batch export multiple avatars for better performance
     */
    public function batchExport(array $names, string $format = 'png', string $size = 'medium'): array
    {
        $results = [];

        foreach ($names as $name) {
            $this->create($name);

            if (isset($this->responsiveSizes[$size])) {
                $dimensions = $this->responsiveSizes[$size];
                $this->setDimension($dimensions['width'], $dimensions['height']);
                $this->setFontSize($dimensions['fontSize']);
            }

            $filepath = $this->export($format);
            $results[$name] = $filepath;
        }

        return $results;
    }

    /**
     * Get exported files list
     */
    public function getExportedFiles(): array
    {
        return $this->exportedFiles;
    }

    /**
     * Clear exported files list
     */
    public function clearExportedFiles(): static
    {
        $this->exportedFiles = [];

        return $this;
    }
}
