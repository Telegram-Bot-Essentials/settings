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

    public static function menu(?string $depth = null): TelegramResponse
    {
        if ($depth === '') {
            $depth = null;
        }

        $text = __('tbe-settings::bot_settings.menu.title');

        $replyMarkup = Keyboard::make()
            ->inline();

        if (settings()->getSettings()->isEmpty()) {
            return new TelegramResponse(
                text: __('tbe-settings::bot_settings.menu.empty'),
            );
        }

        $notSet = __('tbe::general.status.notSet');
        $NA = "<u>{$notSet}</u>";

        $text .= "\r\n";
        $text .= "\r\n";

        $depthSegments = $depth ? explode('.', $depth) : [];

        $settingsOfDepth = settings()->getSettings()->filter(function ($setting) use ($depthSegments) {
            $keySegments = explode('.', $setting->key);

            if (empty($depthSegments)) {
                return count($keySegments) === 1;
            }

            if (count($keySegments) <= count($depthSegments)) {
                return false;
            }

            if (array_slice($keySegments, 0, count($depthSegments)) !== $depthSegments) {
                return false;
            }

            return count($keySegments) === count($depthSegments) + 1;
        });

        $keys = [];

        $settingsOfDepth->each(function ($setting) use (&$keys, &$text, $NA) {
            $value = null;
            $key = self::renderSettingKey($setting);
            if ($key) {
                $keys[] = $key;
            }
            switch ($setting->type) {
                case SettingType::DIRECTORY:
                    $value = null;
                    break;
                case SettingType::CHECKBOX:
                    $value = settings()->get($setting->key)
                        ? __('tbe::general.status.enabled')
                        : __('tbe::general.status.disabled');
                    break;
                case SettingType::SENSITIVE:
                    $value = settings()->get($setting->key) ? '<tg-spoiler>' . (settings()->get($setting->key)) . '</tg-spoiler>' : $NA;
                    break;
                case SettingType::MULTISELECT:
                    $value = settings()->get($setting->key) ? implode(', ', settings()->get($setting->key) ?? []) : $NA;
                    break;
                case SettingType::SELECT:
                    $currentValue = settings()->get($setting->key);
                    $value = $currentValue
                        ? $setting->getOptionLabel($currentValue)
                        : $NA;
                    break;
                default:
                    $value = settings()->get($setting->key) ?? $NA;
                    break;
            }
            if (isset($value)) {
                $text .= '<b>' . $setting->getLabel() . '</b>: <i>' . $value . '</i>';
                $text .= "\r\n";
            }
        });

        $keys = array_values(array_filter($keys));
        if (! empty($keys)) {
            addInlineKeysSmartSorted($replyMarkup, $keys, 3);
        }

        if ($depth) {
            $replyMarkup->row([
                Keyboard::inlineButton([
                    'text' => __('tbe::general.keys.back'),
                    'callback_data' => encodeCallback(self::$type, 'menu', [self::cropLastDepth($depth)]),
                ]),
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
                    'text' => $setting->getLabel() . ' ' . $setting->getTypeEmoji(),
                    'callback_data' => encodeCallback(self::$type, 'menu', [$setting->key]),
                ]);
            case SettingType::ENUM:
                return Keyboard::inlineButton([
                    'text' => $setting->getTypeEmoji() . ' ' . $setting->getLabel() . ': ' . (settings()->get($setting->key) ?? __('tbe::general.status.notSet')),
                    'callback_data' => encodeCallback(self::$type, 'update', [$setting->key]),
                ]);
            default:
                return Keyboard::inlineButton([
                    'text' => $setting->getLabel() . ' ' . $setting->getTypeEmoji(),
                    'callback_data' => encodeCallback(self::$type, 'update', [$setting->key]),
                ]);
        }
    }

    public static function select(Setting $setting): TelegramResponse
    {
        $text = __('tbe-settings::bot_settings.selectors.no_options');

        $replyMarkup = Keyboard::make()
            ->inline();

        $currentValue = settings()->get($setting->key);
        $options = $setting->getOptions();

        $keys = [];
        foreach ($options as $optionKey => $label) {
            $keys[] = Keyboard::inlineButton([
                'text' => $label . ' ' . ($currentValue == $optionKey ? '✅' : ''),
                'callback_data' => encodeCallback(self::$type, 'select', [$setting->key, $optionKey]),
            ]);
        }
        if (count($keys) > 0) {
            $text = __('tbe-settings::bot_settings.selectors.prompt');
        }
        addInlineKeysSmartSorted($replyMarkup, $keys, 2);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::general.keys.back'),
                'callback_data' => encodeCallback(self::$type, 'menu'),
            ]),
        ]);

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
        );
    }

    public static function multiselect(Setting $setting)
    {
        $text = __('tbe-settings::bot_settings.selectors.no_options');

        $replyMarkup = Keyboard::make()
            ->inline();

        $currentValue = settings()->get($setting->key);
        $options = $setting->getOptions();

        $keys = [];
        foreach ($options as $optionKey => $value) {
            $exist = in_array($value, $currentValue);
            $keys[] = Keyboard::inlineButton([
                'text' => $value . ' ' . ($exist ? '✅' : ''),
                'callback_data' => encodeCallback(self::$type, 'multiSelect', [$setting->key, $optionKey, $exist]),
            ]);
        }
        if (count($keys) > 0) {
            $text = __('tbe-settings::bot_settings.selectors.prompt');
        }
        addInlineKeysSmartSorted($replyMarkup, $keys, 2);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::general.keys.back'),
                'callback_data' => encodeCallback(self::$type, 'menu'),
            ]),
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
