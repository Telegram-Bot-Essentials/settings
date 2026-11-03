<?php

namespace TelegramBotEssentials\Settings\Gates;

use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Essence\Contracts\HandlerGate;
use TelegramBotEssentials\Essence\Telegram\Commands\Command;
use TelegramBotEssentials\Essence\Telegram\TelegramResponse;
use TelegramBotEssentials\Settings\Services\ChannelMembership;
use TelegramBotEssentials\Settings\Telegram\CallbackQueries\Member\ChannelLockQuery;

/**
 * Members must join the channel before the bot acts on what they ask of it.
 * Only that: /start and its deep links run regardless, so a referral or a
 * campaign is not lost to someone joining second, and nothing the bot sends by
 * itself waits on it.
 */
class ChannelLockGate implements HandlerGate
{
    public function check(object $handler): void
    {
        if ($this->isExempt($handler) || hasAccess('settings.manage') || ! settings()->get('channel_lock.status')) {
            return;
        }

        $channelId = settings()->get('channel_lock.channel_id');
        $channelId = is_string($channelId) ? ltrim($channelId, '@') : '';
        if (! $channelId) {
            ownerAlert('settings.channel_lock.no_channel', fn () => __('tbe-settings::bot_settings.channel_lock.alerts.no_channel'));

            return;
        }

        dependsOn(
            app(ChannelMembership::class)->isMember($channelId),
            (new TelegramResponse(
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
            ))
                // The button pressed may be a prize waiting to be claimed once they join.
                ->asNewMessage()
                ->inlineQueryButton(__('tbe-settings::bot_settings.channel_lock.buttons.join'), 'channel_lock'),
        );
    }

    private function isExempt(object $handler): bool
    {
        return $handler instanceof ChannelLockQuery
            || ($handler instanceof Command && $handler->getName() === 'start')
            // Only the private chat: in a group this would answer everyone
            // outside the channel. Choosing there is harmless, and paying
            // happens in private, where the lock still applies.
            || wHook()->inGroup();
    }
}
