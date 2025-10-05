<?php

namespace TelegramBotEssentials\Settings\Telegram\StateAnswers\Admin;

use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Telegram\StateAnswers\StateAnswer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBotEssentials\Settings\Enums\SettingType;
use TelegramBotEssentials\Settings\Telegram\Features\Admin\BotSettingsFeature;

class BotSettingsAnswer extends StateAnswer
{
    protected string $type = 'BTSTNG';
    protected int $perm = Roles::ADMIN->value;

    function update(string $key): void
    {
        $setting = settings()->getSetting($key);

        switch ($setting->type) {
            case SettingType::TEXT:
                settings()->set($key, wHook()->update()->message->text);
                break;
        }
    }
}
