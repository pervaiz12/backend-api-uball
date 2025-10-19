<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\MessageResource;
use App\Notifications\MessageReceived;
use App\Events\MessageReceived as MessageReceivedEvent;

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
            'body' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,txt'],
        ], [
            'receiver_id.not_in' => 'You cannot send a message to yourself.',
        ]);

        // Ensure at least one of body or attachment is provided
        $bodyInput = (string) $request->input('body', '');
        if ((trim($bodyInput) === '') && !$request->hasFile('attachment')) {
            return response()->json([
                'message' => 'Message body or an attachment is required.'
            ], 422);
        }

        $data = [
            'sender_id' => $auth->id,
            'receiver_id' => (int) $validated['receiver_id'],
            'body' => $bodyInput,
        ];

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            // Store on the public disk to ensure it's served via /storage
            $storedRelativePath = \Illuminate\Support\Facades\Storage::disk('public')->putFile('messages', $file, 'public');
            // This returns e.g. 'messages/filename.ext'
            $data['attachment_path'] = $storedRelativePath;
            $data['attachment_type'] = $file->getMimeType();
            $data['attachment_name'] = $file->getClientOriginalName();
            $data['attachment_size'] = $file->getSize();
        }

        $message = Message::create($data);

        // Notify the receiver via database notification
        $receiver = User::find($validated['receiver_id']);
        if ($receiver) {
            try {
                \Log::info('Creating message notification', [
                    'receiver_id' => $receiver->id,
                    'sender_id' => $message->sender_id,
                    'message_id' => $message->id
                ]);
                $receiver->notify(new MessageReceived($message));
                \Log::info('Message notification created successfully');
            } catch (\Exception $e) {
                \Log::error('Failed to create message notification', ['error' => $e->getMessage()]);
            }
        } else {
            \Log::warning('Receiver not found for message notification', ['receiver_id' => $validated['receiver_id']]);
        }

        // Broadcast real-time notification via Pusher
        try {
            \Log::info('Broadcasting MessageReceived event', [
                'message_id' => $message->id,
                'receiver_id' => $message->receiver_id,
                'sender_id' => $message->sender_id
            ]);
            broadcast(new MessageReceivedEvent($message))->toOthers();
            \Log::info('MessageReceived event broadcast successfully');
        } catch (\Exception $e) {
            \Log::error('Failed to broadcast MessageReceived event', ['error' => $e->getMessage()]);
        }

        return (new MessageResource(
            $message->load(['receiver:id,name,profile_photo', 'sender:id,name,profile_photo'])
        ))->response()->setStatusCode(201);
    }

    public function markConversationRead(int $userId)
    {
        $auth = Auth::user();
        // Mark all unread messages sent by $userId to the current user as read
        \App\Models\Message::where('sender_id', $userId)
            ->where('receiver_id', $auth->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Conversation marked as read']);
    }
}
