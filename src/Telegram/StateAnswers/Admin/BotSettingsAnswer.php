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
                    'text' => __('tbe-settings::bot_settings.messages.updated', ['label' => $setting->getLabel()]),
                    'reply_markup' => wHook()->user()->getKeyboard()
                ]);
                break;
        }

        BotSettingsFeature::menu($this->extractDepthFromKey($key))->send();
    }

    // TODO: it can improved by adding support of different cancellation keys, so we will be able to cancel or delete the setting by choosing the right key
    function cancel(): void
    {
        $key = $this->params['key'] ?? null;
        if($key){
            settings()->set($key, null);
        }

        BotSettingsFeature::menu($this->extractDepthFromKey($key))->send();
    }

    private function extractDepthFromKey(?string $key): ?string
    {
        if (!$key || !str_contains($key, '.')) {
            return null;
        }

        $segments = explode('.', $key);
        array_pop($segments);

        return implode('.', $segments);
    }
}
