<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Clip;
use Illuminate\Support\Facades\Storage;

class ConvertVideosToH264 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'videos:convert-to-h264 {--dry-run : Show what would be converted without actually converting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert all HEVC/H.265 videos to H.264 for browser compatibility';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No videos will be converted');
        }
        
        $this->info('🎬 Checking for videos that need conversion...');
        
        // Get all clips
        $clips = Clip::whereNotNull('video_url')->get();
        $this->info("Found {$clips->count()} total clips");
        
        $converted = 0;
        $skipped = 0;
        $failed = 0;
        
        foreach ($clips as $clip) {
            // Extract path from URL
            $videoUrl = $clip->video_url;
            if (!$videoUrl) {
                continue;
            }
            
            // Parse URL to get path
            $parsedUrl = parse_url($videoUrl);
            $urlPath = $parsedUrl['path'] ?? '';
            
            // Convert /storage/clips/file.mp4 to clips/file.mp4
            $relativePath = str_replace('/storage/', '', $urlPath);
            $fullPath = Storage::disk('public')->path($relativePath);
            
            if (!file_exists($fullPath)) {
                $this->warn("⚠️  Video file not found: {$relativePath}");
                $failed++;
                continue;
            }
            
            // Check codec
            $codecCheck = [];
            exec("ffprobe -v error -select_streams v:0 -show_entries stream=codec_name -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($fullPath) . " 2>&1", $codecCheck);
            $currentCodec = trim($codecCheck[0] ?? '');
            
            if ($currentCodec === 'h264') {
                $this->line("✅ Already H.264: {$relativePath}");
                $skipped++;
                continue;
            }
            
            $this->warn("🔄 Needs conversion ({$currentCodec}): {$relativePath}");
            
            if ($isDryRun) {
                $this->info("   Would convert to H.264");
                continue;
            }
            
            // Convert video
            $convertedPath = $this->convertVideo($fullPath, $relativePath);
            
            if ($convertedPath) {
                // Update database with new path
                $newUrl = Storage::disk('public')->url($convertedPath);
                $clip->video_url = $newUrl;
                $clip->save();
                
                $this->info("✅ Converted successfully: {$convertedPath}");
                $converted++;
            } else {
                $this->error("❌ Conversion failed: {$relativePath}");
                $failed++;
            }
        }
        
        $this->newLine();
        $this->info('📊 Summary:');
        $this->info("   ✅ Converted: {$converted}");
        $this->info("   ⏭️  Skipped (already H.264): {$skipped}");
        $this->info("   ❌ Failed: {$failed}");
        
        return 0;
    }
    
    private function convertVideo(string $fullPath, string $relativePath): ?string
    {
        try {
            // Generate new filename
            $pathInfo = pathinfo($relativePath);
            $convertedName = $pathInfo['filename'] . '_h264_' . time() . '.mp4';
            $convertedPath = $pathInfo['dirname'] . '/' . $convertedName;
            $fullConvertedPath = Storage::disk('public')->path($convertedPath);
            
            // Convert to H.264
            $command = sprintf(
                'ffmpeg -i %s -c:v libx264 -preset fast -crf 23 -c:a aac -b:a 128k -movflags +faststart -y %s 2>&1',
                escapeshellarg($fullPath),
                escapeshellarg($fullConvertedPath)
            );
            
            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);
            
            if ($returnVar === 0 && file_exists($fullConvertedPath)) {
                // Delete old file
                unlink($fullPath);
                return $convertedPath;
            }
            
            return null;
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return null;
        }
    }
}
