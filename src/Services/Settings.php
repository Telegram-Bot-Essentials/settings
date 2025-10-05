<?php

namespace TelegramBotEssentials\Settings\Services;

use Illuminate\Support\Collection;
use TelegramBotEssentials\Settings\DTOs\Setting;
use TelegramBotEssentials\Settings\Enums\SettingType;
use TelegramBotEssentials\Settings\Models\BotSetting;

class Settings
{
    private Collection $settings;

    public function __construct()
    {
        $this->settings = collect();
    }

    public function addSetting(Setting $setting): void
    {
        $this->settings->put($setting->key, $setting);
    }

    public function getSettings(): Collection
    {
        return $this->settings;
    }

    public function getSetting(string $key): Setting
    {
        return $this->settings->get($key);
    }

    public function get(string $key): mixed
    {
        $setting = $this->settings->get($key);
        $botSetting = BotSetting::where('bot_id', wHook()->bot()->id)
            ->where('key', $key)->first();

        switch ($setting->type) {
            case SettingType::CHECKBOX:
                return $botSetting ? boolval($botSetting->value) : ($setting->default ?? $key);
        }
        return $botSetting->value ?? ($setting->default ?? null);
    }

    public function set(string $key, mixed $data): mixed
    {
        $setting = $this->settings->get($key);

        $botSetting = BotSetting::query()
            ->firstOrCreate([
                'bot_id' => wHook()->bot()->id,
                'key' => $key,
            ]);

        $botSetting->update(['value' => $data ?? $setting->default]);
        return $botSetting->value;
    }
}