<?php

namespace TelegramBotEssentials\Settings\Telegram\ReplyKeys\Admin;

use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKey;
use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBotEssentials\Settings\Telegram\Features\Admin\BotSettingsFeature;

class BotSettingsKey extends ReplyKey
{
    protected string $text;
    protected int $perm = Roles::ADMIN->value;

    public function __construct()
    {
        $this->text = __('tbe::bot_settings.reply_key');
    }

    /**
     * @throws TelegramSDKException
     */
    public function handle(): void
    {
        BotSettingsFeature::menu()->send();
    }
}
