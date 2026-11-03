<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use TelegramBotEssentials\Essence\Events\BotDeepLinkReceived;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQuery;
use TelegramBotEssentials\Essence\Telegram\Commands\Command;
use TelegramBotEssentials\Settings\Contracts\IgnoresChannelLock;
use TelegramBotEssentials\Settings\Models\BotSetting;
use TelegramBotEssentials\Settings\Telegram\CallbackQueries\Member\ChannelLockQuery;

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

    $this->postWebhookUpdate($bot, $this->makeMessageUpdate('/help', peerId: 555))->assertOk();

    expect(collect(sentTexts())->pluck('text'))
        ->toContain(__('tbe-settings::bot_settings.channel_lock.prompt'))
        ->and(collect(sentTexts())->pluck('chat_id'))->not->toContain((int) $bot->bot_owner_peer_id);
});

it('lets members through and alerts the owner when membership cannot be checked', function () {
    $bot = $this->makeBot();
    lockChannel($bot, 'mychannel');
    fakeChatMember(400);

    $this->postWebhookUpdate($bot, $this->makeMessageUpdate('/help', peerId: 555))->assertOk();
    $this->postWebhookUpdate($bot, $this->makeMessageUpdate('/help', peerId: 556))->assertOk();

    $toOwner = collect(sentTexts())->where('chat_id', (int) $bot->bot_owner_peer_id);
    expect($toOwner)->toHaveCount(1)
        ->and($toOwner->first()['text'])->toContain('@mychannel')->toContain('member list is inaccessible')
        ->and(collect(sentTexts())->pluck('text'))->not->toContain(__('tbe-settings::bot_settings.channel_lock.prompt'));
});

it('alerts the owner when the lock is on without a channel', function () {
    $bot = $this->makeBot();
    lockChannel($bot, null);

    $this->postWebhookUpdate($bot, $this->makeMessageUpdate('/help', peerId: 555))->assertOk();

    expect(collect(sentTexts())->where('chat_id', (int) $bot->bot_owner_peer_id)->pluck('text')->all())
        ->toBe(['⚠️ '.__('tbe-settings::bot_settings.channel_lock.alerts.no_channel')]);
});

it('leaves groups alone, and still locks the private chat', function () {
    config(['tbe-essence.groups.enabled' => true]);
    $bot = $this->makeBot();
    lockChannel($bot, 'mychannel');
    fakeChatMember(['status' => 'left', 'user' => ['id' => 1, 'is_bot' => false, 'first_name' => 'T']]);

    $this->postWebhookUpdate($bot, $this->inGroupChat($this->makeMessageUpdate('hello', 6001), -100222))->assertOk();

    expect(sentTexts())->toBe([]);
    Http::assertNotSent(fn ($request) => str_contains($request->url(), '/getChatMember'));

    $this->postWebhookUpdate($bot, $this->makeMessageUpdate('/help', 6001))->assertOk();

    expect(array_column(sentTexts(), 'chat_id'))->toContain(6001);
});

class LockTestStartCommand extends Command
{
    protected string $name = 'start';

    public function handle(): ?bool
    {
        wHook()->api()->sendMessage([...wHook()->replyTo(), 'text' => 'welcome']);

        return true;
    }
}

function outsider(): array
{
    return ['status' => 'left', 'user' => ['id' => 1, 'is_bot' => false, 'first_name' => 'T']];
}

it('runs /start and hands over its deep link before the member joins', function () {
    commandBus()->addCommands([LockTestStartCommand::class]);
    $links = [];
    Event::listen(BotDeepLinkReceived::class, function (BotDeepLinkReceived $event) use (&$links) {
        $links[] = $event->payload;
    });
    $bot = $this->makeBot();
    lockChannel($bot, 'mychannel');
    fakeChatMember(outsider());

    $this->postWebhookUpdate($bot, $this->makeMessageUpdate('/start c_abc', 6101))->assertOk();

    expect($links)->toBe(['c_abc'])
        ->and(collect(sentTexts())->pluck('text'))->toContain('welcome')
        ->not->toContain(__('tbe-settings::bot_settings.channel_lock.prompt'));
    Http::assertNotSent(fn ($request) => str_contains($request->url(), '/getChatMember'));
});

it('does not ask anyone to join over text no handler claims', function () {
    $bot = $this->makeBot();
    lockChannel($bot, 'mychannel');
    fakeChatMember(outsider());

    $this->postWebhookUpdate($bot, $this->makeMessageUpdate('just talking', 6102))->assertOk();

    expect(collect(sentTexts())->pluck('text'))->not->toContain(__('tbe-settings::bot_settings.channel_lock.prompt'));
});

it('still records a member who blocks the bot', function () {
    $bot = $this->makeBot();
    lockChannel($bot, 'mychannel');
    fakeChatMember(outsider());
    $user = $this->makeBotUser($bot, 6103);

    $this->postWebhookUpdate($bot, [
        'my_chat_member' => [
            'chat' => ['id' => 6103, 'type' => 'private'],
            'from' => ['id' => 6103, 'is_bot' => false, 'first_name' => 'T'],
            'date' => 1,
            'old_chat_member' => ['status' => 'member', 'user' => ['id' => 1, 'is_bot' => true, 'first_name' => 'B']],
            'new_chat_member' => ['status' => 'kicked', 'user' => ['id' => 1, 'is_bot' => true, 'first_name' => 'B'], 'until_date' => 0],
        ],
    ])->assertOk();

    expect($user->refresh()->reachability())->toBe('blocked');
});

it('tells someone who has not joined when they press the confirm button', function () {
    $bot = $this->makeBot();
    lockChannel($bot, 'mychannel');
    fakeChatMember(outsider());

    $this->postWebhookUpdate($bot, $this->makeCallbackQueryUpdate(encodeCallback(ChannelLockQuery::TYPE, 'checkMembership'), 6104))->assertOk();

    Http::assertSent(fn ($request) => str_contains($request->url(), '/answerCallbackQuery')
        && $request['text'] === __('tbe-settings::bot_settings.messages.channel_lock.not_joined'));
});

it('answers a locked button with a new message, leaving the one it sits on', function () {
    callbackQueryBus()->addCallbackQueries([LockTestPressQuery::class]);
    $bot = $this->makeBot();
    lockChannel($bot, 'mychannel');
    fakeChatMember(outsider());

    $this->postWebhookUpdate($bot, $this->makeCallbackQueryUpdate(encodeCallback('LOCK_PRESS', 'press'), 6105))->assertOk();

    expect(collect(sentTexts())->pluck('text'))->toContain(__('tbe-settings::bot_settings.channel_lock.prompt'));
    Http::assertNotSent(fn ($request) => str_contains($request->url(), '/editMessage'));
});

class LockTestPressQuery extends CallbackQuery
{
    protected string $type = 'LOCK_PRESS';

    public function press(): void
    {
        wHook()->api()->sendMessage([...wHook()->replyTo(), 'text' => 'pressed']);
    }
}

it('lets a handler marked as ignoring the lock through', function () {
    callbackQueryBus()->addCallbackQueries([LockTestIgnoringQuery::class]);
    $bot = $this->makeBot();
    lockChannel($bot, 'mychannel');
    fakeChatMember(outsider());

    $this->postWebhookUpdate($bot, $this->makeCallbackQueryUpdate(encodeCallback('LOCK_IGNORING', 'press'), 6106))->assertOk();

    expect(collect(sentTexts())->pluck('text'))
        ->toContain('pressed')
        ->not->toContain(__('tbe-settings::bot_settings.channel_lock.prompt'));
});

class LockTestIgnoringQuery extends CallbackQuery implements IgnoresChannelLock
{
    protected string $type = 'LOCK_IGNORING';

    public function press(): void
    {
        wHook()->api()->sendMessage([...wHook()->replyTo(), 'text' => 'pressed']);
    }
}
