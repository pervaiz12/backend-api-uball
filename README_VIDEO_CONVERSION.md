# Video Conversion Service - Setup Guide

## Overview
This service automatically converts uploaded videos to browser-compatible MP4 format using H.264 codec and AAC audio.

## Requirements

### 1. Install FFmpeg
FFmpeg is required for video conversion. Install it on your server:

**Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install ffmpeg
```

**macOS:**
```bash
brew install ffmpeg
```

**Windows:**
Download from https://ffmpeg.org/download.html and add to PATH

### 2. Verify Installation
```bash
ffmpeg -version
```

## How It Works

### Automatic Conversion
When a video is uploaded via `ClipController::upload()`:

1. Video is uploaded to `storage/app/public/clips/`
2. `VideoConversionService` automatically converts it to browser-compatible format
3. Converted video uses H.264 codec (universally supported by all browsers)
4. Original video is kept (can be deleted via config)
5. Database stores the converted video URL

### Conversion Settings

**Standard Conversion:**
- Codec: H.264 (libx264)
- Audio: AAC 128kbps
- Quality: CRF 23 (good quality)
- Container: MP4 with faststart (web streaming optimized)
- Pixel Format: yuv420p (maximum compatibility)

**Mobile Conversion:**
- Max Resolution: 720p
- Profile: H.264 Baseline (maximum device compatibility)
- Lower bitrate for faster loading
- Optimized for mobile networks

## Usage Examples

### Basic Usage (Already Integrated)
The service is automatically used in `ClipController::upload()`. No additional code needed.

### Manual Conversion
```php
use App\Services\VideoConversionService;

$service = new VideoConversionService();

// Convert to browser-compatible format
$convertedPath = $service->convertToBrowserCompatible('clips/video.mp4');

// Convert for mobile devices
$mobilePath = $service->convertForMobile('clips/video.mp4');

// Get video information
$info = $service->getVideoInfo('clips/video.mp4');
// Returns: duration, size, bitrate, codec, width, height, fps

// Check if video is already compatible
$isCompatible = $service->isBrowserCompatible('clips/video.mp4');

// Generate multiple quality versions
$versions = $service->generateMultipleQualities('clips/video.mp4');
// Returns: ['1080p' => 'path', '720p' => 'path', '480p' => 'path']
```

## Configuration

### Delete Original Videos
To save storage space, you can configure the service to delete original videos after conversion.

Add to `.env`:
```env
DELETE_ORIGINAL_VIDEOS=true
```

Add to `config/app.php`:
```php
'delete_original_videos' => env('DELETE_ORIGINAL_VIDEOS', false),
```

## Troubleshooting

### Video Not Converting
1. Check if FFmpeg is installed: `ffmpeg -version`
2. Check Laravel logs: `storage/logs/laravel.log`
3. Verify file permissions on storage directory
4. Increase PHP memory limit in `php.ini` or `.env`

### Browser Still Not Playing Video
1. Check browser console for errors
2. Verify video URL is accessible
3. Check CORS headers in `config/cors.php`
4. Test video URL directly in browser
5. Verify video codec with: `ffprobe -v error -show_entries stream=codec_name video.mp4`

### Performance Issues
1. Conversion happens synchronously - consider using queues for large files
2. Increase PHP `max_execution_time` for large videos
3. Consider using Laravel Horizon for queue management

## Queue Integration (Optional)

For better performance with large videos, use Laravel queues:

### 1. Create Job
```bash
php artisan make:job ConvertVideoJob
```

### 2. Job Implementation
```php
<?php

namespace App\Jobs;

use App\Services\VideoConversionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ConvertVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $videoPath,
        public int $clipId
    ) {}

    public function handle(VideoConversionService $service): void
    {
        $convertedPath = $service->convertToBrowserCompatible($this->videoPath);
        
        if ($convertedPath) {
            // Update clip with converted video URL
            \App\Models\Clip::where('id', $this->clipId)
                ->update(['video_url' => \Storage::disk('public')->url($convertedPath)]);
        }
    }
}
```

### 3. Dispatch Job
```php
// In ClipController::upload()
ConvertVideoJob::dispatch($path, $clip->id);
```

## Supported Formats

### Input Formats
- MP4, AVI, MOV, MKV, FLV, WMV, WebM
- Most common video formats

### Output Format
- MP4 with H.264 video and AAC audio
- Universally supported by all modern browsers

## Browser Compatibility

The converted videos work on:
- ✅ Chrome/Edge (all versions)
- ✅ Firefox (all versions)
- ✅ Safari (all versions)
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)
- ✅ Smart TVs and embedded browsers

## Storage Recommendations

1. Use CDN for video delivery (CloudFlare, AWS CloudFront)
2. Enable gzip compression for faster delivery
3. Consider adaptive bitrate streaming for large files
4. Implement video cleanup jobs for old/unused videos

## Monitoring

Check conversion success rate:
```bash
# View logs
tail -f storage/logs/laravel.log | grep "Video conversion"

# Successful conversions
grep "Video converted successfully" storage/logs/laravel.log | wc -l

# Failed conversions
grep "Video conversion failed" storage/logs/laravel.log | wc -l
```

## Support

For issues or questions:
1. Check Laravel logs
2. Verify FFmpeg installation
3. Test FFmpeg command manually
4. Check file permissions
