<?php

namespace TelegramBotEssentials\Settings;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use TelegramBotEssentials\Essence\Contracts\ResolvesBotLocale;
use TelegramBotEssentials\Essence\Events\BotUpdateReceived;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Exceptions\TbeLogicException;
use TelegramBotEssentials\Settings\DTOs\Setting;
use TelegramBotEssentials\Settings\Enums\SettingType;
use TelegramBotEssentials\Settings\Listeners\LockActionsToChannelUsers;
use TelegramBotEssentials\Settings\Services\ChannelMembership;
use TelegramBotEssentials\Settings\Services\LocaleRegistry;
use TelegramBotEssentials\Settings\Services\Settings;
use TelegramBotEssentials\Settings\Support\TbeSettingsBotLocaleResolver;
use TelegramBotEssentials\Settings\Telegram\CallbackQueries\Admin\BotSettingsQuery;
use TelegramBotEssentials\Settings\Telegram\CallbackQueries\Member\ChannelLockQuery;
use TelegramBotEssentials\Settings\Telegram\StateAnswers\Admin\BotSettingsAnswer;

class TbeSettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Settings::class, fn () => new Settings);
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

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'tbe-settings');

        // In boot(): every provider's register() runs before any provider's
        // boot(), so this always overrides essence's own register()-phase
        // default binding, regardless of package load order.
        $this->app->bind(ResolvesBotLocale::class, TbeSettingsBotLocaleResolver::class);

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
            description: fn () => __('tbe-settings::bot_settings.locale.description'),
        ));

        settings()->addSetting(new Setting(
            key: 'channel_lock',
            label: fn () => __('tbe-settings::bot_settings.channel_lock.label'),
            type: SettingType::DIRECTORY,
            description: fn () => __('tbe-settings::bot_settings.channel_lock.description'),
        ));

        settings()->addSetting(new Setting(
            key: 'channel_lock.status',
            label: fn () => __('tbe-settings::bot_settings.channel_lock.status.label'),
            type: SettingType::CHECKBOX,
            default: false,
            description: fn () => __('tbe-settings::bot_settings.channel_lock.status.description'),
        ));

        settings()->addSetting(new Setting(
            key: 'channel_lock.channel_id',
            label: fn () => __('tbe-settings::bot_settings.channel_lock.channel_id.label'),
            type: SettingType::TEXT,
            description: fn () => __('tbe-settings::bot_settings.channel_lock.channel_id.description'),
            beforeSet: function (mixed $value) {
                if (! $value) {
                    return $value;
                }

                $channelId = ltrim($value, '@');
                if (! app(ChannelMembership::class)->isBotAdmin($channelId)) {
                    throw new TbeLogicException(__('tbe-settings::bot_settings.channel_lock.channel_id.bot_not_admin'));
                }

                return $value;
            },
        ));
    }

    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../lang' => resource_path('lang/vendor/tbe-settings'),
            ], 'tbe-settings-translations');
        }
    }
}
