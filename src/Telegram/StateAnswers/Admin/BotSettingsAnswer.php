<?php

namespace TelegramBotEssentials\Settings\Telegram\StateAnswers\Admin;

use Illuminate\Support\Facades\Validator;
use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Telegram\StateAnswers\StateAnswer;
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
            case SettingType::SENSITIVE:
            case SettingType::NUMBER:
            case SettingType::TEXT:
                settings()->set($key, wHook()->update()->message->text);
                wHook()->user()->changeState();
                wHook()->api()->sendMessage([
                    'chat_id' => wHook()->user()->telegramUser->peer_id,
                    'text' => "$key updated successfully",
                    'reply_markup' => wHook()->user()->getKeyboard()
                ]);
                break;
        }

        BotSettingsFeature::menu()->send();
    }
}
