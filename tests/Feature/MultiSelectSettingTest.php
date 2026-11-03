<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Telegram\Bot\Objects\Update;
use TelegramBotEssentials\Settings\DTOs\Setting;
use TelegramBotEssentials\Settings\Enums\SettingType;
use TelegramBotEssentials\Settings\Telegram\CallbackQueries\Admin\BotSettingsQuery;
use TelegramBotEssentials\Settings\Telegram\Features\Admin\BotSettingsFeature;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->labels = ['owner' => 'Bot owner', 7 => 'Sales, north', 9 => 'Support'];
    settings()->addSetting(new Setting(
        key: 'test_targets',
        label: 'Targets',
        type: SettingType::MULTISELECT,
        default: [],
        options: fn () => $this->labels,
    ));

    $bot = $this->makeBot();
    wHook()->setBot($bot);
    wHook()->setApi(telegramApi((string) $bot->bot_token));
    wHook()->setUpdate(new Update([
        'update_id' => 1,
        'callback_query' => ['id' => '1', 'from' => ['id' => 1, 'is_bot' => false, 'first_name' => 'T'], 'data' => 'x',
            'message' => ['message_id' => 1, 'date' => 1, 'chat' => ['id' => 1, 'type' => 'private']]],
    ]));
});

it('stores the keys of what is picked, not the labels', function () {
    $query = app(BotSettingsQuery::class);

    $query->multiSelect('test_targets', '7', false);
    $query->multiSelect('test_targets', 'owner', false);

    expect(settings()->get('test_targets'))->toBe(['7', 'owner']);
});

it('takes a pick away again', function () {
    $query = app(BotSettingsQuery::class);
    settings()->set('test_targets', ['7', '9']);

    $query->multiSelect('test_targets', '7', true);

    expect(settings()->get('test_targets'))->toBe(['9']);
});

it('keeps a choice when its label changes', function () {
    settings()->set('test_targets', ['9']);
    $this->labels[9] = 'Renamed';

    $texts = collect(BotSettingsFeature::multiselect(settings()->getSetting('test_targets'))->replyMarkup->toArray()['inline_keyboard'])
        ->flatten(1)->pluck('text')->all();

    expect($texts)->toContain('Renamed ✅');
});
