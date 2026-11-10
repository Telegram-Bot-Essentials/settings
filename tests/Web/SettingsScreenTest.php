<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use TelegramBotEssentials\Essence\Web\WebAccounts;
use TelegramBotEssentials\Settings\DTOs\Setting;
use TelegramBotEssentials\Settings\Enums\SettingType;
use TelegramBotEssentials\Settings\Web\Manage\BotSettingsPage;

beforeEach(function () {
    // Testbench ships without an app key, and secrets are encrypted with it.
    config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);
    app()->forgetInstance('encrypter');

    settings()->addSetting(new Setting(key: 'shop', label: 'Shop', type: SettingType::DIRECTORY, description: 'How the shop sells'));
    settings()->addSetting(new Setting(key: 'shop.title', label: 'Shop title', type: SettingType::TEXT, rules: ['max:20']));
    settings()->addSetting(new Setting(key: 'shop.limit', label: 'Daily limit', type: SettingType::NUMBER));
    settings()->addSetting(new Setting(key: 'shop.open', label: 'Open', type: SettingType::CHECKBOX, default: false));
    settings()->addSetting(new Setting(key: 'shop.mode', label: 'Mode', type: SettingType::ENUM, options: ['fast', 'slow'], default: 'fast'));
    settings()->addSetting(new Setting(key: 'shop.tags', label: 'Tags', type: SettingType::MULTISELECT, options: ['a' => 'Alpha', 'b' => 'Beta']));
    settings()->addSetting(new Setting(key: 'shop.api_key', label: 'API key', type: SettingType::SENSITIVE));

    $this->bot = $this->makeBot(['bot_owner_peer_id' => 9401]);
    wHook()->setBot($this->bot);
    $this->admin = $this->grant($this->makeBotUser($this->bot, 9401), 'settings.manage', 'web.manage');
    $this->account = $this->panelAccount($this->admin);
    Auth::guard(WebAccounts::guard())->login($this->account);
    $this->url = '/manage/'.$this->bot->unique_id.'/settings';
});

it('shows the settings grouped by directory', function () {
    settings()->set('shop.title', 'Corner store', $this->bot);

    $this->get($this->url)
        ->assertOk()
        ->assertSee('How the shop sells')
        ->assertSee('Shop title')
        ->assertSee('Corner store')
        ->assertSee(__('tbe-settings::bot_settings.locale.label'));
});

it('never sends a stored secret to the browser', function () {
    settings()->set('shop.api_key', 'sk-live-very-secret', $this->bot);

    $this->get($this->url)
        ->assertOk()
        ->assertDontSee('sk-live-very-secret')
        ->assertSee(__('tbe-settings::web.secret_set'));
});

it('keeps the screen from anyone without the ability', function () {
    $limited = $this->grant($this->makeBotUser($this->bot, 9402), 'web.manage');
    Auth::guard(WebAccounts::guard())->login($this->panelAccount($limited));

    $this->get($this->url)->assertForbidden();
});

it('saves what changed through the settings rules', function () {
    $this->enterPanel('manage', $this->bot, $this->account);

    Livewire::test(BotSettingsPage::class)
        ->fillForm(['shop__title' => 'Kiosk', 'shop__limit' => '12', 'shop__open' => true, 'shop__mode' => 'slow', 'shop__tags' => ['b']])
        ->call('save')
        ->assertHasNoErrors();

    expect(settings()->get('shop.title', $this->bot))->toBe('Kiosk')
        ->and((string) settings()->get('shop.limit', $this->bot))->toBe('12')
        ->and((bool) settings()->get('shop.open', $this->bot))->toBeTrue()
        ->and(settings()->get('shop.mode', $this->bot))->toBe('slow')
        ->and(settings()->get('shop.tags', $this->bot))->toBe(['b']);
});

it('refuses a value the setting\'s rules refuse, saving the rest', function () {
    $this->enterPanel('manage', $this->bot, $this->account);

    Livewire::test(BotSettingsPage::class)
        ->fillForm(['shop__title' => str_repeat('x', 30), 'shop__limit' => '5'])
        ->call('save')
        ->assertHasErrors(['data.shop__title']);

    expect(settings()->get('shop.title', $this->bot))->toBeNull()
        ->and((string) settings()->get('shop.limit', $this->bot))->toBe('5');
});

it('replaces a secret only when a new one is typed', function () {
    settings()->set('shop.api_key', 'old-secret', $this->bot);
    $this->enterPanel('manage', $this->bot, $this->account);

    Livewire::test(BotSettingsPage::class)->fillForm(['shop__title' => 'Kiosk'])->call('save');
    expect(settings()->get('shop.api_key', $this->bot))->toBe('old-secret');

    Livewire::test(BotSettingsPage::class)->fillForm(['shop__api_key' => 'new-secret'])->call('save');
    expect(settings()->get('shop.api_key', $this->bot))->toBe('new-secret');
});

it('saves over a real request', function () {
    $this->livewireRequest($this->url, 'BotSettingsPage', 'save')->assertOk();
});

it('refuses saving once signed out', function () {
    $this->livewireRequest($this->url, 'BotSettingsPage', 'save', [],
        fn () => Auth::guard(WebAccounts::guard())->logout())->assertUnauthorized();
});
