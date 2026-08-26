<?php

namespace TelegramBotEssentials\Settings\DTOs;

use Closure;
use TelegramBotEssentials\Essence\Exceptions\TbeException;
use TelegramBotEssentials\Settings\Enums\SettingType;

class Setting
{
    public mixed $default;

    public array|Closure|null $options;

    public function __construct(
        public string $key,
        public string|Closure $label,
        public SettingType $type,
        mixed $default = null,
        array|Closure|null $options = null,
        public string|Closure|null $description = null,
        public ?Closure $beforeSet = null,
        public ?Closure $afterSet = null,
        public string|array|Closure|null $rules = null,
    ) {
        $this->setDefault($default);
        $this->setOptions($options);
    }

    public function getLabel(): string
    {
        return $this->resolveString($this->label);
    }

    public function getDescription(): ?string
    {
        if ($this->description === null) {
            return null;
        }

        return $this->resolveString($this->description);
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getOptions(): array
    {
        if ($this->options instanceof Closure) {
            return ($this->options)();
        }

        return $this->options ?? [];
    }

    public function getOptionLabel(string|int $optionKey): string
    {
        $options = $this->getOptions();

        return (string) ($options[$optionKey] ?? $optionKey);
    }

    /**
     * @return array<int, mixed>
     */
    public function getRules(): array
    {
        $rules = $this->rules instanceof Closure ? ($this->rules)() : $this->rules;

        return match (true) {
            $rules === null => [],
            is_array($rules) => $rules,
            default => explode('|', $rules),
        };
    }

    public function callBeforeSet(mixed $value, mixed $oldValue): mixed
    {
        if ($this->beforeSet === null) {
            return $value;
        }

        return ($this->beforeSet)($value, $oldValue, $this);
    }

    public function callAfterSet(mixed $newValue, mixed $oldValue): void
    {
        if ($this->afterSet === null) {
            return;
        }

        ($this->afterSet)($newValue, $oldValue, $this);
    }

    private function resolveString(string|Closure $value): string
    {
        return $value instanceof Closure ? $value() : $value;
    }

    private function setDefault(mixed $default): void
    {
        if ($this->type == SettingType::CHECKBOX) {
            $allowedValues = [true, false];
            if (! in_array($default, $allowedValues, true)) {
                throw new TbeException('Checkbox default value must be true or false');
            }
            $this->default = $default;
        } else {
            $this->default = $default;
        }
    }

    private function setOptions(array|Closure|null $options): void
    {
        switch ($this->type) {
            case SettingType::ENUM:
            case SettingType::SELECT:
            case SettingType::MULTISELECT:
                if ($options === null) {
                    throw new TbeException('Options must be provided for setting type: '.$this->type->value);
                }
                if ($options instanceof Closure) {
                    break;
                }
                if ($options === []) {
                    throw new TbeException('Options must be provided for setting type: '.$this->type->value);
                }
                break;
        }
        $this->options = $options;
    }

    public function getTypeEmoji(): string
    {
        switch ($this->type) {
            case SettingType::CHECKBOX:
                return settings()->get($this->key) ? '✅' : '❌';
            case SettingType::SENSITIVE:
                return '🔒';
            case SettingType::NUMBER:
                return '🔢';
            case SettingType::TEXT:
                return '💬';
            case SettingType::ENUM:
                return '🧩';
            case SettingType::SELECT:
                return '📋';
            case SettingType::MULTISELECT:
                return '🗃️';
            case SettingType::DIRECTORY:
                return '📂';
        }

        return 'Unknown Setting Type';
    }
}
