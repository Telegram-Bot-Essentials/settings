<?php

namespace TelegramBotEssentials\Settings;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use TelegramBotEssentials\Essence\Events\BotWebhookInitialized;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Settings\DTOs\Setting;
use TelegramBotEssentials\Settings\Enums\SettingType;
use TelegramBotEssentials\Settings\Listeners\SetBotLocale;
use TelegramBotEssentials\Settings\Services\LocaleRegistry;
use TelegramBotEssentials\Settings\Services\Settings;
use TelegramBotEssentials\Settings\Telegram\CallbackQueries\Admin\BotSettingsQuery;
use TelegramBotEssentials\Settings\Telegram\StateAnswers\Admin\BotSettingsAnswer;

class TbeSettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Settings::class, fn () => new Settings());
        $this->app->singleton(LocaleRegistry::class);
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

        callbackQueryBus()->addCallbackQueries([
            BotSettingsQuery::class,
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
