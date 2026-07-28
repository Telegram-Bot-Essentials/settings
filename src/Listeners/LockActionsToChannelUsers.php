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
        if (hasAccess()) {
            tbeLog('settings')->debug('Channel lock: skipped, user has access');
            return;
        }
        if (!settings()->get('channel_lock.status')) {
            tbeLog('settings')->debug('Channel lock: skipped, feature disabled');
            return;
        }
        $channelId = ltrim(settings()->get('channel_lock.channel_id'), '@');
        if (!$channelId) {
            tbeLog('settings')->warning('Channel lock enabled without a channel_id configured');
            return;
        }

        $isMember = app(ChannelMembership::class)->isMember($channelId);

        if (!$isMember) {
            tbeLog('settings')->debug('Channel lock: blocked user pending channel join', [
                'channel_id' => $channelId,
            ]);
        }

        dependsOn(
            $isMember,
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
