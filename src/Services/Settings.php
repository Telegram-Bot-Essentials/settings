<?php

namespace TelegramBotEssentials\Settings\Services;

use Illuminate\Support\Collection;

class Settings
{
    private Collection $settings;

    public function __construct()
    {
        $this->settings = collect();
    }

    public function addSetting()
    {

    }

    public function getSettings(): Collection
    {
        return $this->settings;
    }
}