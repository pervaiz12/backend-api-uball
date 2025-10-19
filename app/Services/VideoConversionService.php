<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class VideoConversionService
{
    /**
     * Convert uploaded video to browser-compatible MP4 format
     * Uses H.264 codec which is universally supported by browsers
     * 
     * @param string $inputPath Path to the uploaded video file
     * @return string|null Path to the converted video or null on failure
     */
    public function convertToBrowserCompatible(string $inputPath): ?string
    {
        try {
            // Check if FFmpeg is available
            if (!$this->isFFmpegAvailable()) {
                Log::warning('FFmpeg is not available for video conversion');
                return null;
            }

            $fullInputPath = Storage::disk('public')->path($inputPath);
            
            // Check if input file exists
            if (!file_exists($fullInputPath)) {
                Log::error("Input video file not found: {$fullInputPath}");
                return null;
            }

            // Generate output filename
            $pathInfo = pathinfo($inputPath);
            $outputFilename = $pathInfo['filename'] . '_converted_' . time() . '.mp4';
            $outputPath = $pathInfo['dirname'] . '/' . $outputFilename;
            $fullOutputPath = Storage::disk('public')->path($outputPath);

            // Ensure output directory exists
            $outputDir = dirname($fullOutputPath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            // FFmpeg command for browser-compatible conversion
            // - H.264 video codec (libx264) - universally supported
            // - AAC audio codec - universally supported
            // - MP4 container with faststart for web streaming
            // - Reasonable quality settings
            $command = sprintf(
                'ffmpeg -i %s -c:v libx264 -preset medium -crf 23 -c:a aac -b:a 128k -movflags +faststart -pix_fmt yuv420p -y %s 2>&1',
                escapeshellarg($fullInputPath),
                escapeshellarg($fullOutputPath)
            );

            Log::info("Starting video conversion: {$inputPath}");
            
            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);

            if ($returnVar === 0 && file_exists($fullOutputPath)) {
                Log::info("Video conversion successful: {$outputPath}");
                
                // Delete original file to save space
                if (config('app.delete_original_videos', false)) {
                    Storage::disk('public')->delete($inputPath);
                    Log::info("Original video deleted: {$inputPath}");
                }
                
                return $outputPath;
            } else {
                Log::error("Video conversion failed. FFmpeg output: " . implode("\n", $output));
                return null;
            }

        } catch (\Exception $e) {
            Log::error("Video conversion exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Convert video with additional optimization for mobile devices
     * 
     * @param string $inputPath Path to the uploaded video file
     * @return string|null Path to the converted video or null on failure
     */
    public function convertForMobile(string $inputPath): ?string
    {
        try {
            if (!$this->isFFmpegAvailable()) {
                Log::warning('FFmpeg is not available for video conversion');
                return null;
            }

            $fullInputPath = Storage::disk('public')->path($inputPath);
            
            if (!file_exists($fullInputPath)) {
                Log::error("Input video file not found: {$fullInputPath}");
                return null;
            }

            $pathInfo = pathinfo($inputPath);
            $outputFilename = $pathInfo['filename'] . '_mobile_' . time() . '.mp4';
            $outputPath = $pathInfo['dirname'] . '/' . $outputFilename;
            $fullOutputPath = Storage::disk('public')->path($outputPath);

            $outputDir = dirname($fullOutputPath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            // Mobile-optimized settings:
            // - Lower resolution (720p max)
            // - Lower bitrate for faster loading
            // - H.264 baseline profile for maximum compatibility
            $command = sprintf(
                'ffmpeg -i %s -c:v libx264 -profile:v baseline -level 3.0 -preset fast -crf 28 -vf "scale=\'min(1280,iw)\':\'min(720,ih)\':force_original_aspect_ratio=decrease" -c:a aac -b:a 96k -movflags +faststart -pix_fmt yuv420p -y %s 2>&1',
                escapeshellarg($fullInputPath),
                escapeshellarg($fullOutputPath)
            );

            Log::info("Starting mobile video conversion: {$inputPath}");
            
            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);

            if ($returnVar === 0 && file_exists($fullOutputPath)) {
                Log::info("Mobile video conversion successful: {$outputPath}");
                return $outputPath;
            } else {
                Log::error("Mobile video conversion failed. FFmpeg output: " . implode("\n", $output));
                return null;
            }

        } catch (\Exception $e) {
            Log::error("Mobile video conversion exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get video information (duration, resolution, codec, etc.)
     * 
     * @param string $videoPath Path to the video file
     * @return array|null Video information or null on failure
     */
    public function getVideoInfo(string $videoPath): ?array
    {
        try {
            if (!$this->isFFmpegAvailable()) {
                return null;
            }

            $fullPath = Storage::disk('public')->path($videoPath);
            
            if (!file_exists($fullPath)) {
                return null;
            }

            // Use ffprobe to get video information
            $command = sprintf(
                'ffprobe -v quiet -print_format json -show_format -show_streams %s 2>&1',
                escapeshellarg($fullPath)
            );

            $output = [];
            exec($command, $output);
            
            $json = implode('', $output);
            $info = json_decode($json, true);

            if (!$info) {
                return null;
            }

            // Extract useful information
            $videoStream = null;
            foreach ($info['streams'] ?? [] as $stream) {
                if ($stream['codec_type'] === 'video') {
                    $videoStream = $stream;
                    break;
                }
            }

            return [
                'duration' => $info['format']['duration'] ?? null,
                'size' => $info['format']['size'] ?? null,
                'bitrate' => $info['format']['bit_rate'] ?? null,
                'codec' => $videoStream['codec_name'] ?? null,
                'width' => $videoStream['width'] ?? null,
                'height' => $videoStream['height'] ?? null,
                'fps' => $this->calculateFPS($videoStream),
            ];

        } catch (\Exception $e) {
            Log::error("Error getting video info: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if the video is already browser-compatible
     * 
     * @param string $videoPath Path to the video file
     * @return bool True if video is browser-compatible
     */
    public function isBrowserCompatible(string $videoPath): bool
    {
        $info = $this->getVideoInfo($videoPath);
        
        if (!$info) {
            return false;
        }

        // Check if codec is H.264 (browser-compatible)
        $compatibleCodecs = ['h264', 'avc1'];
        $codec = strtolower($info['codec'] ?? '');
        
        return in_array($codec, $compatibleCodecs);
    }

    /**
     * Check if FFmpeg is available on the system
     * 
     * @return bool True if FFmpeg is available
     */
    private function isFFmpegAvailable(): bool
    {
        static $available = null;
        
        if ($available !== null) {
            return $available;
        }

        $output = [];
        $returnVar = 0;
        exec('ffmpeg -version 2>&1', $output, $returnVar);
        
        $available = $returnVar === 0;
        return $available;
    }

    /**
     * Calculate FPS from video stream data
     * 
     * @param array|null $stream Video stream data
     * @return float|null FPS value or null
     */
    private function calculateFPS(?array $stream): ?float
    {
        if (!$stream) {
            return null;
        }

        // Try to get FPS from r_frame_rate
        if (isset($stream['r_frame_rate'])) {
            $parts = explode('/', $stream['r_frame_rate']);
            if (count($parts) === 2 && $parts[1] > 0) {
                return round($parts[0] / $parts[1], 2);
            }
        }

        // Try to get FPS from avg_frame_rate
        if (isset($stream['avg_frame_rate'])) {
            $parts = explode('/', $stream['avg_frame_rate']);
            if (count($parts) === 2 && $parts[1] > 0) {
                return round($parts[0] / $parts[1], 2);
            }
        }

        return null;
    }

    /**
     * Generate multiple quality versions of a video
     * Useful for adaptive streaming
     * 
     * @param string $inputPath Path to the uploaded video file
     * @return array Array of converted video paths
     */
    public function generateMultipleQualities(string $inputPath): array
    {
        $versions = [];

        // High quality (1080p)
        $highQuality = $this->convertToQuality($inputPath, '1080p', 23);
        if ($highQuality) {
            $versions['1080p'] = $highQuality;
        }

        // Medium quality (720p)
        $mediumQuality = $this->convertToQuality($inputPath, '720p', 25);
        if ($mediumQuality) {
            $versions['720p'] = $mediumQuality;
        }

        // Low quality (480p)
        $lowQuality = $this->convertToQuality($inputPath, '480p', 28);
        if ($lowQuality) {
            $versions['480p'] = $lowQuality;
        }

        return $versions;
    }

    /**
     * Convert video to specific quality
     * 
     * @param string $inputPath Input video path
     * @param string $quality Quality label (1080p, 720p, 480p)
     * @param int $crf CRF value for quality (lower = better quality)
     * @return string|null Path to converted video
     */
    private function convertToQuality(string $inputPath, string $quality, int $crf): ?string
    {
        try {
            if (!$this->isFFmpegAvailable()) {
                return null;
            }

            $fullInputPath = Storage::disk('public')->path($inputPath);
            
            if (!file_exists($fullInputPath)) {
                return null;
            }

            $pathInfo = pathinfo($inputPath);
            $outputFilename = $pathInfo['filename'] . "_{$quality}_" . time() . '.mp4';
            $outputPath = $pathInfo['dirname'] . '/' . $outputFilename;
            $fullOutputPath = Storage::disk('public')->path($outputPath);

            // Resolution mapping
            $resolutions = [
                '1080p' => '1920:1080',
                '720p' => '1280:720',
                '480p' => '854:480',
            ];

            $resolution = $resolutions[$quality] ?? '1280:720';

            $command = sprintf(
                'ffmpeg -i %s -c:v libx264 -preset medium -crf %d -vf "scale=%s:force_original_aspect_ratio=decrease" -c:a aac -b:a 128k -movflags +faststart -pix_fmt yuv420p -y %s 2>&1',
                escapeshellarg($fullInputPath),
                $crf,
                $resolution,
                escapeshellarg($fullOutputPath)
            );

            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);

            if ($returnVar === 0 && file_exists($fullOutputPath)) {
                Log::info("Video converted to {$quality}: {$outputPath}");
                return $outputPath;
            }

            return null;

        } catch (\Exception $e) {
            Log::error("Quality conversion exception: " . $e->getMessage());
            return null;
        }
    }
}
