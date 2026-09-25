<?php

declare(strict_types=1);

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;
use TelegramBotEssentials\Settings\DTOs\Setting;
use TelegramBotEssentials\Settings\Enums\SettingType;
use TelegramBotEssentials\Settings\Services\Settings;

beforeEach(function () {
    // Testbench ships without an app key, and these settings are encrypted with it.
    config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);
    app()->forgetInstance('encrypter');

    $this->bot = $this->makeBot();
    app(Settings::class)->addSetting(new Setting(key: 'gateway.secret', label: 'Secret', type: SettingType::SENSITIVE));
    app(Settings::class)->addSetting(new Setting(key: 'gateway.name', label: 'Name', type: SettingType::TEXT));
});

function storeSetting(int $botId, string $key, ?string $value): void
{
    DB::table('bot_settings')->insert(['bot_id' => $botId, 'key' => $key, 'value' => $value, 'created_at' => now(), 'updated_at' => now()]);
}

function storedValue(int $botId, string $key): ?string
{
    return DB::table('bot_settings')->where('bot_id', $botId)->where('key', $key)->value('value');
}

it('reads a sensitive value that was saved encrypted', function () {
    app(Settings::class)->set('gateway.secret', 'top-secret', $this->bot);

    expect(app(Settings::class)->get('gateway.secret', $this->bot))->toBe('top-secret')
        ->and(storedValue($this->bot->id, 'gateway.secret'))->not->toContain('top-secret');
});

it('reads a plain-text value saved before the key became sensitive, and encrypts it in place', function () {
    storeSetting($this->bot->id, 'gateway.secret', 'legacy-plain');

    expect(app(Settings::class)->get('gateway.secret', $this->bot))->toBe('legacy-plain')
        ->and(storedValue($this->bot->id, 'gateway.secret'))->not->toContain('legacy-plain')
        ->and(decrypt(storedValue($this->bot->id, 'gateway.secret')))->toBe('legacy-plain');
});

it('does not touch a value encrypted with another app key, and still reports it', function () {
    $foreign = (new Encrypter(random_bytes(32), 'aes-256-cbc'))->encrypt('from-another-app');
    storeSetting($this->bot->id, 'gateway.secret', $foreign);

    expect(fn () => app(Settings::class)->get('gateway.secret', $this->bot))->toThrow(DecryptException::class)
        ->and(storedValue($this->bot->id, 'gateway.secret'))->toBe($foreign);
});

it('encrypts every plain sensitive value in one go and reports how many', function () {
    $other = $this->makeBot();
    storeSetting($this->bot->id, 'gateway.secret', 'plain-a');
    storeSetting($other->id, 'gateway.secret', 'plain-b');
    storeSetting($this->bot->id, 'gateway.name', 'not-sensitive');

    expect(app(Settings::class)->encryptPlainSensitiveValues())->toBe(2)
        ->and(storedValue($this->bot->id, 'gateway.secret'))->not->toContain('plain-a')
        ->and(storedValue($other->id, 'gateway.secret'))->not->toContain('plain-b')
        ->and(storedValue($this->bot->id, 'gateway.name'))->toBe('not-sensitive')
        ->and(app(Settings::class)->get('gateway.secret', $this->bot))->toBe('plain-a')
        ->and(app(Settings::class)->get('gateway.secret', $other))->toBe('plain-b');
});

it('is safe to run twice and leaves encrypted and empty values alone', function () {
    $other = $this->makeBot();
    app(Settings::class)->set('gateway.secret', 'already-encrypted', $this->bot);
    storeSetting($other->id, 'gateway.secret', null);
    $before = DB::table('bot_settings')->orderBy('id')->pluck('value', 'id')->all();

    expect(app(Settings::class)->encryptPlainSensitiveValues())->toBe(0)
        ->and(app(Settings::class)->encryptPlainSensitiveValues())->toBe(0)
        ->and(DB::table('bot_settings')->orderBy('id')->pluck('value', 'id')->all())->toBe($before);
});

it('runs the bulk fix from its migration', function () {
    storeSetting($this->bot->id, 'gateway.secret', 'legacy-plain');

    (require dirname(__DIR__, 2).'/database/migrations/2026_09_26_000000_encrypt_plain_sensitive_bot_settings.php')->up();

    expect(storedValue($this->bot->id, 'gateway.secret'))->not->toContain('legacy-plain');
});
