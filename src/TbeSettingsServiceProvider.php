<?php

namespace TelegramBotEssentials\Settings;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use TelegramBotEssentials\Essence\Events\BotUpdateReceived;
use TelegramBotEssentials\Essence\Events\BotWebhookInitialized;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Settings\DTOs\Setting;
use TelegramBotEssentials\Settings\Enums\SettingType;
use TelegramBotEssentials\Settings\Listeners\LockActionsToChannelUsers;
use TelegramBotEssentials\Settings\Listeners\SetBotLocale;
use TelegramBotEssentials\Settings\Services\ChannelMembership;
use TelegramBotEssentials\Settings\Services\LocaleRegistry;
use TelegramBotEssentials\Settings\Services\Settings;
use TelegramBotEssentials\Settings\Telegram\CallbackQueries\Admin\BotSettingsQuery;
use TelegramBotEssentials\Settings\Telegram\CallbackQueries\Member\ChannelLockQuery;
use TelegramBotEssentials\Settings\Telegram\StateAnswers\Admin\BotSettingsAnswer;

class TbeSettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Settings::class, fn () => new Settings());
        $this->app->singleton(LocaleRegistry::class);
        $this->app->singleton(ChannelMembership::class);
    }

    /**
     * @throws LogicException
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $this->registerPublishing();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'tbe-settings');

        botEventBus()->listen(BotWebhookInitialized::class, SetBotLocale::class);
        botEventBus()->listen(BotUpdateReceived::class, LockActionsToChannelUsers::class);

        callbackQueryBus()->addCallbackQueries([
            BotSettingsQuery::class,
            ChannelLockQuery::class,
        ]);

        stateAnswerBus()->addStateAnswers([
            BotSettingsAnswer::class,
        ]);

        $this->registerSettings();
    }

    protected function registerSettings(): void
    {
        settings()->addSetting(new Setting(
            key: 'locale',
            label: fn () => __('tbe-settings::bot_settings.locale.label'),
            type: SettingType::SELECT,
            default: config('app.locale'),
            options: fn () => app(LocaleRegistry::class)->selectOptions(),
        ));

        settings()->addSetting(new Setting(
            key: 'channel_lock',
            label: fn () => __('tbe-settings::bot_settings.channel_lock.label'),
            type: SettingType::DIRECTORY,
        ));

        settings()->addSetting(new Setting(
            key: 'channel_lock.status',
            label: fn () => __('tbe-settings::bot_settings.channel_lock.status.label'),
            type: SettingType::CHECKBOX,
            default: false
        ));

        settings()->addSetting(new Setting(
            key: 'channel_lock.channel_id',
            label: fn () => __('tbe-settings::bot_settings.channel_lock.channel_id.label'),
            type: SettingType::TEXT,
        ));
    }

    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../lang' => resource_path('lang/vendor/tbe-settings'),
            ], 'tbe-settings-translations');
        }
    }
}
