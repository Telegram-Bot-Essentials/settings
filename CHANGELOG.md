# Changelog

All notable changes to this project are documented here. Format follows
[Keep a Changelog](https://keepachangelog.com/en/1.0.0/). Until the API
stabilizes at 1.0 a `0.0.x` bump may carry breaking changes.

## [Unreleased]

### Fixed

- Reading a sensitive setting that was saved as plain text (before its key
  became sensitive) threw `The payload is invalid` and broke every screen that
  read it, e.g. a payment gateway's merchant id. Such a value is now returned
  and encrypted in place on first read. A value that looks encrypted but cannot
  be opened (another app key) is still reported, never rewritten.
- New `Settings::encryptPlainSensitiveValues()` and a migration that runs it
  encrypt all existing plain-text sensitive values up front, so none is left
  unencrypted until someone happens to read it. Safe to run repeatedly.
- `BotSetting` declares its `bot_id`, `key` and `value` properties.

## [0.0.19] - 2026-09-22

### Changed

- Accepts `telegram-bot-essentials/essence` `^0.14` as well as `^0.13`:
  0.14.0 only removed `DoneLimited`/`CannotSetItAsDone`/`HidesDone`, none of
  which this package uses.

## [0.0.18] - 2026-09-20

### Changed

- **BREAKING:** requires `telegram-bot-essentials/essence` `^0.13` (the JSON user
  state and the forms engine). No code change: the package's suite passes
  against essence 0.13.0.

## [0.0.17] - 2026-09-01

### Changed

- **BREAKING:** per-bot locale is now provided by rebinding essence's
  `ResolvesBotLocale` contract (`TbeSettingsBotLocaleResolver`) instead of a
  package-owned `BotWebhookInitialized` listener. Essence calls the contract
  from both the webhook path and `tbe:set-webhook`'s command-menu loop, so the
  Telegram command menu is now built in each bot's own locale. Requires
  `telegram-bot-essentials/essence` `^0.12`.
- **BREAKING:** handlers (`BotSettingsKey`) hold translation keys and resolve
  them lazily via `__()` on every read, so one registered instance renders
  correctly in every bot's locale.

### Added

- Pest test suite, Laravel Pint, Larastan (level max) and GitHub Actions CI.
- Laravel Workbench setup for interactive development.
- `LICENSE` (MIT) and this changelog.

### Fixed

- The channel-lock "not joined yet" re-check is answered as a blocking
  callback alert rather than a silent toast (0.0.15).
