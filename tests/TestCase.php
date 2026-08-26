<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Settings\Tests;

use TelegramBotEssentials\Essence\Testing\TestCase as EssenceTestCase;
use TelegramBotEssentials\Settings\TbeSettingsServiceProvider;

abstract class TestCase extends EssenceTestCase
{
    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            TbeSettingsServiceProvider::class,
        ]);
    }
}
