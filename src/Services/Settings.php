<?php

namespace TelegramBotEssentials\Settings\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use TelegramBotEssentials\Essence\Exceptions\TbeException;
use TelegramBotEssentials\Essence\Models\Bot;
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

    /**
     * $bot defaults to the ambient webhook-context bot, since every existing
     * call site only ever runs inside a real webhook request. Pass it
     * explicitly from anywhere else (a console command, a queued job) that
     * has no webhook context to read it from.
     */
    public function get(string $key, ?Bot $bot = null): mixed
    {
        $bot ??= wHook()->bot();

        return Cache::rememberForever($this->cacheKey($key, $bot), function () use ($key, $bot) {
            $setting = $this->settings->get($key);
            $botSetting = BotSetting::firstOrCreate([
                'bot_id' => $bot->id,
                'key' => $key,
            ]);

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
        });
    }

    public function set(string $key, mixed $data, ?Bot $bot = null): mixed
    {
        $bot ??= wHook()->bot();

        $setting = $this->settings->get($key);

        if (! $setting) {
            throw new TbeException('Setting "' . $key . '" not found');
        }

        $rules = [...explode('|', $this->getValidationRuleForType($setting)), ...$setting->getRules()];
        Validator::validate(
            ['value' => $data],
            ['value' => $rules],
            attributes: ['value' => $setting->getLabel()]
        );

        $hasHooks = $setting->beforeSet !== null || $setting->afterSet !== null;
        $oldValue = $hasHooks ? $this->get($key, $bot) : null;

        $data = $setting->callBeforeSet($data, $oldValue);

        $botSetting = BotSetting::query()
            ->firstOrCreate([
                'bot_id' => $bot->id,
                'key' => $key,
            ]);

        $result = $this->setValueForType($botSetting, $data ?? $setting->default, $setting->type);

        Cache::forget($this->cacheKey($key, $bot));

        tbeLog('settings')->info('Bot setting updated', [
            'key' => $key,
            'value' => $setting->type === SettingType::SENSITIVE
                ? '[redacted]'
                : (is_scalar($data) ? $data : json_encode($data)),
        ]);

        if ($hasHooks) {
            try {
                $setting->callAfterSet($this->get($key, $bot), $oldValue);
            } catch (\Throwable $e) {
                tbeLog('settings')->error('Setting afterSet hook failed', [
                    'key' => $key,
                    'exception' => $e,
                ]);
            }
        }

        return $result;
    }

    private function cacheKey(string $key, Bot $bot): string
    {
        return 'settings:' . $bot->id . ':' . $key;
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
