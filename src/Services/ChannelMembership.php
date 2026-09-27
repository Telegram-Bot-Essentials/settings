<?php

namespace TelegramBotEssentials\Settings\Services;

use Illuminate\Support\Facades\Cache;

class ChannelMembership
{
    public const JOINED_STATUSES = ['creator', 'administrator', 'member'];

    /**
     * Whether the current user has joined the channel. When the bot cannot
     * tell (it was removed from the channel, lost its admin rights, the
     * channel is gone) the lock fails open and the owner and admins are
     * alerted: a broken setting must not shut every member out of the bot.
     */
    public function isMember(string $channelId): bool
    {
        $cacheKey = $this->cacheKey($channelId);

        if (Cache::get($cacheKey)) {
            return true;
        }

        try {
            $chatMember = wHook()->api()->getChatMember([
                'chat_id' => '@'.$channelId,
                'user_id' => wHook()->user()->telegram_user_peer_id,
            ]);
        } catch (\Exception $e) {
            adminAlert('settings.channel_lock.check_failed', fn () => __('tbe-settings::bot_settings.channel_lock.alerts.check_failed', [
                'channel' => '@'.$channelId,
                'error' => $e->getMessage(),
            ]));

            return true;
        }

        $joined = in_array($chatMember->status, self::JOINED_STATUSES);

        if ($joined) {
            Cache::put($cacheKey, true, now()->addDay());
        }

        return $joined;
    }

    public function isBotAdmin(string $channelId): bool
    {
        $cacheKey = $this->botAdminCacheKey($channelId);

        if (Cache::get($cacheKey)) {
            return true;
        }

        try {
            $chatMember = wHook()->api()->getChatMember([
                'chat_id' => '@'.$channelId,
                'user_id' => wHook()->api()->getMe()->id,
            ]);
        } catch (\Exception) {
            return false;
        }

        $isAdmin = $chatMember->status === 'administrator';

        if ($isAdmin) {
            Cache::put($cacheKey, true, now()->addDay());
        }

        return $isAdmin;
    }

    private function cacheKey(string $channelId): string
    {
        return 'channel_membership:'.wHook()->bot()->id.':'.$channelId.':'.wHook()->user()->telegram_user_peer_id;
    }

    private function botAdminCacheKey(string $channelId): string
    {
        return 'channel_bot_admin:'.wHook()->bot()->id.':'.$channelId;
    }
}
