# Changelog

All notable changes to this project are documented here. Format follows
[Keep a Changelog](https://keepachangelog.com/en/1.0.0/). Until the API
stabilizes at 1.0 a `0.0.x` bump may carry breaking changes.

## [Unreleased]

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
