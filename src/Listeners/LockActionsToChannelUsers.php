<?php

namespace TelegramBotEssentials\Settings\Listeners;

use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Essence\Events\BotUpdateReceived;
use TelegramBotEssentials\Essence\Telegram\TelegramResponse;
use TelegramBotEssentials\Settings\Services\ChannelMembership;
use TelegramBotEssentials\Settings\Telegram\CallbackQueries\Member\ChannelLockQuery;

class LockActionsToChannelUsers
{
    public function handle(BotUpdateReceived $event): void
    {
        if (hasAccess() || ! settings()->get('channel_lock.status')) {
            return;
        }

        $channelId = settings()->get('channel_lock.channel_id');
        $channelId = is_string($channelId) ? ltrim($channelId, '@') : '';
        if (! $channelId) {
            adminAlert('settings.channel_lock.no_channel', fn () => __('tbe-settings::bot_settings.channel_lock.alerts.no_channel'));

            return;
        }

        dependsOn(
            app(ChannelMembership::class)->isMember($channelId),
            new TelegramResponse(
                text: __('tbe-settings::bot_settings.channel_lock.prompt'),
                replyMarkup: Keyboard::make()
                    ->inline()
                    ->row([
                        Keyboard::inlineButton([
                            'text' => __('tbe-settings::bot_settings.channel_lock.buttons.join'),
                            'url' => 'https://t.me/'.$channelId,
                        ]),
                    ])
                    ->row([
                        Keyboard::inlineButton([
                            'text' => __('tbe-settings::bot_settings.channel_lock.buttons.confirm'),
                            'callback_data' => encodeCallback(ChannelLockQuery::TYPE, 'checkMembership'),
                        ]),
                    ])
            ));
    }
}
