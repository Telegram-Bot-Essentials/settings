<?php

namespace TelegramBotEssentials\Settings\Listeners;

use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Essence\Events\BotUpdateReceived;
use TelegramBotEssentials\Essence\Telegram\TelegramResponse;
use TelegramBotEssentials\Settings\Telegram\CallbackQueries\Member\ChannelLockQuery;

class LockActionsToChannelUsers
{
    public function handle(BotUpdateReceived $event): void
    {
        if (hasAccess()) return;
        if (!settings()->get('channel_lock.status')) return;
        $channelId = ltrim(settings()->get('channel_lock.channel_id'), '@');
        if (!$channelId) return;

        try {
            $chatMember = wHook()->api()->getChatMember([
                'chat_id' => '@' . $channelId,
                'user_id' => wHook()->user()->telegram_user_peer_id,
            ]);
        } catch (\Exception) {
            return;
        }

        dependsOn(
            in_array($chatMember->status, ChannelLockQuery::JOINED_STATUSES),
            new TelegramResponse(
                text: __('tbe-settings::bot_settings.channel_lock.prompt'),
                replyMarkup: Keyboard::make()
                    ->inline()
                    ->row([
                        Keyboard::inlineButton([
                            'text' => __('tbe-settings::bot_settings.channel_lock.buttons.join'),
                            'url' => 'https://t.me/' . $channelId,
                        ])
                    ])
                    ->row([
                        Keyboard::inlineButton([
                            'text' => __('tbe-settings::bot_settings.channel_lock.buttons.confirm'),
                            'callback_data' => encodeCallback(ChannelLockQuery::TYPE, 'checkMembership'),
                        ])
                    ])
            ));
    }
}
