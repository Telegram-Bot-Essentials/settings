<?php

namespace TelegramBotEssentials\Settings\Services;

use Illuminate\Support\Facades\Cache;

class ChannelMembership
{
    public const JOINED_STATUSES = ['creator', 'administrator', 'member'];

    public function isMember(string $channelId): bool
    {
        $cacheKey = $this->cacheKey($channelId);

        if (Cache::get($cacheKey)) {
            return true;
        }

        try {
            $chatMember = wHook()->api()->getChatMember([
                'chat_id' => '@' . $channelId,
                'user_id' => wHook()->user()->telegram_user_peer_id,
            ]);
        } catch (\Exception $e) {
            tbeLog('settings')->warning('Channel lock: getChatMember failed', [
                'channel_id' => $channelId,
                'exception' => $e,
            ]);

            return false;
        }

        $joined = in_array($chatMember->status, self::JOINED_STATUSES);

        if ($joined) {
            Cache::put($cacheKey, true, now()->addDay());
        }

        return $joined;
    }

    private function cacheKey(string $channelId): string
    {
        return 'channel_membership:' . wHook()->bot()->id . ':' . $channelId . ':' . wHook()->user()->telegram_user_peer_id;
    }
}
