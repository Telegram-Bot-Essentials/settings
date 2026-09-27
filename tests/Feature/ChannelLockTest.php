<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Settings\Models\BotSetting;

uses(RefreshDatabase::class);

function lockChannel(Bot $bot, ?string $channelId): void
{
    BotSetting::query()->create(['bot_id' => $bot->id, 'key' => 'channel_lock.status', 'value' => '1']);
    if ($channelId !== null) {
        BotSetting::query()->create(['bot_id' => $bot->id, 'key' => 'channel_lock.channel_id', 'value' => $channelId]);
    }
}

function fakeChatMember(array|int $response): void
{
    // A fresh factory: the base TestCase already registered a catch-all fake,
    // and stubs registered first win.
    $factory = new Factory;
    $factory->fake([
        '*/getChatMember*' => is_int($response)
            ? Http::response(['ok' => false, 'error_code' => $response, 'description' => 'Bad Request: member list is inaccessible'], $response)
            : Http::response(['ok' => true, 'result' => $response]),
        '*' => Http::response(['ok' => true, 'result' => true]),
    ]);
    Http::swap($factory);
}

function sentTexts(): array
{
    return collect(Http::recorded())
        ->filter(fn ($pair) => str_contains((string) $pair[0]->url(), '/sendMessage'))
        ->map(fn ($pair) => ['chat_id' => (int) $pair[0]['chat_id'], 'text' => (string) $pair[0]['text']])
        ->values()
        ->all();
}

it('asks a member who has not joined to join the channel', function () {
    $bot = $this->makeBot();
    lockChannel($bot, 'mychannel');
    fakeChatMember(['status' => 'left', 'user' => ['id' => 555, 'is_bot' => false, 'first_name' => 'u']]);

    $this->postWebhookUpdate($bot, $this->makeMessageUpdate('hello', peerId: 555))->assertOk();

    expect(collect(sentTexts())->pluck('text'))
        ->toContain(__('tbe-settings::bot_settings.channel_lock.prompt'))
        ->and(collect(sentTexts())->pluck('chat_id'))->not->toContain((int) $bot->bot_owner_peer_id);
});

it('lets members through and alerts the owner when membership cannot be checked', function () {
    $bot = $this->makeBot();
    lockChannel($bot, 'mychannel');
    fakeChatMember(400);

    $this->postWebhookUpdate($bot, $this->makeMessageUpdate('hello', peerId: 555))->assertOk();
    $this->postWebhookUpdate($bot, $this->makeMessageUpdate('again', peerId: 556))->assertOk();

    $toOwner = collect(sentTexts())->where('chat_id', (int) $bot->bot_owner_peer_id);
    expect($toOwner)->toHaveCount(1)
        ->and($toOwner->first()['text'])->toContain('@mychannel')->toContain('member list is inaccessible')
        ->and(collect(sentTexts())->pluck('text'))->not->toContain(__('tbe-settings::bot_settings.channel_lock.prompt'));
});

it('alerts the owner when the lock is on without a channel', function () {
    $bot = $this->makeBot();
    lockChannel($bot, null);

    $this->postWebhookUpdate($bot, $this->makeMessageUpdate('hello', peerId: 555))->assertOk();

    expect(collect(sentTexts())->where('chat_id', (int) $bot->bot_owner_peer_id)->pluck('text')->all())
        ->toBe(['⚠️ '.__('tbe-settings::bot_settings.channel_lock.alerts.no_channel')]);
});
