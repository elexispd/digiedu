<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (string) $user->id === (string) $id;
});

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    // Only allow if user is a participant of the conversation
    return \App\Models\Conversation::where('id', $conversationId)
        ->where(function ($query) use ($user) {
            $query->where('participant_one_id', $user->id)
                  ->orWhere('participant_two_id', $user->id);
        })->exists();
});
