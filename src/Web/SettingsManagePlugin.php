<?php

namespace TelegramBotEssentials\Settings\Web;

use Filament\Contracts\Plugin;
use Filament\Panel;
use TelegramBotEssentials\Settings\Web\Manage\BotSettingsPage;

class SettingsManagePlugin implements Plugin
{
    public function getId(): string
    {
        return 'tbe-settings-manage';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([BotSettingsPage::class]);
    }

    public function boot(Panel $panel): void {}
}
