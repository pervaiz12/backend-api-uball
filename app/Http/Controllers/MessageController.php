<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\MessageResource;

class MessageController extends Controller
{
    public function index()
    {
        $auth = Auth::user();
        $inbox = Message::with('sender:id,name,profile_photo')
            ->where('receiver_id', $auth->id)
            ->latest()->get();
        $sent = Message::with('receiver:id,name,profile_photo')
            ->where('sender_id', $auth->id)
            ->latest()->get();
        return response()->json([
            'inbox' => MessageResource::collection($inbox),
            'sent' => MessageResource::collection($sent),
        ]);
    }

    public function conversation($userId)
    {
        $auth = Auth::user();
        
        // Get all messages between current user and specified user
        $messages = Message::with(['sender:id,name,profile_photo', 'receiver:id,name,profile_photo'])
            ->where(function ($query) use ($auth, $userId) {
                $query->where('sender_id', $auth->id)
                      ->where('receiver_id', $userId);
            })
            ->orWhere(function ($query) use ($auth, $userId) {
                $query->where('sender_id', $userId)
                      ->where('receiver_id', $auth->id);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        return MessageResource::collection($messages);
    }

    public function send(Request $request)
    {
        $auth = Auth::user();

        // Validate input and ensure the receiver is not the authenticated user
        $validated = $request->validate([
            'receiver_id' => [
                'required',
                'integer',
                'exists:users,id',
                // Prevent sending a message to self
                'not_in:' . $auth->id,
            ],
            'body' => ['required', 'string', 'max:2000'],
        ], [
            'receiver_id.not_in' => 'You cannot send a message to yourself.',
        ]);

        $message = Message::create([
            'sender_id' => $auth->id,
            'receiver_id' => (int) $validated['receiver_id'],
            'body' => $validated['body'],
        ]);

        return (new MessageResource(
            $message->load(['receiver:id,name,profile_photo', 'sender:id,name,profile_photo'])
        ))->response()->setStatusCode(201);
    }
}
