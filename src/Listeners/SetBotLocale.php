<?php

namespace TelegramBotEssentials\Settings\Listeners;

use Illuminate\Support\Facades\App;
use TelegramBotEssentials\Essence\Events\BotWebhookInitialized;

class SetBotLocale
{
    public function handle(BotWebhookInitialized $event): void
    {
        $locale = settings()->get('locale') ?? config('app.locale');

        App::setLocale($locale);
    }
}
