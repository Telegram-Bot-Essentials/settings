<?php

namespace TelegramBotEssentials\Settings\Services;

use Illuminate\Contracts\Encryption\DecryptException;
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
                    $stored = $botSetting->value;
                    $value = $stored === null || $stored === '' ? null : $this->decryptSensitive($botSetting, $stored);
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
            throw new TbeException('Setting "'.$key.'" not found');
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
        return $this->cacheKeyFor($key, $bot->id);
    }

    private function cacheKeyFor(string $key, int|string $botId): string
    {
        return 'settings:'.$botId.':'.$key;
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
                $rules = 'nullable|in:'.implode(',', array_keys($setting->getOptions()));
                break;
            case SettingType::ENUM:
                $rules = 'nullable|in:'.implode(',', $setting->getOptions());
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

    /**
     * Encrypts, in place, every sensitive setting that is still stored as plain text (saved before
     * the key became sensitive) and returns how many it fixed. Values that are already encrypted,
     * empty, or encrypted with another app key are left alone, so it is safe to run repeatedly.
     */
    public function encryptPlainSensitiveValues(): int
    {
        $keys = [];
        foreach ($this->settings as $key => $setting) {
            if ($setting instanceof Setting && $setting->type === SettingType::SENSITIVE) {
                $keys[] = (string) $key;
            }
        }

        $fixed = 0;

        BotSetting::query()
            ->withoutGlobalScopes()
            ->whereIn('key', $keys)
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->each(function (BotSetting $botSetting) use (&$fixed) {
                if ($this->encryptIfPlain($botSetting)) {
                    $fixed++;
                    Cache::forget($this->cacheKeyFor($botSetting->key, $botSetting->bot_id));
                }
            });

        return $fixed;
    }

    /**
     * Sensitive values are stored encrypted, but one saved before its key became sensitive is
     * plain text. Reading such a value returns it and encrypts it in place, so a stale row heals
     * itself instead of breaking every screen that reads it. A value that has the shape of an
     * encrypted payload yet cannot be opened (another app key) is a real error and is rethrown.
     */
    private function decryptSensitive(BotSetting $botSetting, string $stored): mixed
    {
        try {
            return decrypt($stored);
        } catch (DecryptException $e) {
            if ($this->looksEncrypted($stored)) {
                throw $e;
            }

            $this->encryptIfPlain($botSetting);

            return $stored;
        }
    }

    private function encryptIfPlain(BotSetting $botSetting): bool
    {
        $value = $botSetting->value;
        if ($value === null || $value === '') {
            return false;
        }

        try {
            decrypt($value);

            return false;
        } catch (DecryptException) {
            if ($this->looksEncrypted($value)) {
                return false;
            }
        }

        $botSetting->update(['value' => encrypt($value)]);

        tbeLog('settings')->warning('Encrypted a sensitive setting that was stored as plain text', [
            'key' => $botSetting->key,
        ]);

        return true;
    }

    /** Whether the stored text has the shape of a Laravel encrypted payload (base64 of iv/value/mac JSON). */
    private function looksEncrypted(string $value): bool
    {
        $payload = json_decode((string) base64_decode($value, true), true);

        return is_array($payload) && isset($payload['iv'], $payload['value'], $payload['mac']);
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
