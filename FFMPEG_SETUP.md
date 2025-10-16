# FFmpeg Setup for Video Thumbnail Generation

## Installation Instructions

### macOS (using Homebrew)
```bash
brew install ffmpeg
```

### Ubuntu/Debian
```bash
sudo apt update
sudo apt install ffmpeg
```

### CentOS/RHEL
```bash
sudo yum install epel-release
sudo yum install ffmpeg
```

### Windows
1. Download FFmpeg from https://ffmpeg.org/download.html
2. Extract to C:\ffmpeg
3. Add C:\ffmpeg\bin to your PATH environment variable

### Docker (if using containerized deployment)
Add to your Dockerfile:
```dockerfile
RUN apt-get update && apt-get install -y ffmpeg
```

## Alternative: PHP-FFMpeg Library
If you prefer using a PHP library instead of command-line FFmpeg:

```bash
composer require php-ffmpeg/php-ffmpeg
```

## Verification
Test if FFmpeg is working:
```bash
ffmpeg -version
```

## How It Works
1. When a video is uploaded, the system automatically generates a thumbnail
2. Extracts a frame at the 2-second mark of the video
3. Resizes to 320x240 pixels with proper aspect ratio
4. Saves as JPEG in the `storage/thumbnails/` directory
5. Falls back gracefully if FFmpeg is not available

## Fallback Methods
The system tries multiple methods in order:
1. FFmpeg command-line (best quality, most reliable)
2. PHP-FFMpeg library (good alternative)
3. ImageMagick extension (limited video support)
4. If all fail, no thumbnail is generated (users can upload custom)

## Troubleshooting
- Ensure PHP has exec() function enabled
- Check file permissions for storage directories
- Verify video file formats are supported
- Check Laravel logs for detailed error messages
