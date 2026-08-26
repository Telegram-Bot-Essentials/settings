<?php

declare(strict_types=1);

use TelegramBotEssentials\Settings\Services\Settings;

it('falls back to the setting default until a value is set', function () {
    $bot = $this->makeBot();

    expect(app(Settings::class)->get('channel_lock.status', $bot))->toBeFalse();
});

// A checkbox reads back as a native bool only via the default path
// (Settings::get() falls back to the un-cast $setting->default when no row
// exists); a written-and-read value round-trips through the `value` column
// as a string instead.

it('persists a set value and returns it on the next get', function () {
    $bot = $this->makeBot();

    app(Settings::class)->set('channel_lock.status', true, $bot);

    expect(app(Settings::class)->get('channel_lock.status', $bot))->toBe('1');
});

it('scopes settings per bot', function () {
    $botA = $this->makeBot();
    $botB = $this->makeBot();

    app(Settings::class)->set('channel_lock.status', true, $botA);

    expect(app(Settings::class)->get('channel_lock.status', $botA))->toBe('1')
        ->and(app(Settings::class)->get('channel_lock.status', $botB))->toBeFalse();
});

it('invalidates the cached value when the setting changes', function () {
    $bot = $this->makeBot();

    app(Settings::class)->get('channel_lock.status', $bot);
    app(Settings::class)->set('channel_lock.status', true, $bot);

    expect(app(Settings::class)->get('channel_lock.status', $bot))->toBe('1');
});
