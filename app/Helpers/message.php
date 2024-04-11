<?php
function getCacheMessageData($objectType, $chatId, $tag): array
{
    $messageCache = Cache::tags($tag . '.' . $chatId)->get($objectType);
    $messageId = $messageCache['message_id'] ?? '';
    $messageText = $messageCache[$objectType]['text'] ?? $messageCache['caption'] ?? '';
    return [$messageCache, $messageId, $messageText];
}
