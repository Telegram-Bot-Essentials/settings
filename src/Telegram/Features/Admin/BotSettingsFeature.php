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
            $text .= "<b>{$setting->label}</b>: <i>" . (settings()->get($setting->key) ?? "N/A") . "</i>";
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
                    'text' => $setting->label . ' ' . "✏",
                    'callback_data' => encodeCallback(self::$type, 'update', [$setting->key])
                ]);
            case SettingType::NUMBER:
                return Keyboard::inlineButton([
                    'text' => $setting->label,
                    'callback_data' => encodeCallback(self::$type, 'update', [$setting->key])
                ]);
            case SettingType::PASSWORD:
                return Keyboard::inlineButton([
                    'text' => $setting->label,
                    'callback_data' => encodeCallback(self::$type, 'update', [$setting->key])
                ]);
            case SettingType::ENUM:
                return Keyboard::inlineButton([
                    'text' => $setting->label,
                    'callback_data' => encodeCallback(self::$type, 'update', [$setting->key])
                ]);
            case SettingType::SELECT:
                return Keyboard::inlineButton([
                    'text' => $setting->label,
                    'callback_data' => encodeCallback(self::$type, 'update', [$setting->key])
                ]);
            case SettingType::MULTISELECT:
                return Keyboard::inlineButton([
                    'text' => $setting->label,
                    'callback_data' => encodeCallback(self::$type, 'update', [$setting->key])
                ]);
        }
        return null;
    }
}
