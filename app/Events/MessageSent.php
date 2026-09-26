<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $conversationId;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message)
    {
        // We load the sender and conversation relations so the payload and recipient channel are populated
        $this->message = $message->load(['sender:id,name', 'conversation']);
        $this->conversationId = $message->conversation_id;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('conversation.' . $this->conversationId),
        ];

        if ($this->message->conversation) {
            $conv = $this->message->conversation;
            $recipientId = ($conv->participant_one_id === $this->message->sender_id)
                ? $conv->participant_two_id
                : $conv->participant_one_id;

            if ($recipientId) {
                $channels[] = new PrivateChannel('users.' . $recipientId);
            }
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'MessageSent';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->conversationId,
            'body' => $this->message->body,
            'sender_id' => $this->message->sender_id,
            'sender_name' => $this->message->sender?->name,
            'created_at' => $this->message->created_at,
        ];
    }
}
