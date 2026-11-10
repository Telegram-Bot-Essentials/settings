<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Settings\Tests;

use TelegramBotEssentials\Essence\Testing\WebTestCase as EssenceWebTestCase;
use TelegramBotEssentials\Settings\TbeSettingsServiceProvider;

abstract class WebTestCase extends EssenceWebTestCase
{
    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            TbeSettingsServiceProvider::class,
        ]);
    }
}
