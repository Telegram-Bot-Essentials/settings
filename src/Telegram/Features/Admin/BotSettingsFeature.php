<?php

namespace TelegramBotEssentials\Settings\Telegram\Features\Admin;

use TelegramBotEssentials\Essence\Telegram\TelegramResponse;
use Telegram\Bot\Keyboard\Keyboard;

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

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => 'test',
                'callback_data' => encodeCallback(self::$type, 'test')
            ])
        ]);

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }
}
