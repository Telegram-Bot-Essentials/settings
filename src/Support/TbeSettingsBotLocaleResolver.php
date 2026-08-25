<?php

namespace TelegramBotEssentials\Settings\Support;

use TelegramBotEssentials\Essence\Contracts\ResolvesBotLocale;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Settings\Services\Settings;

class TbeSettingsBotLocaleResolver implements ResolvesBotLocale
{
    public function __construct(private Settings $settings) {}

    public function resolve(Bot $bot): string
    {
        $locale = $this->settings->get('locale', $bot);

        return is_string($locale) && $locale !== '' ? $locale : config()->string('app.locale');
    }
}
