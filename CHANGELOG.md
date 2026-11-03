# Changelog

All notable changes to this project are documented here. Format follows
[Keep a Changelog](https://keepachangelog.com/en/1.0.0/). Until the API
stabilizes at 1.0 a `0.0.x` bump may carry breaking changes.

## [Unreleased]

## [0.0.33] - 2026-11-03

### Added

- `IgnoresChannelLock`: a handler implementing it runs for members outside the
  locked channel, for what they should reach regardless, such as support.

## [0.0.32] - 2026-11-03

### Fixed

- A `MULTISELECT` setting stores its option keys, like `SELECT` does. It stored the
  labels, so a rename or another language lost the choice, and a comma in a label
  split it in two.

## [0.0.31] - 2026-11-03

### Changed

- The channel lock only refuses what a member asks of the bot: reply keys,
  button presses, form answers, commands, text matchers and inline queries.
  `/start` and its deep links run before joining, so a referral or a campaign
  prize is no longer lost to someone joining second, and blocking the bot is
  recorded as usual. The prompt is a new message, so it no longer replaces a
  prize message the member still needs. Needs essence with `HandlerGate`.

## [0.0.30] - 2026-11-01

### Fixed

- The channel lock leaves groups alone. It would have answered every message
  there from someone outside the channel. The private chat is still locked.

## [0.0.29] - 2026-11-01

### Added

- `lang/<locale>/abilities.php` labels this package's Gate abilities for
  essence's `abilities()` catalog, so a roles screen (tbe-access) shows
  them by name.

## [0.0.28] - 2026-10-31

### Changed

- Requires essence 0.19.0-beta.3. Alerts go out through `ownerAlert()`,
  essence's new name for `adminAlert()`: they always reached the bot's owner.

## [0.0.27] - 2026-10-31

### Fixed

- Requires essence 0.19.0-beta.2 or later; 0.19.0-beta.1 still had the
  permission API.

## [0.0.26] - 2026-10-31

### Changed

- Requires essence 0.19. The admin handlers name a Laravel Gate ability
  (`settings.manage`) instead of a permission, and nothing is registered with essence
  any more: the app's Gate decides who holds it. Essence no longer lets the
  bot owner through by default, so an app grants these with `Gate::before`
  or `Gate::define`.

### Removed

- The permission labels (`lang/*/permissions.php`).

## [0.0.25] - 2026-10-12

### Changed

- Requires essence 0.18. A flow's state is read and written on the webhook's
  conversation (`wHook()->conversation()`) instead of the user, so it stays
  in the chat it started in.

## [0.0.24] - 2026-10-12

Requires essence 0.17, which replaces power levels with named permissions.

### Changed

- The admin screens ask for `settings.manage` instead of the admin power level.
  The names are registered with essence's permission registry, so a role
  package can grant them separately.
- Holders of `settings.manage` skip the channel lock, in place of every admin.

## [0.0.23] - 2026-10-01

### Changed

- Reworded the user-facing English and Persian strings to read more naturally; no keys or placeholders changed.

## [0.0.22] - 2026-09-28

### Changed

- Accepts essence 0.16 alongside 0.15.

## [0.0.21] - 2026-09-27

### Changed

- Requires essence `^0.15` for `adminAlert()` and `tbeLog()->audit()`.
- Channel lock fails open: when the bot cannot check membership (removed from
  the channel, no longer an admin, channel gone) members are let through
  instead of every one of them being locked out, and the owner and admins get
  a throttled alert with Telegram's error. The lock being on without a
  channel set alerts them the same way instead of logging a warning on every
  update.
- Channel lock no longer logs per update: the skipped / blocked / re-check /
  confirmed debug and info lines are gone.
- `Bot setting "<key>" updated` is an audit entry (`tbeLog()->audit()`), so it
  lands on the audit channel with the key in the message.

## [0.0.20] - 2026-09-25

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
