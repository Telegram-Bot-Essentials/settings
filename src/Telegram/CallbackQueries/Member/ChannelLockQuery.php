<?php

namespace TelegramBotEssentials\Settings\Telegram\CallbackQueries\Member;

use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Models\MessageMeta;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQuery;
use TelegramBotEssentials\Settings\Services\ChannelMembership;

class ChannelLockQuery extends CallbackQuery
{
    public const TYPE = 'CHLOCK';

    protected string $type = self::TYPE;
    protected int $perm = Roles::MEMBER->value;

    public function checkMembership(): void
    {
        $channelId = ltrim(settings()->get('channel_lock.channel_id'), '@');

        if (!app(ChannelMembership::class)->isMember($channelId)) {
            tbeLog('settings')->debug('Channel lock: re-check still not joined', [
                'channel_id' => $channelId,
            ]);
            $this->answer(__('tbe-settings::bot_settings.messages.channel_lock.not_joined'));
            return;
        }

        tbeLog('settings')->info('Channel lock: membership confirmed', [
            'channel_id' => $channelId,
        ]);

        MessageMeta::makeWithCurrentMessage()->deleteMessage();
        $this->answer(__('tbe-settings::bot_settings.messages.channel_lock.joined'));
    }
}
