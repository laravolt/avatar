<?php

/*
|--------------------------------------------------------------------------
| HD Avatar Configuration
|--------------------------------------------------------------------------
| Configuration for high-definition avatar generation with enhanced
| performance through image export and storage optimization.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | HD Image Settings
    |--------------------------------------------------------------------------
    | Configuration for high-definition avatar generation
    |
    */
    'hd' => [
        // Enable HD mode - when true, uses higher resolution settings
        'enabled' => env('AVATAR_HD_ENABLED', true),

        // HD dimensions (default: 512x512, supports up to 2048x2048)
        'width' => env('AVATAR_HD_WIDTH', 512),
        'height' => env('AVATAR_HD_HEIGHT', 512),

        // HD font size (scales with dimensions)
        'fontSize' => env('AVATAR_HD_FONT_SIZE', 192),

        // Export quality settings
        'quality' => [
            'png' => env('AVATAR_HD_PNG_QUALITY', 95),
            'jpg' => env('AVATAR_HD_JPG_QUALITY', 90),
            'webp' => env('AVATAR_HD_WEBP_QUALITY', 85),
        ],

        // Anti-aliasing for smoother edges
        'antialiasing' => env('AVATAR_HD_ANTIALIASING', true),

        // DPI for high-quality rendering
        'dpi' => env('AVATAR_HD_DPI', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Export and Storage Settings
    |--------------------------------------------------------------------------
    | Configuration for image export and storage optimization
    |
    */
    'export' => [
        // Default export format
        'format' => env('AVATAR_EXPORT_FORMAT', 'png'), // png, jpg, webp

        // Export path (relative to storage/app)
        'path' => env('AVATAR_EXPORT_PATH', 'avatars'),

        // Filename pattern: {name}, {initials}, {hash}, {timestamp}
        'filename_pattern' => env('AVATAR_EXPORT_FILENAME', '{hash}_{timestamp}.{format}'),

        // Enable multiple format export
        'multiple_formats' => env('AVATAR_EXPORT_MULTIPLE', false),

        // Progressive JPEG for better loading
        'progressive_jpeg' => env('AVATAR_PROGRESSIVE_JPEG', true),

        // WebP lossless compression
        'webp_lossless' => env('AVATAR_WEBP_LOSSLESS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance and Caching
    |--------------------------------------------------------------------------
    | Enhanced caching and performance settings for HD avatars
    |
    */
    'performance' => [
        // Enable file-based caching in addition to memory cache
        'file_cache' => env('AVATAR_FILE_CACHE', true),

        // Cache different sizes separately
        'size_based_cache' => env('AVATAR_SIZE_CACHE', true),

        // Preload fonts for better performance
        'preload_fonts' => env('AVATAR_PRELOAD_FONTS', true),

        // Background processing for large batches
        'background_processing' => env('AVATAR_BACKGROUND_PROCESSING', false),

        // Lazy loading support
        'lazy_loading' => env('AVATAR_LAZY_LOADING', true),

        // Compression levels
        'compression' => [
            'png' => env('AVATAR_PNG_COMPRESSION', 6), // 0-9
            'webp' => env('AVATAR_WEBP_COMPRESSION', 80), // 0-100
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Management
    |--------------------------------------------------------------------------
    | Configuration for storage optimization and cleanup
    |
    */
    'storage' => [
        // Automatic cleanup of old files
        'auto_cleanup' => env('AVATAR_AUTO_CLEANUP', true),

        // Maximum age for cached files (in days)
        'max_age_days' => env('AVATAR_MAX_AGE_DAYS', 30),

        // Maximum storage size (in MB, 0 = unlimited)
        'max_storage_mb' => env('AVATAR_MAX_STORAGE_MB', 500),

        // Storage driver (local, s3, etc.)
        'disk' => env('AVATAR_STORAGE_DISK', 'local'),

        // CDN URL for serving images
        'cdn_url' => env('AVATAR_CDN_URL', null),

        // Enable storage metrics
        'metrics' => env('AVATAR_STORAGE_METRICS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | HD Themes
    |--------------------------------------------------------------------------
    | Enhanced themes with HD-specific optimizations
    |
    */
    'hd_themes' => [
        'ultra-hd' => [
            'width' => 1024,
            'height' => 1024,
            'fontSize' => 384,
            'backgrounds' => [
                '#667eea', '#764ba2', '#f093fb', '#f5576c',
                '#4facfe', '#00f2fe', '#43e97b', '#38f9d7',
                '#ffecd2', '#fcb69f', '#a8edea', '#fed6e3',
            ],
            'foregrounds' => ['#FFFFFF'],
            'border' => [
                'size' => 4,
                'color' => 'foreground',
                'radius' => 8,
            ],
        ],
        'retina' => [
            'width' => 512,
            'height' => 512,
            'fontSize' => 192,
            'backgrounds' => [
                '#667eea', '#764ba2', '#f093fb', '#f5576c',
                '#4facfe', '#00f2fe', '#43e97b', '#38f9d7',
            ],
            'foregrounds' => ['#FFFFFF'],
        ],
        'material-hd' => [
            'width' => 384,
            'height' => 384,
            'fontSize' => 144,
            'shape' => 'circle',
            'backgrounds' => [
                '#1976D2', '#388E3C', '#F57C00', '#7B1FA2',
                '#5D4037', '#455A64', '#E64A19', '#00796B',
            ],
            'foregrounds' => ['#FFFFFF'],
            'border' => [
                'size' => 2,
                'color' => 'background',
                'radius' => 0,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Responsive Sizes
    |--------------------------------------------------------------------------
    | Predefined sizes for responsive avatar generation
    |
    */
    'responsive_sizes' => [
        'thumbnail' => ['width' => 64, 'height' => 64, 'fontSize' => 24],
        'small' => ['width' => 128, 'height' => 128, 'fontSize' => 48],
        'medium' => ['width' => 256, 'height' => 256, 'fontSize' => 96],
        'large' => ['width' => 512, 'height' => 512, 'fontSize' => 192],
        'xl' => ['width' => 768, 'height' => 768, 'fontSize' => 288],
        'xxl' => ['width' => 1024, 'height' => 1024, 'fontSize' => 384],
    ],

    /*
    |--------------------------------------------------------------------------
    | Advanced Features
    |--------------------------------------------------------------------------
    | Additional HD avatar features
    |
    */
    'features' => [
        // Generate avatar sprites for animations
        'sprites' => env('AVATAR_SPRITES', false),

        // Generate avatar variations (different colors/styles)
        'variations' => env('AVATAR_VARIATIONS', false),

        // Generate blur placeholder images
        'placeholders' => env('AVATAR_PLACEHOLDERS', true),

        // Generate different aspect ratios
        'aspect_ratios' => env('AVATAR_ASPECT_RATIOS', false),

        // Watermarking support
        'watermark' => [
            'enabled' => env('AVATAR_WATERMARK', false),
            'text' => env('AVATAR_WATERMARK_TEXT', ''),
            'opacity' => env('AVATAR_WATERMARK_OPACITY', 0.3),
            'position' => env('AVATAR_WATERMARK_POSITION', 'bottom-right'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Settings
    |--------------------------------------------------------------------------
    | Configuration for avatar API endpoints
    |
    */
    'api' => [
        // Enable avatar API endpoints
        'enabled' => env('AVATAR_API_ENABLED', true),

        // Rate limiting (requests per minute)
        'rate_limit' => env('AVATAR_API_RATE_LIMIT', 60),

        // Enable CORS for API endpoints
        'cors' => env('AVATAR_API_CORS', true),

        // API authentication
        'auth' => env('AVATAR_API_AUTH', false),

        // Response headers
        'headers' => [
            'Cache-Control' => 'public, max-age=31536000', // 1 year
            'Expires' => gmdate('D, d M Y H:i:s', time() + 31536000).' GMT',
        ],
    ],
];
