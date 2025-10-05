<?php

namespace TelegramBotEssentials\Settings\Telegram\Features\Admin;

use Telegram\Bot\Keyboard\Button;
use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Essence\Telegram\TelegramResponse;
use TelegramBotEssentials\Settings\DTOs\Setting;
use TelegramBotEssentials\Settings\Enums\SettingType;

class BotSettingsFeature
{
    static string $type = 'BTSTNG';

    /**
     * @return TelegramResponse
     */
    public static function menu(): TelegramResponse
    {
        $text = 'Bot Settings';

        $replyMarkup = Keyboard::make()
            ->inline();

        if (settings()->getSettings()->isEmpty()) {
            return new TelegramResponse(
                text: 'No settings found',
            );
        }

        $text .= "\r\n";
        $text .= "\r\n";
        settings()->getSettings()->each(function ($setting) use (&$keys, $replyMarkup, &$text) {
            $key = self::renderSetting($setting, $replyMarkup);
            if ($key) {
                $keys[] = $key;
            }
            switch ($setting->type) {
                case SettingType::SELECT:
                case SettingType::ENUM:
                case SettingType::NUMBER:
                case SettingType::TEXT:
                    $text .= "<b>{$setting->label}</b>: <i>" . (settings()->get($setting->key) ?? "N/A") . "</i>";
                    break;
                case SettingType::CHECKBOX:
                    $text .= "<b>{$setting->label}</b>: <i>"
                        . (settings()->get($setting->key)
                            ? __('tbe::general.status.enabled') . ' ' . __('tbe::general.status.enabledEmoji')
                            : __('tbe::general.status.disabled') . ' ' . __('tbe::general.status.disabledEmoji')
                        ) . "</i>";
                    break;
                case SettingType::SENSITIVE:
                    $text .= "<b>{$setting->label}</b>: <i><tg-spoiler>" . (settings()->get($setting->key) ?? "N/A") . "</tg-spoiler></i>";
                    break;
                case SettingType::MULTISELECT:
                    $value = settings()->get($setting->key) == null ? "N/A" : json_decode(settings()->get($setting->key));
                    break;
            }
            $text .= "\r\n";
        });

        addInlineKeysSmartSorted($replyMarkup, $keys, 3);

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }

    public static function renderSetting(Setting $setting, Keyboard $replyMarkup): ?Button
    {
        switch ($setting->type) {
            case SettingType::CHECKBOX:
                return Keyboard::inlineButton([
                    'text' => $setting->label . ' ' . (settings()->get($setting->key) ? '✅' : '❌'),
                    'callback_data' => encodeCallback(self::$type, 'update', [$setting->key])
                ]);
            case SettingType::TEXT:
                return Keyboard::inlineButton([
                    'text' => $setting->label . ' ' . "🏷",
                    'callback_data' => encodeCallback(self::$type, 'update', [$setting->key])
                ]);
            case SettingType::NUMBER:
                return Keyboard::inlineButton([
                    'text' => $setting->label . ' ' . "⚖️",
                    'callback_data' => encodeCallback(self::$type, 'update', [$setting->key])
                ]);
            case SettingType::SENSITIVE:
                return Keyboard::inlineButton([
                    'text' => $setting->label . ' ' . '🔑',
                    'callback_data' => encodeCallback(self::$type, 'update', [$setting->key])
                ]);
            case SettingType::ENUM:
                return Keyboard::inlineButton([
                    'text' => $setting->label . ' ' . '🗄',
                    'callback_data' => encodeCallback(self::$type, 'update', [$setting->key])
                ]);
            case SettingType::SELECT:
                return Keyboard::inlineButton([
                    'text' => $setting->label . ' ' . "📁",
                    'callback_data' => encodeCallback(self::$type, 'update', [$setting->key])
                ]);
            case SettingType::MULTISELECT:
                return Keyboard::inlineButton([
                    'text' => $setting->label . ' ' . '🗂',
                    'callback_data' => encodeCallback(self::$type, 'update', [$setting->key])
                ]);
        }
        return null;
    }
}
