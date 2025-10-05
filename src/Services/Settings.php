<?php

namespace TelegramBotEssentials\Settings\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
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
            ->firstOrCreate(['key' => $key]);

        switch ($setting->type) {
            case SettingType::SELECT:
            case SettingType::ENUM:
            case SettingType::CHECKBOX:
            case SettingType::NUMBER:
            case SettingType::TEXT:
                $value = $botSetting->value;
                break;
            case SettingType::PASSWORD:
                $value = $botSetting->value == null ? null : decrypt($botSetting->value);
                break;
            case SettingType::MULTISELECT:
                $value = $botSetting->value == null ? null : json_decode($botSetting->value);
                break;
        }

        if(!$value) $value = $setting->default ?? null;

        return $value;
    }

    public function set(string $key, mixed $data): mixed
    {
        $setting = $this->settings->get($key);

        $rules = $this->getValidationRuleForType($setting->type);
        Validator::validate(
            ['value' => $data],
            ['value' => $rules],
            attributes: ['value' => $setting->label]
        );

        $botSetting = BotSetting::query()
            ->firstOrCreate([
                'bot_id' => wHook()->bot()->id,
                'key' => $key,
            ]);

        return $this->setValueForType($botSetting, $data ?? $setting->default, $setting->type);
    }

    private function getValidationRuleForType(SettingType $type): string
    {
        $rules = 'required';
        switch ($type) {
            case SettingType::NUMBER:
                $rules = 'required|numeric';
                break;
            case SettingType::TEXT:
            case SettingType::PASSWORD:
                $rules = 'required|string';
                break;
            case SettingType::SELECT:
            case SettingType::ENUM:
                $rules = 'required|in:' . implode(',', $setting->enums);
                break;
            case SettingType::MULTISELECT:
                $rules = 'required|array';
                break;
            case SettingType::CHECKBOX:
                $rules = 'required|boolean';
                break;
        }
        return $rules;
    }

    private function setValueForType(BotSetting $botSetting, mixed $data, SettingType $type)
    {
        switch ($type) {
            case SettingType::SELECT:
            case SettingType::ENUM:
            case SettingType::CHECKBOX:
            case SettingType::NUMBER:
            case SettingType::TEXT:
                break;
            case SettingType::PASSWORD:
                $data = encrypt($data);
                break;
            case SettingType::MULTISELECT:
                $data = json_encode($data);
                break;
        }

        $botSetting->update(['value' => $data]);

        return $botSetting->value;
    }
}