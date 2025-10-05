<?php

namespace TelegramBotEssentials\Settings\Telegram\CallbackQueries\Admin;

use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Models\MessageMeta;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQuery;
use TelegramBotEssentials\Settings\Enums\SettingType;
use TelegramBotEssentials\Settings\Telegram\Features\Admin\BotSettingsFeature;

class BotSettingsQuery extends CallbackQuery
{
    protected string $type = 'BTSTNG';
    protected int $perm = Roles::ADMIN->value;

    public function update(string $key): void
    {
        $setting = settings()->getSetting($key);

        switch ($setting->type) {
            case SettingType::CHECKBOX:
                settings()->set($key, !settings()->get($key));
                break;
            case SettingType::SENSITIVE:
            case SettingType::NUMBER:
            case SettingType::TEXT:
                wHook()->user()->changeState(encodeAnswerState($this->type, 'update', ["key" => $key]));
                MessageMeta::makeWithCurrentMessage()->deleteMessage();
                wHook()->api()->sendMessage([
                    'chat_id' => wHook()->user()->telegramUser->peer_id,
                    'text' => 'Enter new value for ' . $setting->label . ':',
                    'reply_markup' => wHook()->user()->getKeyboard(),
                ]);
                break;
            default:
                break;
        }

        $tgResponse = $tgResponse ?? BotSettingsFeature::menu();
        $tgResponse->update();
    }
}
