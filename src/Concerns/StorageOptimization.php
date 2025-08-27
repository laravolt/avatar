<?php

declare(strict_types=1);

namespace Laravolt\Avatar\Concerns;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Image;

/**
 * Storage Optimization Trait
 *
 * Provides storage management, compression, and caching functionality
 */
trait StorageOptimization
{
    protected string $storageDisk = 'local';

    protected string $storageDirectory = 'avatars';

    protected int $maxStorageSize = 500; // MB

    protected int $maxFileAge = 30; // days

    protected bool $compressionEnabled = true;

    protected array $storageMetrics = [];

    /**
     * Initialize storage optimization
     */
    protected function initializeStorage(): void
    {
        $this->ensureStorageDirectory();
        $this->loadStorageMetrics();
    }

    /**
     * Ensure storage directory exists
     */
    protected function ensureStorageDirectory(): void
    {
        if (! Storage::disk($this->storageDisk)->exists($this->storageDirectory)) {
            Storage::disk($this->storageDisk)->makeDirectory($this->storageDirectory);
        }
    }

    /**
     * Store avatar with optimized compression
     */
    public function storeOptimized(string $name, string $format = 'png', array $options = []): string
    {
        $this->buildAvatar();

        $filename = $this->generateOptimizedFilename($name, $format);
        $path = $this->storageDirectory.'/'.$filename;
        $fullPath = Storage::disk($this->storageDisk)->path($path);

        // Apply compression based on format and settings
        $this->applyCompression($format, $options);

        $options = match (strtolower($format)) {
            'png' => [
                'interlaced' => $options['interlaced'] ?? true,
                'indexed' => false,
            ],
            'jpg', 'jpeg' => [
                'quality' => $options['quality'] ?? 90,
                'progressive' => $options['progressive'] ?? true,
                'strip' => $options['strip'] ?? true,
            ],
            'webp' => [
                'quality' => $options['quality'] ?? 85,
                'strip' => $options['strip'] ?? true,
            ],
            default => [],
        };

        /** @var Image */
        $image = $this->image;

        // Save the optimized image
        match (strtolower($format)) {
            'png' => $image->toPng(...$options)->save($fullPath),
            'jpg', 'jpeg' => $image->toJpeg(...$options)->save($fullPath),
            'webp' => $image->toWebp(...$options)->save($fullPath),
        };

        // Update storage metrics
        $this->updateStorageMetrics($path, $format);

        // Check storage limits and cleanup if necessary
        $this->checkStorageLimits();

        return Storage::disk($this->storageDisk)->url($path);
    }

    /**
     * Apply compression optimizations
     */
    protected function applyCompression(string $format, array $options): void
    {
        if (! $this->compressionEnabled) {
            return;
        }

        // Ensure image is built before applying compression
        if (! isset($this->image)) {
            $this->buildAvatar();
        }

        switch (strtolower($format)) {
            case 'png':
                // PNG compression through color reduction if size is large
                if ($this->width > 512 && ! ($options['preserve_quality'] ?? false)) {
                    $this->image->reduceColors(256);
                }
                break;

            case 'jpg':
            case 'jpeg':
                // JPEG progressive encoding for better loading
                // Note: interlace() method may not be available in all Intervention Image versions
                // if ($options['progressive'] ?? true) {
                //     $this->image->interlace();
                // }
                break;

            case 'webp':
                // WebP optimization settings are handled in the export
                break;
        }
    }

    /**
     * Generate optimized filename with content hash
     */
    protected function generateOptimizedFilename(string $name, string $format): string
    {
        $hash = $this->generateContentHash();
        $sanitizedName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
        $timestamp = date('Y-m-d');

        return "{$sanitizedName}_{$hash}_{$timestamp}.{$format}";
    }

    /**
     * Get cached avatar URL or generate new one
     */
    public function getCachedOrGenerate(string $name, string $format = 'png', array $options = []): string
    {
        $cacheKey = $this->generateCacheKey($name, $format, $options);

        // Check if URL is cached
        if ($cachedUrl = Cache::get($cacheKey)) {
            // Verify file still exists
            $path = str_replace(Storage::disk($this->storageDisk)->url(''), '', $cachedUrl);
            if (Storage::disk($this->storageDisk)->exists($path)) {
                return $cachedUrl;
            }
        }

        // Generate new avatar and cache the URL
        $url = $this->storeOptimized($name, $format, $options);
        Cache::put($cacheKey, $url, Carbon::now()->addDays(7)); // Cache URL for 7 days

        return $url;
    }

    /**
     * Batch store multiple avatars with optimization
     */
    public function batchStoreOptimized(array $names, string $format = 'png', array $options = []): array
    {
        $results = [];
        $startTime = microtime(true);

        foreach ($names as $name) {
            $this->create($name);
            $results[$name] = $this->storeOptimized($name, $format, $options);
        }

        $processingTime = microtime(true) - $startTime;

        // Log batch processing metrics
        $this->logBatchMetrics(count($names), $processingTime, $format);

        return $results;
    }

    /**
     * Clean up old and oversized files
     */
    public function performCleanup(): array
    {
        $cleaned = [
            'old_files' => $this->cleanupOldFiles(),
            'large_files' => $this->cleanupLargeFiles(),
            'duplicate_files' => $this->removeDuplicateFiles(),
        ];

        $this->rebuildStorageMetrics();

        return $cleaned;
    }

    /**
     * Clean up files older than specified age
     */
    protected function cleanupOldFiles(): array
    {
        $cleaned = [];
        $cutoffTime = Carbon::now()->subDays($this->maxFileAge)->timestamp;

        $files = Storage::disk($this->storageDisk)->allFiles($this->storageDirectory);

        foreach ($files as $file) {
            $lastModified = Storage::disk($this->storageDisk)->lastModified($file);

            if ($lastModified < $cutoffTime) {
                Storage::disk($this->storageDisk)->delete($file);
                $cleaned[] = $file;
            }
        }

        return $cleaned;
    }

    /**
     * Clean up files if storage size exceeds limit
     */
    protected function cleanupLargeFiles(): array
    {
        $cleaned = [];
        $currentSize = $this->getTotalStorageSize();

        if ($currentSize <= $this->maxStorageSize * 1024 * 1024) {
            return $cleaned; // No cleanup needed
        }

        // Get files sorted by size (largest first)
        $files = $this->getFilesSortedBySize();
        $targetReduction = $currentSize - ($this->maxStorageSize * 1024 * 1024 * 0.8); // Reduce to 80% of limit
        $reducedSize = 0;

        foreach ($files as $file) {
            if ($reducedSize >= $targetReduction) {
                break;
            }

            $fileSize = Storage::disk($this->storageDisk)->size($file['path']);
            Storage::disk($this->storageDisk)->delete($file['path']);
            $cleaned[] = $file['path'];
            $reducedSize += $fileSize;
        }

        return $cleaned;
    }

    /**
     * Remove duplicate files based on content hash
     */
    protected function removeDuplicateFiles(): array
    {
        $cleaned = [];
        $hashes = [];

        $files = Storage::disk($this->storageDisk)->allFiles($this->storageDirectory);

        foreach ($files as $file) {
            $content = Storage::disk($this->storageDisk)->get($file);
            $hash = md5($content);

            if (isset($hashes[$hash])) {
                // Duplicate found, remove the newer file
                $existingFile = $hashes[$hash];
                $existingTime = Storage::disk($this->storageDisk)->lastModified($existingFile);
                $currentTime = Storage::disk($this->storageDisk)->lastModified($file);

                if ($currentTime > $existingTime) {
                    Storage::disk($this->storageDisk)->delete($file);
                    $cleaned[] = $file;
                } else {
                    Storage::disk($this->storageDisk)->delete($existingFile);
                    $cleaned[] = $existingFile;
                    $hashes[$hash] = $file;
                }
            } else {
                $hashes[$hash] = $file;
            }
        }

        return $cleaned;
    }

    /**
     * Get total storage size in bytes
     */
    protected function getTotalStorageSize(): int
    {
        $totalSize = 0;
        $files = Storage::disk($this->storageDisk)->allFiles($this->storageDirectory);

        foreach ($files as $file) {
            $totalSize += Storage::disk($this->storageDisk)->size($file);
        }

        return $totalSize;
    }

    /**
     * Get files sorted by size
     */
    protected function getFilesSortedBySize(): array
    {
        $files = [];
        $allFiles = Storage::disk($this->storageDisk)->allFiles($this->storageDirectory);

        foreach ($allFiles as $file) {
            $files[] = [
                'path' => $file,
                'size' => Storage::disk($this->storageDisk)->size($file),
            ];
        }

        // Sort by size (largest first)
        usort($files, fn ($a, $b) => $b['size'] <=> $a['size']);

        return $files;
    }

    /**
     * Update storage metrics
     */
    protected function updateStorageMetrics(string $path, string $format): void
    {
        $size = Storage::disk($this->storageDisk)->size($path);

        $this->storageMetrics['total_files'] = ($this->storageMetrics['total_files'] ?? 0) + 1;
        $this->storageMetrics['total_size'] = ($this->storageMetrics['total_size'] ?? 0) + $size;
        $this->storageMetrics['formats'][$format] = ($this->storageMetrics['formats'][$format] ?? 0) + 1;
        $this->storageMetrics['last_updated'] = Carbon::now()->toISOString();

        // Persist metrics to cache
        Cache::put($this->getMetricsCacheKey(), $this->storageMetrics, Carbon::now()->addHours(1));
    }

    /**
     * Load storage metrics from cache
     */
    protected function loadStorageMetrics(): void
    {
        $cachedMetrics = Cache::get($this->getMetricsCacheKey());

        $this->storageMetrics = is_array($cachedMetrics) ? $cachedMetrics : [
            'total_files' => 0,
            'total_size' => 0,
            'formats' => [],
            'last_updated' => Carbon::now()->toISOString(),
        ];
    }

    /**
     * Rebuild storage metrics by scanning all files
     */
    protected function rebuildStorageMetrics(): void
    {
        $metrics = [
            'total_files' => 0,
            'total_size' => 0,
            'formats' => [],
            'last_updated' => Carbon::now()->toISOString(),
        ];

        $files = Storage::disk($this->storageDisk)->allFiles($this->storageDirectory);

        foreach ($files as $file) {
            $size = Storage::disk($this->storageDisk)->size($file);
            $format = pathinfo($file, PATHINFO_EXTENSION);

            $metrics['total_files']++;
            $metrics['total_size'] += $size;
            $metrics['formats'][$format] = ($metrics['formats'][$format] ?? 0) + 1;
        }

        $this->storageMetrics = $metrics;
        Cache::put($this->getMetricsCacheKey(), $metrics, Carbon::now()->addHours(1));
    }

    /**
     * Check storage limits and trigger cleanup if needed
     */
    protected function checkStorageLimits(): void
    {
        $currentSize = $this->getTotalStorageSize();
        $limitBytes = $this->maxStorageSize * 1024 * 1024;

        if ($currentSize > $limitBytes) {
            $this->performCleanup();
        }
    }

    /**
     * Log batch processing metrics
     */
    protected function logBatchMetrics(int $count, float $processingTime, string $format): void
    {
        $metrics = [
            'batch_size' => $count,
            'processing_time' => $processingTime,
            'avg_time_per_avatar' => $processingTime / $count,
            'format' => $format,
            'timestamp' => Carbon::now()->toISOString(),
        ];

        Cache::put('avatar_batch_metrics_'.time(), $metrics, Carbon::now()->addDays(7));
    }

    /**
     * Generate cache key for URL caching
     */
    protected function generateCacheKey(string $name, string $format, array $options): string
    {
        $data = [
            'name' => $name,
            'format' => $format,
            'width' => $this->width,
            'height' => $this->height,
            'options' => $options,
        ];

        return 'avatar_url_'.md5(serialize($data));
    }

    /**
     * Get metrics cache key
     */
    protected function getMetricsCacheKey(): string
    {
        return 'avatar_storage_metrics';
    }

    /**
     * Get storage statistics
     */
    public function getStorageStatistics(): array
    {
        $this->loadStorageMetrics();

        return [
            'total_files' => $this->storageMetrics['total_files'],
            'total_size_bytes' => $this->storageMetrics['total_size'],
            'total_size_mb' => round($this->storageMetrics['total_size'] / 1024 / 1024, 2),
            'formats' => $this->storageMetrics['formats'],
            'storage_limit_mb' => $this->maxStorageSize,
            'usage_percentage' => round(($this->storageMetrics['total_size'] / ($this->maxStorageSize * 1024 * 1024)) * 100, 2),
            'last_updated' => $this->storageMetrics['last_updated'],
            'disk' => $this->storageDisk,
            'directory' => $this->storageDirectory,
        ];
    }

    /**
     * Set storage configuration
     */
    public function configureStorage(string $disk, string $directory, int $maxSizeMB = 500): static
    {
        $this->storageDisk = $disk;
        $this->storageDirectory = $directory;
        $this->maxStorageSize = $maxSizeMB;

        $this->initializeStorage();

        return $this;
    }

    /**
     * Enable or disable compression
     */
    public function setCompressionEnabled(bool $enabled): static
    {
        $this->compressionEnabled = $enabled;

        return $this;
    }

    /**
     * Set maximum file age for cleanup
     */
    public function setMaxFileAge(int $days): static
    {
        $this->maxFileAge = $days;

        return $this;
    }

    /**
     * Get storage disk (for testing purposes)
     */
    public function getStorageDisk(): string
    {
        return $this->storageDisk;
    }

    /**
     * Get storage directory (for testing purposes)
     */
    public function getStorageDirectory(): string
    {
        return $this->storageDirectory;
    }

    /**
     * Get max storage size (for testing purposes)
     */
    public function getMaxStorageSize(): int
    {
        return $this->maxStorageSize;
    }

    /**
     * Get max file age (for testing purposes)
     */
    public function getMaxFileAge(): int
    {
        return $this->maxFileAge;
    }

    /**
     * Get compression enabled status (for testing purposes)
     */
    public function getCompressionEnabled(): bool
    {
        return $this->compressionEnabled;
    }

    /**
     * Get storage metrics (for testing purposes)
     */
    public function getStorageMetrics(): array
    {
        return $this->storageMetrics;
    }
}
