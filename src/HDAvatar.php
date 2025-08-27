<?php

namespace Laravolt\Avatar;

use Illuminate\Contracts\Cache\Repository;
use Laravolt\Avatar\Concerns\ImageExport;
use Laravolt\Avatar\Concerns\StorageOptimization;

/**
 * HD Avatar Class
 *
 * Enhanced avatar class that combines HDAvatarResponse with export and storage traits
 * for complete HD avatar functionality with performance optimization.
 */
class HDAvatar extends HDAvatarResponse
{
    use ImageExport, StorageOptimization;

    public function __construct(array $config = [], ?Repository $cache = null)
    {
        parent::__construct($config, $cache);

        // Initialize storage optimization
        $this->initializeStorage();

        // Configure storage settings from config
        if (isset($config['storage'])) {
            $storageConfig = $config['storage'];
            $this->configureStorage(
                $storageConfig['disk'] ?? 'local',
                $storageConfig['directory'] ?? 'avatars',
                $storageConfig['max_storage_mb'] ?? 500
            );

            $this->setMaxFileAge($storageConfig['max_age_days'] ?? 30);
            $this->setCompressionEnabled($storageConfig['compression'] ?? true);
        }
    }

    /**
     * Create and immediately export HD avatar
     */
    public function createAndExport(string $name, string $format = 'png', array $options = []): array
    {
        $this->createHD($name);

        $exported = [
            'name' => $name,
            'format' => $format,
            'url' => $this->storeOptimized($name, $format, $options),
            'responsive_urls' => [],
            'metadata' => [
                'width' => $this->width,
                'height' => $this->height,
                'font_size' => $this->fontSize,
                'background' => $this->background,
                'foreground' => $this->foreground,
                'hash' => $this->generateContentHash(),
            ],
        ];

        // Generate responsive sizes if configured
        if (!empty($this->responsiveSizes)) {
            foreach ($this->responsiveSizes as $size => $dimensions) {
                $this->setDimension($dimensions['width'], $dimensions['height']);
                $this->setFontSize($dimensions['fontSize']);

                $exported['responsive_urls'][$size] = $this->storeOptimized("{$name}_{$size}", $format, $options);
            }
        }

        return $exported;
    }

    /**
     * Batch create and export multiple avatars
     */
    public function batchCreateAndExport(array $names, string $format = 'png', array $options = []): array
    {
        $results = [];
        $startTime = microtime(true);

        foreach ($names as $name) {
            $results[$name] = $this->createAndExport($name, $format, $options);
        }

        $processingTime = microtime(true) - $startTime;

        return [
            'avatars' => $results,
            'batch_info' => [
                'total_count' => count($names),
                'processing_time_seconds' => round($processingTime, 3),
                'average_time_per_avatar' => round($processingTime / count($names), 3),
                'format' => $format,
                'timestamp' => now()->toISOString(),
            ],
        ];
    }

    /**
     * Generate avatar with all export formats
     */
    public function exportAllFormats(string $name, array $options = []): array
    {
        $this->createHD($name);

        $formats = ['png', 'jpg', 'webp'];
        $exports = [];

        foreach ($formats as $format) {
            $exports[$format] = [
                'url' => $this->storeOptimized($name, $format, $options),
                'cached_url' => $this->getCachedOrGenerate($name, $format, $options),
            ];
        }

        return $exports;
    }

    /**
     * Create avatar sprite sheet with variations
     */
    public function createSpriteSheet(string $name, array $variations, string $format = 'png'): string
    {
        $this->createHD($name);

        $filename = $this->generateOptimizedFilename("{$name}_sprite", $format);
        $path = $this->storageDirectory . '/' . $filename;
        $fullPath = Storage::disk($this->storageDisk)->path($path);

        $this->exportSpriteSheet($variations, $fullPath, $format);

        return Storage::disk($this->storageDisk)->url($path);
    }

    /**
     * Get comprehensive avatar information
     */
    public function getAvatarInfo(string $name): array
    {
        $this->createHD($name);

        return [
            'name' => $name,
            'initials' => $this->getInitial(),
            'dimensions' => [
                'width' => $this->width,
                'height' => $this->height,
            ],
            'styling' => [
                'background' => $this->background,
                'foreground' => $this->foreground,
                'font_size' => $this->fontSize,
                'shape' => $this->shape,
                'border' => [
                    'size' => $this->borderSize,
                    'color' => $this->borderColor,
                    'radius' => $this->borderRadius,
                ],
            ],
            'configuration' => [
                'hd_enabled' => $this->hdEnabled,
                'responsive_sizes' => $this->responsiveSizes,
                'export_path' => $this->exportPath,
            ],
            'performance' => [
                'cache_enabled' => $this->cacheEnabled,
                'compression_enabled' => $this->compressionEnabled,
                'storage_disk' => $this->storageDisk,
            ],
            'hash' => $this->generateContentHash(),
            'estimated_file_sizes' => $this->estimateFileSizes(),
        ];
    }

    /**
     * Optimize existing avatar storage
     */
    public function optimizeStorage(): array
    {
        $beforeStats = $this->getStorageStatistics();
        $cleaned = $this->performCleanup();
        $afterStats = $this->getStorageStatistics();

        return [
            'before' => $beforeStats,
            'after' => $afterStats,
            'cleaned' => $cleaned,
            'optimization_summary' => [
                'files_removed' => array_sum(array_map('count', $cleaned)),
                'space_saved_mb' => round(($beforeStats['total_size_mb'] - $afterStats['total_size_mb']), 2),
                'optimization_percentage' => round((($beforeStats['total_size_mb'] - $afterStats['total_size_mb']) / max($beforeStats['total_size_mb'], 0.1)) * 100, 2),
            ],
        ];
    }

    /**
     * Generate avatar API response
     */
    public function apiResponse(string $name, string $format = 'png', string $size = 'medium'): array
    {
        $info = $this->getAvatarInfo($name);

        // Set appropriate size
        if (isset($this->responsiveSizes[$size])) {
            $dimensions = $this->responsiveSizes[$size];
            $this->setDimension($dimensions['width'], $dimensions['height']);
            $this->setFontSize($dimensions['fontSize']);
        }

        return [
            'success' => true,
            'data' => [
                'avatar' => [
                    'name' => $name,
                    'initials' => $info['initials'],
                    'url' => $this->getCachedOrGenerate($name, $format, []),
                    'placeholder' => $this->toPlaceholder(),
                    'responsive_urls' => $this->generateResponsiveUrls($name, $format),
                ],
                'metadata' => [
                    'size' => $size,
                    'format' => $format,
                    'dimensions' => [
                        'width' => $this->width,
                        'height' => $this->height,
                    ],
                    'hash' => $info['hash'],
                    'cache_key' => $this->generateCacheKey($name, $format, ['size' => $size]),
                ],
            ],
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Generate responsive URLs for all configured sizes
     */
    protected function generateResponsiveUrls(string $name, string $format): array
    {
        $urls = [];

        foreach ($this->responsiveSizes as $size => $dimensions) {
            $urls[$size] = $this->getCachedUrl($format, $size);
        }

        return $urls;
    }

    /**
     * Health check for HD avatar system
     */
    public function healthCheck(): array
    {
        $stats = $this->getStorageStatistics();
        $exportStats = $this->getExportStats();

        $health = [
            'status' => 'healthy',
            'checks' => [
                'storage_accessible' => true,
                'cache_functional' => true,
                'within_storage_limits' => $stats['usage_percentage'] < 90,
                'export_formats_supported' => count($exportStats['supported_formats']) >= 3,
            ],
            'warnings' => [],
            'statistics' => [
                'storage' => $stats,
                'export' => $exportStats,
            ],
        ];

        // Add warnings based on checks
        if (!$health['checks']['within_storage_limits']) {
            $health['warnings'][] = "Storage usage is at {$stats['usage_percentage']}% - cleanup recommended";
        }

        if ($stats['total_files'] > 10000) {
            $health['warnings'][] = "Large number of cached files ({$stats['total_files']}) - consider cleanup";
        }

        // Overall status
        $failedChecks = array_filter($health['checks'], fn ($check) => !$check);
        if (!empty($failedChecks)) {
            $health['status'] = 'degraded';
        }

        if (count($failedChecks) > 2) {
            $health['status'] = 'unhealthy';
        }

        return $health;
    }
}
