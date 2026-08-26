<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Settings\Telegram\ReplyKeys\Admin\BotSettingsKey;

// A companion's ReplyKeys are never auto-discovered - the consuming app
// lists them in config('tbe-essence.keyboard') and essence registers them
// from there. Do that one line here, standing in for the app's config.
beforeEach(fn () => replyKeyBus()->addReplyKey(BotSettingsKey::class));

it('opens the settings menu for an admin', function () {
    $bot = $this->makeBot();
    $peerId = 555;
    $this->makeBotUser($bot, $peerId, ['power' => Roles::ADMIN->value]);

    $this->postWebhookUpdate($bot, $this->makeMessageUpdate('Bot Settings ⚙️', peerId: $peerId))
        ->assertOk();

    $this->assertTelegramSent(fn ($request) => str_contains((string) $request['text'], 'Bot Settings'));
});

it('does not open the settings menu for a member', function () {
    $bot = $this->makeBot();
    $peerId = 556;
    $this->makeBotUser($bot, $peerId, ['power' => Roles::MEMBER->value]);

    $this->postWebhookUpdate($bot, $this->makeMessageUpdate('Bot Settings ⚙️', peerId: $peerId))
        ->assertOk();

    Http::assertNothingSent();
});
