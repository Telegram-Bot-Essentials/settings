<?php

namespace TelegramBotEssentials\Settings\Telegram\ReplyKeys\Admin;

use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKey;
use TelegramBotEssentials\Settings\Telegram\Features\Admin\BotSettingsFeature;

class BotSettingsKey extends ReplyKey
{
    protected ?string $ability = 'settings.manage';

    protected function text(): string
    {
        return __('tbe::bot_settings.reply_key');
    }

    /**
     * @throws TelegramSDKException
     */
    public function handle(): void
    {
        BotSettingsFeature::menu()->send();
    }
}
