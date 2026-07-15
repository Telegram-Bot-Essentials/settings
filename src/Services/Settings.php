<?php

namespace TelegramBotEssentials\Settings\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use TelegramBotEssentials\Essence\Exceptions\TbeException;
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
            case SettingType::CHECKBOX:
            case SettingType::SELECT:
            case SettingType::ENUM:
            case SettingType::NUMBER:
            case SettingType::TEXT:
                $value = $botSetting->value;
                break;
            case SettingType::SENSITIVE:
                $value = $botSetting->value == null ? null : decrypt($botSetting->value);
                break;
            case SettingType::MULTISELECT:
                $value = $botSetting->value == null ? ($setting->default ?? []) : explode(',', $botSetting->value);
                break;
        }

        if (is_null($value)) {
            $value = $setting->default ?? null;
        }

        return $value;
    }

    public function set(string $key, mixed $data): mixed
    {
        $setting = $this->settings->get($key);

        if (! $setting) {
            throw new TbeException('Setting "' . $key . '" not found');
        }

        $rules = $this->getValidationRuleForType($setting);
        Validator::validate(
            ['value' => $data],
            ['value' => $rules],
            attributes: ['value' => $setting->getLabel()]
        );

        $botSetting = BotSetting::query()
            ->firstOrCreate([
                'bot_id' => wHook()->bot()->id,
                'key' => $key,
            ]);

        return $this->setValueForType($botSetting, $data ?? $setting->default, $setting->type);
    }

    private function getValidationRuleForType(Setting $setting): string
    {
        $rules = 'required';
        switch ($setting->type) {
            case SettingType::NUMBER:
                $rules = 'nullable|numeric';
                break;
            case SettingType::TEXT:
            case SettingType::SENSITIVE:
                $rules = 'nullable|string';
                break;
            case SettingType::SELECT:
                $rules = 'nullable|in:' . implode(',', array_keys($setting->getOptions()));
                break;
            case SettingType::ENUM:
                $rules = 'nullable|in:' . implode(',', $setting->getOptions());
                break;
            case SettingType::MULTISELECT:
                $rules = 'nullable|array';
                break;
            case SettingType::CHECKBOX:
                $rules = 'nullable|boolean';
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
            case SettingType::SENSITIVE:
                $data = encrypt($data);
                break;
            case SettingType::MULTISELECT:
                $data = implode(',', $data);
                break;
        }

        $botSetting->update(['value' => $data]);

        return $botSetting->value;
    }
}
