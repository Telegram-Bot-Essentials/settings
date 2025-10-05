<?php

namespace TelegramBotEssentials\Settings\DTOs;

use TelegramBotEssentials\Essence\Exceptions\TbeException;
use TelegramBotEssentials\Settings\Enums\SettingType;

class Setting
{
    public mixed $default;
    public ?array $options;

    public function __construct(
        public string $key, public string $label, public SettingType $type,
        mixed         $default = null, ?array $options = null, public ?string $description = null
    )
    {
        $this->setDefault($default);
        $this->setEnums($options);
    }

    private function setDefault(mixed $default): void
    {
        if ($this->type == SettingType::CHECKBOX) {
            $allowedValues = [true, false];
            if (!in_array($default, $allowedValues)) {
                throw new TbeException('Checkbox default value must be true or false');
            }
            $this->default = $default;
        } else {
            $this->default = $default;
        }
    }

    private function setEnums(?array $options)
    {
        switch ($this->type) {
            case SettingType::ENUM:
            case SettingType::SELECT:
            case SettingType::MULTISELECT:
                if (empty($options)) {
                    throw new TbeException('Options must be provided for setting type: ' . $this->type->value);
                }
                break;
        }
        $this->options = $options;
    }

    public function getTypeEmoji(): string
    {
        switch ($this->type) {
            case SettingType::CHECKBOX:
                return 'z';
            case SettingType::SENSITIVE:
                return "🏷";
            case SettingType::NUMBER:
                return "⚖️";
            case SettingType::TEXT:
                return '🔑';
            case SettingType::ENUM:
                return '🗄';
            case SettingType::SELECT:
                return "📁";
            case SettingType::MULTISELECT:
                return '🗂';
        }
        return 'Unknown Setting Type';
    }
}
