<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Clip;
use App\Models\Like;

echo "🧪 Testing API Response Structure\n";
echo "==================================\n\n";

$currentUserId = 1; // Simulating logged-in user

$clips = Clip::with(['user:id,name,profile_photo,role,is_official,city', 'player:id,name,profile_photo,role,is_official', 'game:id,location,game_date'])
    ->where('status', 'approved')
    ->orderByDesc('created_at')
    ->limit(1)
    ->get();

$clips->transform(function ($clip) use ($currentUserId) {
    $isLiked = Like::where('user_id', $currentUserId)
        ->where('clip_id', $clip->id)
        ->exists();
    
    $clip->is_liked_by_user = $isLiked;
    $clip->content = $clip->description;
    $clip->media_url = $clip->video_url;
    $clip->media_type = 'video';
    
    if ($clip->player) {
        $originalUser = $clip->user;
        $clip->user = $clip->player;
        $clip->uploader = $originalUser;
    }
    
    return $clip;
});

$clip = $clips->first();

if ($clip) {
    echo "📋 API Response for Frontend:\n";
    echo "=============================\n\n";
    
    echo "Clip ID: {$clip->id}\n";
    echo "Title: {$clip->title}\n";
    echo "Description: {$clip->description}\n\n";
    
    echo "🎬 Video & Thumbnail:\n";
    echo "  video_url: {$clip->video_url}\n";
    echo "  thumbnail_url: {$clip->thumbnail_url}\n";
    echo "  media_url: {$clip->media_url}\n";
    echo "  media_type: {$clip->media_type}\n\n";
    
    echo "👤 User (Display Name - Should be Player):\n";
    echo "  ID: {$clip->user->id}\n";
    echo "  Name: {$clip->user->name}\n";
    echo "  Role: {$clip->user->role}\n";
    echo "  Profile Photo: " . ($clip->user->profile_photo ?: 'null') . "\n\n";
    
    if (isset($clip->uploader)) {
        echo "📤 Uploader (Original):\n";
        echo "  ID: {$clip->uploader->id}\n";
        echo "  Name: {$clip->uploader->name}\n";
        echo "  Role: {$clip->uploader->role}\n\n";
    }
    
    echo "📊 Stats:\n";
    echo "  Likes: {$clip->likes_count}\n";
    echo "  Comments: {$clip->comments_count}\n";
    echo "  Views: {$clip->views_count}\n";
    echo "  Liked by current user: " . ($clip->is_liked_by_user ? 'Yes' : 'No') . "\n\n";
    
    echo "✅ Frontend should now display:\n";
    echo "   - Player Name: {$clip->user->name} ✅\n";
    echo "   - Thumbnail: {$clip->thumbnail_url} ✅\n";
    echo "   - Video: {$clip->video_url} ✅\n";
}
