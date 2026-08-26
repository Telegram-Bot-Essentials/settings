<?php

namespace TelegramBotEssentials\Settings\Telegram\CallbackQueries\Admin;

use Illuminate\Support\Facades\App;
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

        $this->answer(__('tbe-settings::bot_settings.messages.editing', ['label' => $setting->getLabel()]));
        switch ($setting->type) {
            case SettingType::CHECKBOX:
                settings()->set($key, ! settings()->get($key));
                break;
            case SettingType::SENSITIVE:
            case SettingType::NUMBER:
            case SettingType::TEXT:
                wHook()->user()->changeState(encodeAnswerState($this->type, 'update', ['key' => $key]));
                MessageMeta::makeWithCurrentMessage()->deleteMessage();
                $text = $setting->getDescription() !== null
                    ? '<i>'.$setting->getDescription().'</i>'."\r\n\r\n".__('tbe-settings::bot_settings.prompts.enter_new_value', ['label' => $setting->getLabel()])
                    : __('tbe-settings::bot_settings.prompts.enter_new_value', ['label' => $setting->getLabel()]);
                wHook()->api()->sendMessage([
                    'chat_id' => wHook()->user()->telegramUser->peer_id,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                    'reply_markup' => wHook()->user()->getKeyboard(),
                ]);

                return;
            case SettingType::ENUM:
                $x = nextInArray($setting->getOptions() ?? [], settings()->get($key));
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
        settings()->set($key, $optionKey);

        if ($key === 'locale') {
            App::setLocale($optionKey);
        }

        BotSettingsFeature::menu()->update();
    }

    public function multiSelect(string $key, string $optionKey, bool $remove): void
    {
        $setting = settings()->getSetting($key);
        $result = settings()->get($key);
        if ($remove) {
            $result = array_filter($result, fn ($value) => $value != ($setting->getOptions()[$optionKey] ?? null));
        } else {
            $result[] = $setting->getOptions()[$optionKey] ?? null;
        }
        settings()->set($key, $result);
        BotSettingsFeature::multiselect($setting)->update();
    }
}
