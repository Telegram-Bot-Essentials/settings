<?php

namespace TelegramBotEssentials\Settings\Telegram\CallbackQueries\Member;

use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Models\MessageMeta;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQuery;

class ChannelLockQuery extends CallbackQuery
{
    public const TYPE = 'CHLOCK';
    public const JOINED_STATUSES = ['creator', 'administrator', 'member'];

    protected string $type = self::TYPE;
    protected int $perm = Roles::MEMBER->value;

    public function checkMembership(): void
    {
        $channelId = ltrim(settings()->get('channel_lock.channel_id'), '@');

        try {
            $chatMember = wHook()->api()->getChatMember([
                'chat_id' => '@' . $channelId,
                'user_id' => wHook()->user()->telegram_user_peer_id,
            ]);
        } catch (\Exception) {
            $this->answer(__('tbe-settings::bot_settings.messages.channel_lock.not_joined'));
            return;
        }

        if (!in_array($chatMember->status, self::JOINED_STATUSES)) {
            $this->answer(__('tbe-settings::bot_settings.messages.channel_lock.not_joined'));
            return;
        }

        MessageMeta::makeWithCurrentMessage()->deleteMessage();
        $this->answer(__('tbe-settings::bot_settings.messages.channel_lock.joined'));
    }
}
