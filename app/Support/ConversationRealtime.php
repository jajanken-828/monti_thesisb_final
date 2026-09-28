<?php

namespace App\Support;

use App\Models\Eco\ConversationMessage;
use Illuminate\Support\Facades\Cache;

class ConversationRealtime
{
    /**
     * Cache key for "<side> is typing" heartbeat.
     * $side is 'eco' or 'client'.
     */
    public static function typingKey(int $inquiryId, string $side): string
    {
        return "inquiry:{$inquiryId}:typing:{$side}";
    }

    public static function markTyping(int $inquiryId, string $side): void
    {
        Cache::put(self::typingKey($inquiryId, $side), true, now()->addSeconds(6));
    }

    public static function isTyping(int $inquiryId, string $side): bool
    {
        return (bool) Cache::get(self::typingKey($inquiryId, $side), false);
    }

    public static function clearTyping(int $inquiryId, string $side): void
    {
        Cache::forget(self::typingKey($inquiryId, $side));
    }

    /**
     * Fetch messages for polling. Returns only rows newer than $afterId
     * so the frontend can append without a full reload.
     */
    public static function feed(int $inquiryId, int $afterId = 0)
    {
        return ConversationMessage::with('attachments')
            ->where('inquiry_id', $inquiryId)
            ->when($afterId > 0, fn ($q) => $q->where('id', '>', $afterId))
            ->orderBy('id')
            ->get();
    }
}
