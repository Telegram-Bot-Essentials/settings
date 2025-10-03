<?php

use TelegramBotEssentials\Settings\Services\Settings;

if(!function_exists('settings')) {
    function settings(): Settings
    {
        return app(Settings::class);
    }
}