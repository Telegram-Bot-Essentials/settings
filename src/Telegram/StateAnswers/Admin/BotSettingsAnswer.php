<?php

namespace TelegramBotEssentials\Settings\Telegram\StateAnswers\Admin;

use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Telegram\StateAnswers\StateAnswer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBotEssentials\Settings\Telegram\Features\Admin\BotSettingsFeature;

class BotSettingsAnswer extends StateAnswer
{
    protected string $type = 'BTSTNG';
    protected int $perm = Roles::ADMIN->value;
}
