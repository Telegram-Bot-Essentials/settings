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
    public static function menu(?string $depth = null): TelegramResponse
    {
        $text = 'Bot Settings';

        $replyMarkup = Keyboard::make()
            ->inline();

        if (settings()->getSettings()->isEmpty()) {
            return new TelegramResponse(
                text: 'No settings found',
            );
        }

        $NA = "<u>N/A</u>";

        $text .= "\r\n";
        $text .= "\r\n";

        $settingsOfDepth = settings()->getSettings()->filter(function ($setting) use ($depth) {
            return $depth
                ? str_starts_with($setting->key, $depth) && $setting->key != $depth
                : str_starts_with($setting->key, $depth) && !str_contains($setting->key, '.');
        });

        $settingsOfDepth->each(function ($setting) use (&$keys, &$text, $NA) {
            $key = self::renderSettingKey($setting);
            if ($key) {
                $keys[] = $key;
            }
            switch ($setting->type) {
                case SettingType::DIRECTORY:
                    break;
                case SettingType::CHECKBOX:
                    $value = settings()->get($setting->key)
                        ? __('tbe::general.status.enabled')
                        : __('tbe::general.status.disabled');
                    break;
                case SettingType::SENSITIVE:
                    $value = settings()->get($setting->key) ? "<tg-spoiler>" . (settings()->get($setting->key)) . "</tg-spoiler>" : $NA;
                    break;
                case SettingType::MULTISELECT:
                    $value = settings()->get($setting->key) ? implode(', ', settings()->get($setting->key) ?? []) : $NA;
                    break;
                default:
                    $value = settings()->get($setting->key) ?? $NA;
                    break;
            }
            if (isset($value)) {
                $text .= "<b>{$setting->label}</b>: <i>" . $value . "</i>";
                $text .= "\r\n";
            }
        });

        $keys = $keys ?? [];
        array_filter($keys);
        addInlineKeysSmartSorted($replyMarkup, $keys, 3);

        if($depth){
            $replyMarkup->row([
                Keyboard::inlineButton([
                    'text' => __('tbe::general.keys.back'),
                    'callback_data' => encodeCallback(self::$type, 'menu', [self::cropLastDepth($depth)])
                ])
            ]);
        }

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }

    public static function renderSettingKey(Setting $setting): ?Button
    {
        switch ($setting->type) {
            case SettingType::DIRECTORY:
                return Keyboard::inlineButton([
                    'text' => $setting->label . ' ' . $setting->getTypeEmoji(),
                    'callback_data' => encodeCallback(self::$type, 'menu', [$setting->key])
                ]);
            case SettingType::ENUM:
                return Keyboard::inlineButton([
                    'text' => $setting->getTypeEmoji() . ' ' . $setting->label . ': ' . (settings()->get($setting->key) ?? 'N/A'),
                    'callback_data' => encodeCallback(self::$type, 'update', [$setting->key])
                ]);
            default:
                return Keyboard::inlineButton([
                    'text' => $setting->label . ' ' . $setting->getTypeEmoji(),
                    'callback_data' => encodeCallback(self::$type, 'update', [$setting->key])
                ]);
        }
    }

    public static function select(Setting $setting): TelegramResponse
    {
        $text = 'No Options found';

        $replyMarkup = Keyboard::make()
            ->inline();

        $currentValue = settings()->get($setting->key);

        $keys = [];
        foreach ($setting->options as $optionKey => $value) {
            $keys[] = Keyboard::inlineButton([
                'text' => $value . ' ' . ($currentValue == $value ? '✅' : ''),
                'callback_data' => encodeCallback(self::$type, 'select', [$setting->key, $optionKey])
            ]);
        }
        if (count($keys) > 0) $text = 'Select the option:';
        addInlineKeysSmartSorted($replyMarkup, $keys, 2);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::general.keys.back'),
                'callback_data' => encodeCallback(self::$type, 'menu')
            ])
        ]);

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
        );
    }

    public static function multiselect(Setting $setting)
    {
        $text = 'No Options found';

        $replyMarkup = Keyboard::make()
            ->inline();

        $currentValue = settings()->get($setting->key);

        $keys = [];
        foreach ($setting->options as $optionKey => $value) {
            $exist = in_array($value, $currentValue);
            $keys[] = Keyboard::inlineButton([
                'text' => $value . ' ' . ($exist ? '✅' : ''),
                'callback_data' => encodeCallback(self::$type, 'multiSelect', [$setting->key, $optionKey, $exist])
            ]);
        }
        if (count($keys) > 0) $text = 'Select the option:';
        addInlineKeysSmartSorted($replyMarkup, $keys, 2);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::general.keys.back'),
                'callback_data' => encodeCallback(self::$type, 'menu')
            ])
        ]);

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
        );
    }

    private static function cropLastDepth(string $depth): string
    {
        $data = explode('.', $depth);
        array_pop($data);
        return implode('.', $data);
    }
}
