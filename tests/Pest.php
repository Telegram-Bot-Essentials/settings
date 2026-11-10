<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use TelegramBotEssentials\Settings\Tests\TestCase;
use TelegramBotEssentials\Settings\Tests\WebTestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature');
uses(WebTestCase::class, RefreshDatabase::class)->in('Web');
