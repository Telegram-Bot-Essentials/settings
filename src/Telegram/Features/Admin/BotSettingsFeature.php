<?php

namespace TelegramBotEssentials\Settings\Telegram\Features\Admin;

use TelegramBotEssentials\Essence\Telegram\TelegramResponse;
use Telegram\Bot\Keyboard\Keyboard;
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
        $text = 'test';

        $replyMarkup = Keyboard::make()
            ->inline();

        if(settings()->getSettings()->isEmpty()){
            return new TelegramResponse(
                text: 'No settings found',
            );
        }

        settings()->getSettings()->each(function($setting) use ($replyMarkup){
            self::renderSetting($setting, $replyMarkup);
        });

        debugMessage(json_encode(settings()->getSettings(), JSON_PRETTY_PRINT));

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }

    public static function renderSetting(Setting $setting, Keyboard $replyMarkup): void
    {
        switch ($setting->type) {
            case SettingType::CHECKBOX:
                $replyMarkup->row([
                    Keyboard::inlineButton([
                        'text' => $setting->label . ' ' . (settings()->get($setting->key) ? '✅' : '❌'),
                        'callback_data' => encodeCallback(self::$type, 'update', [$setting->key])
                    ])
                ]);
                break;
            case SettingType::TEXT:
                $replyMarkup->row([
                    Keyboard::inlineButton([
                        'text' => $setting->label,
                        'callback_data' => encodeCallback(self::$type, 'text', [$setting->key])
                    ])
                ]);
                break;
            case SettingType::NUMBER:
                $replyMarkup->row([
                    Keyboard::inlineButton([
                        'text' => $setting->label,
                        'callback_data' => encodeCallback(self::$type, 'number', [$setting->key])
                    ])
                ]);
                break;
            case SettingType::PASSWORD:
                $replyMarkup->row([
                    Keyboard::inlineButton([
                        'text' => $setting->label,
                        'callback_data' => encodeCallback(self::$type, 'password', [$setting->key])
                    ])
                ]);
                break;
            case SettingType::ENUM:
                $replyMarkup->row([
                    Keyboard::inlineButton([
                        'text' => $setting->label,
                        'callback_data' => encodeCallback(self::$type, 'enum', [$setting->key])
                    ])
                ]);
                break;
            case SettingType::SELECT:
                $replyMarkup->row([
                    Keyboard::inlineButton([
                        'text' => $setting->label,
                        'callback_data' => encodeCallback(self::$type, 'select', [$setting->key])
                    ])
                ]);
                break;
            case SettingType::MULTISELECT:
                $replyMarkup->row([
                    Keyboard::inlineButton([
                        'text' => $setting->label,
                        'callback_data' => encodeCallback(self::$type, 'multiselect', [$setting->key])
                    ])
                ]);
                break;
        }
    }
}
