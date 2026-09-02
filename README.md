# Telegram Bot Essentials — Settings

[![Latest Version](https://img.shields.io/packagist/v/telegram-bot-essentials/settings.svg)](https://packagist.org/packages/telegram-bot-essentials/settings)
[![tests](https://github.com/Telegram-Bot-Essentials/settings/actions/workflows/tests.yml/badge.svg)](https://github.com/Telegram-Bot-Essentials/settings/actions/workflows/tests.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

A per-bot, typed key/value settings system for the
[`telegram-bot-essentials/essence`](https://github.com/Telegram-Bot-Essentials/essence) bot
framework. Every other package in the ecosystem — Billing, User Wallet, Affiliates, the
gateways — registers its configurable options through this package instead of inventing its
own storage, and admins get a generated settings menu for free.

## Installation

```bash
composer require telegram-bot-essentials/settings
php artisan migrate
```

A `locale` setting is registered out of the box, letting each bot pick its own
admin/reply language independently.

## Usage

Declare a setting once, in a service provider's `boot()`:

```php
use TelegramBotEssentials\Settings\DTOs\Setting;
use TelegramBotEssentials\Settings\Enums\SettingType;

settings()->addSetting(new Setting(
    key: 'billing.user_wallet.status',
    label: fn () => __('my-package::settings.wallet_status.label'),
    type: SettingType::CHECKBOX,
    default: false,
));
```

Then read and write it through the `settings()` helper — always scoped to the current bot:

```php
settings()->get('billing.user_wallet.status');       // falls back to the declared default
settings()->set('billing.user_wallet.status', true); // validated against the type before saving
```

`SettingType` covers `TEXT`, `NUMBER`, `CHECKBOX`, `SENSITIVE` (encrypted at rest),
`SELECT` / `ENUM` / `MULTISELECT`, and `DIRECTORY` (UI grouping). `beforeSet` / `afterSet`
hooks and additive Laravel validation `rules` are available per setting.

> **Evaluate lazily.** Settings are per-bot and `boot()` runs once per application, so never
> resolve `settings()->get(...)` directly in `boot()` to decide whether to register
> something — wrap the check in a `Closure` that runs at request time.

The package also ships a `channel_lock` setting group that gates every action behind
membership in a Telegram channel.

## Documentation

Full documentation, including the `Setting` DTO reference, locale switching, and the
channel-lock flow, lives on the Telegram Bot Essentials documentation site under
**Modules → Settings**.

## License

[MIT](LICENSE).
