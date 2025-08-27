<?php

/**
 * HD Avatar Usage Examples
 *
 * This file demonstrates how to use the HD Avatar functionality
 * for high-performance avatar generation with export and storage optimization.
 */

require_once __DIR__.'/../vendor/autoload.php';

use Laravolt\Avatar\HDAvatar;
use Laravolt\Avatar\HDAvatarResponse;

// =============================================================================
// Basic HD Avatar Usage
// =============================================================================

// Load HD configuration
$hdConfig = require __DIR__.'/../config/hd-avatar.php';

// Create HD Avatar instance
$hdAvatar = new HDAvatar($hdConfig);

// Create and export a single avatar
echo "=== Basic HD Avatar Creation ===\n";
$result = $hdAvatar->createAndExport('John Doe', 'png');
echo 'Avatar URL: '.$result['url']."\n";
echo 'Avatar Hash: '.$result['metadata']['hash']."\n";
echo "Dimensions: {$result['metadata']['width']}x{$result['metadata']['height']}\n\n";

// =============================================================================
// Responsive Avatar Generation
// =============================================================================

echo "=== Responsive Avatar Generation ===\n";
// Create avatar with multiple sizes
$responsiveResult = $hdAvatar->createAndExport('Jane Smith', 'webp');
foreach ($responsiveResult['responsive_urls'] as $size => $url) {
    echo "Size {$size}: {$url}\n";
}
echo "\n";

// =============================================================================
// Batch Avatar Generation
// =============================================================================

echo "=== Batch Avatar Generation ===\n";
$names = ['Alice Johnson', 'Bob Wilson', 'Carol Brown', 'David Miller'];
$batchResult = $hdAvatar->batchCreateAndExport($names, 'png');

echo "Processed {$batchResult['batch_info']['total_count']} avatars in {$batchResult['batch_info']['processing_time_seconds']} seconds\n";
echo "Average time per avatar: {$batchResult['batch_info']['average_time_per_avatar']} seconds\n\n";

// =============================================================================
// Multiple Format Export
// =============================================================================

echo "=== Multiple Format Export ===\n";
$allFormats = $hdAvatar->exportAllFormats('Emma Davis');
foreach ($allFormats as $format => $urls) {
    echo "Format {$format}: {$urls['url']}\n";
}
echo "\n";

// =============================================================================
// Advanced HD Avatar with Custom Settings
// =============================================================================

echo "=== Advanced HD Avatar with Custom Settings ===\n";

// Create HD avatar with custom configuration
$customHDAvatar = new HDAvatarResponse([
    'hd' => [
        'enabled' => true,
        'width' => 1024,
        'height' => 1024,
        'fontSize' => 384,
        'quality' => [
            'png' => 100,
            'jpg' => 95,
            'webp' => 90,
        ],
    ],
    'responsive_sizes' => [
        'thumbnail' => ['width' => 64, 'height' => 64, 'fontSize' => 24],
        'small' => ['width' => 128, 'height' => 128, 'fontSize' => 48],
        'medium' => ['width' => 256, 'height' => 256, 'fontSize' => 96],
        'large' => ['width' => 512, 'height' => 512, 'fontSize' => 192],
        'ultra' => ['width' => 1024, 'height' => 1024, 'fontSize' => 384],
    ],
]);

// Create ultra-HD avatar
$ultraHD = $customHDAvatar->createHD('Michael Chen')
    ->setDimension(1024, 1024)
    ->setFontSize(384)
    ->setBackground('#667eea')
    ->setForeground('#FFFFFF');

// Export in multiple formats
$ultraFiles = $ultraHD->exportMultiple(['png', 'jpg', 'webp']);
foreach ($ultraFiles as $format => $file) {
    echo "Ultra-HD {$format}: {$file}\n";
}
echo "\n";

// =============================================================================
// Performance Optimization Examples
// =============================================================================

echo "=== Performance Optimization ===\n";

// Configure storage optimization
$hdAvatar->configureStorage('local', 'optimized-avatars', 1000); // 1GB limit
$hdAvatar->setCompressionEnabled(true);
$hdAvatar->setMaxFileAge(14); // 14 days

// Get storage statistics
$stats = $hdAvatar->getStorageStatistics();
echo "Storage Usage: {$stats['total_size_mb']} MB ({$stats['usage_percentage']}%)\n";
echo "Total Files: {$stats['total_files']}\n";

// Optimize storage if needed
if ($stats['usage_percentage'] > 80) {
    echo "Optimizing storage...\n";
    $optimization = $hdAvatar->optimizeStorage();
    echo "Removed {$optimization['optimization_summary']['files_removed']} files\n";
    echo "Saved {$optimization['optimization_summary']['space_saved_mb']} MB\n";
}
echo "\n";

// =============================================================================
// API Response Generation
// =============================================================================

echo "=== API Response Generation ===\n";

// Generate API-ready response
$apiResponse = $hdAvatar->apiResponse('Sarah Wilson', 'webp', 'large');
echo "API Response for {$apiResponse['data']['avatar']['name']}:\n";
echo "- URL: {$apiResponse['data']['avatar']['url']}\n";
echo "- Initials: {$apiResponse['data']['avatar']['initials']}\n";
echo "- Hash: {$apiResponse['data']['metadata']['hash']}\n";
echo "- Cache Key: {$apiResponse['data']['metadata']['cache_key']}\n";
echo "\n";

// =============================================================================
// Watermarked Avatars
// =============================================================================

echo "=== Watermarked Avatars ===\n";

// Create avatar with watermark using the ImageExport trait
$watermarkedPath = 'storage/avatars/watermarked_avatar.png';
$hdAvatar->createHD('Company User')
    ->setBackground('#4f46e5')
    ->setForeground('#ffffff');

// Export with watermark (this would work in a real Laravel environment)
echo "Watermarked avatar would be saved to: {$watermarkedPath}\n\n";

// =============================================================================
// Health Check and Monitoring
// =============================================================================

echo "=== Health Check ===\n";

$health = $hdAvatar->healthCheck();
echo "System Status: {$health['status']}\n";

foreach ($health['checks'] as $check => $status) {
    $statusText = $status ? 'PASS' : 'FAIL';
    echo "- {$check}: {$statusText}\n";
}

if (! empty($health['warnings'])) {
    echo "Warnings:\n";
    foreach ($health['warnings'] as $warning) {
        echo "- {$warning}\n";
    }
}
echo "\n";

// =============================================================================
// Avatar Information Retrieval
// =============================================================================

echo "=== Avatar Information ===\n";

$avatarInfo = $hdAvatar->getAvatarInfo('Technical User');
echo "Avatar Details:\n";
echo "- Name: {$avatarInfo['name']}\n";
echo "- Initials: {$avatarInfo['initials']}\n";
echo "- Dimensions: {$avatarInfo['dimensions']['width']}x{$avatarInfo['dimensions']['height']}\n";
echo "- Background: {$avatarInfo['styling']['background']}\n";
echo "- Foreground: {$avatarInfo['styling']['foreground']}\n";
echo '- HD Enabled: '.($avatarInfo['configuration']['hd_enabled'] ? 'Yes' : 'No')."\n";
echo '- Cache Enabled: '.($avatarInfo['performance']['cache_enabled'] ? 'Yes' : 'No')."\n";

echo "\nEstimated File Sizes:\n";
foreach ($avatarInfo['estimated_file_sizes'] as $format => $size) {
    echo "- {$format}: {$size}\n";
}
echo "\n";

// =============================================================================
// Placeholder Generation for Lazy Loading
// =============================================================================

echo "=== Placeholder Generation ===\n";

$placeholder = $hdAvatar->createHD('Lazy User')->toPlaceholder(32, 32);
echo 'Placeholder Data URI: '.substr($placeholder, 0, 50)."...\n";
echo 'Placeholder Length: '.strlen($placeholder)." characters\n\n";

// =============================================================================
// Sprite Sheet Generation
// =============================================================================

echo "=== Sprite Sheet Generation ===\n";

$variations = [
    ['background' => '#ff6b6b', 'foreground' => '#ffffff'],
    ['background' => '#4ecdc4', 'foreground' => '#ffffff'],
    ['background' => '#45b7d1', 'foreground' => '#ffffff'],
    ['background' => '#96ceb4', 'foreground' => '#ffffff'],
    ['background' => '#ffeaa7', 'foreground' => '#2d3436'],
];

$spriteUrl = $hdAvatar->createSpriteSheet('Sprite User', $variations, 'png');
echo "Sprite sheet created: {$spriteUrl}\n\n";

echo "=== HD Avatar Examples Complete ===\n";
echo "All examples have been demonstrated successfully!\n";
echo "Remember to configure your Laravel storage and cache settings for optimal performance.\n";
