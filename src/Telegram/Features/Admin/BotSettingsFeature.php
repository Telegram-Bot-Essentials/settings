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
        settings()->getSettings()->each(function ($setting) use (&$keys, &$text) {
            $key = self::renderSetting($setting);
            if ($key) {
                $keys[] = $key;
            }
            switch ($setting->type) {

                case SettingType::CHECKBOX:
                    $value = settings()->get($setting->key)
                        ? __('tbe::general.status.enabled') . ' ' . __('tbe::general.status.enabledEmoji')
                        : __('tbe::general.status.disabled') . ' ' . __('tbe::general.status.disabledEmoji');
                    break;
                case SettingType::SENSITIVE:
                    $value = "<tg-spoiler>" . (settings()->get($setting->key) ?? "N/A") . "</tg-spoiler>";
                    break;
                case SettingType::MULTISELECT:
//                    $text .= "<b>{$setting->label}</b>: <i>"
//                    . settings()->get($setting->key) == null ? "N/A" : implode(', ',settings()->get($setting->key) ?? []) . "</i>";
                    $value = 'xxxx';
                    break;
                default:
                    $value = settings()->get($setting->key) ?? "N/A";
                    break;
            }
            $text .= "<b>{$setting->label}</b>: <i>" . $value . "</i>";
            $text .= "\r\n";
        });

//        array_filter($keys);
        addInlineKeysSmartSorted($replyMarkup, $keys, 3);

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }

    public static function renderSetting(Setting $setting): ?Button
    {
        switch ($setting->type) {
            case SettingType::CHECKBOX:
                return Keyboard::inlineButton([
                    'text' => $setting->label . ' ' . (settings()->get($setting->key) ? '✅' : '❌'),
                    'callback_data' => encodeCallback(self::$type, 'update', [$setting->key])
                ]);
            default:
                return Keyboard::inlineButton([
                    'text' => $setting->label . ' ' . $setting->getTypeEmoji(),
                    'callback_data' => encodeCallback(self::$type, 'update', [$setting->key])
                ]);
        }
    }
}
