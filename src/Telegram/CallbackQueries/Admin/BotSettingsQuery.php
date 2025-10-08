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

    public function menu(?string $depth = null): void
    {
        BotSettingsFeature::menu($depth)->update();
    }

    public function update(string $key): void
    {
        $setting = settings()->getSetting($key);

        $this->answer("Editing $setting->label...");
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
                return;
            case SettingType::ENUM:
                $x = nextInArray($setting->options ?? [], settings()->get($key));
                settings()->set($key, $x);
                break;
            case SettingType::SELECT:
                $tgResponse = BotSettingsFeature::select($setting);
                break;
            case SettingType::MULTISELECT:
                $tgResponse = BotSettingsFeature::multiselect($setting);
                break;
        }

        $depth = explode('.', $key);
        array_pop($depth);
        $depth = implode('.', $depth);

        $tgResponse = $tgResponse ?? BotSettingsFeature::menu($depth);
        $tgResponse->update();
    }

    public function select(string $key, string $optionKey): void
    {
        $setting = settings()->getSetting($key);
        settings()->set($key, $setting->options[$optionKey] ?? []);
        BotSettingsFeature::menu()->update();
    }

    public function multiSelect(string $key, string $optionKey, bool $remove): void
    {
        $setting = settings()->getSetting($key);
        $result = settings()->get($key);
        if($remove){
            $result = array_filter($result, fn($value) => $value != $setting->options[$optionKey] ?? null);
        }else{
            $result[] = $setting->options[$optionKey] ?? null;
        }
        settings()->set($key, $result);
        BotSettingsFeature::multiselect($setting)->update();
    }
}
