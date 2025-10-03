<?php

namespace TelegramBotEssentials\Settings\DTOs;

use TelegramBotEssentials\Settings\Enums\SettingType;

class Setting
{
    public function __construct(
        public string $key, public string $label, public SettingType $type,
        public mixed  $default = null, public ?array $enums = null, public ?string $description = null
    ) {}
}
