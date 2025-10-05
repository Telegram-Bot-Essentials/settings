<?php

namespace TelegramBotEssentials\Settings\DTOs;

use TelegramBotEssentials\Essence\Exceptions\TbeException;
use TelegramBotEssentials\Settings\Enums\SettingType;

class Setting
{
    public mixed $default;

    public function __construct(
        public string $key, public string $label, public SettingType $type,
        mixed  $default = null, public ?array $enums = null, public ?string $description = null
    ) {
        $this->setDefault($default);
    }

    private function setDefault(mixed $default): void
    {
        if ($this->type == SettingType::CHECKBOX) {
            $allowedValues = [true, false];
            if (!in_array($default, $allowedValues)) {
                throw new TbeException('Checkbox default value must be true or false');
            }
            $this->default = $default;
        }else{
            $this->default = $default;
        }
    }
}
