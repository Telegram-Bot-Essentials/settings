<?php

namespace TelegramBotEssentials\Settings\Web\Manage;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;
use TelegramBotEssentials\Essence\Exceptions\TbeLogicException;
use TelegramBotEssentials\Essence\Web\Filament\PanelGroup;
use TelegramBotEssentials\Essence\Web\Filament\PanelPage;
use TelegramBotEssentials\Settings\DTOs\Setting;
use TelegramBotEssentials\Settings\Enums\SettingType;
use UnitEnum;

/**
 * Every registered bot setting on one page, grouped by its directories, as
 * the bot's settings menu. Saved through settings()->set(), so each value
 * passes the setting's own rules and hooks. Secrets are write-only: the
 * page never sends a stored one to the browser, and an empty box keeps it.
 *
 * @property-read Schema $form
 */
class BotSettingsPage extends PanelPage
{
    protected static ?string $ability = 'settings.manage';

    protected static ?string $slug = 'settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = PanelGroup::Bot;

    protected static ?int $navigationSort = 90;

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return (string) __('tbe-settings::web.nav');
    }

    public function getTitle(): string
    {
        return (string) __('tbe-settings::bot_settings.menu.title');
    }

    public function mount(): void
    {
        $this->form->fill($this->current());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components($this->components(null))->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([$this->formComponent()]);
    }

    private function formComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make([
                    Action::make('save')->label((string) __('tbe-settings::web.save'))->submit('save')->keyBindings(['mod+s']),
                ])->key('form-actions'),
            ]);
    }

    public function save(): void
    {
        // Raw: settings()->set() applies each setting's rules, and only to what
        // changed, so a stored value the options no longer list does not block
        // saving everything else.
        $state = $this->form->getRawState();
        $state = is_array($state) ? $state : $state->toArray();
        $current = $this->current();
        $saved = 0;
        $failed = false;

        foreach ($this->values() as $setting) {
            $field = self::field($setting->key);
            $value = $state[$field] ?? null;

            if ($setting->type === SettingType::SENSITIVE) {
                // Empty keeps the stored secret: the page never had it to show.
                if (! is_string($value) || $value === '') {
                    continue;
                }
            } elseif ($this->same($value, $current[$field] ?? null)) {
                continue;
            }

            try {
                settings()->set($setting->key, $this->forStorage($setting, $value));
                $saved++;
            } catch (ValidationException $e) {
                $this->addError('data.'.$field, $e->getMessage());
                $failed = true;
            } catch (TbeLogicException $e) {
                $this->addError('data.'.$field, $e->getMessage());
                $failed = true;
            }
        }

        $this->form->fill($this->current());

        if ($failed) {
            Notification::make()->title((string) __('tbe-settings::web.not_all_saved'))->danger()->send();

            return;
        }

        Notification::make()->title((string) __($saved > 0 ? 'tbe-settings::web.saved' : 'tbe-settings::web.nothing'))->success()->send();
    }

    /**
     * The fields under $directory (null: the top level), each directory as a
     * section of its own.
     *
     * @return list<Component|Field>
     */
    private function components(?string $directory): array
    {
        $loose = [];
        $sections = [];

        foreach ($this->childrenOf($directory) as $setting) {
            if ($setting->type === SettingType::DIRECTORY) {
                $children = $this->components($setting->key);
                if ($children !== []) {
                    $sections[] = Section::make($setting->getLabel())
                        ->description($setting->getDescription())
                        ->schema($children)
                        ->collapsible()
                        ->columns(2);
                }

                continue;
            }

            $loose[] = $this->input($setting);
        }

        if ($directory === null && $loose !== []) {
            return [Section::make((string) __('tbe-settings::web.general'))->schema($loose)->columns(2), ...$sections];
        }

        return [...$loose, ...$sections];
    }

    private function input(Setting $setting): Field
    {
        $name = self::field($setting->key);

        $field = match ($setting->type) {
            SettingType::CHECKBOX => Toggle::make($name),
            SettingType::NUMBER => TextInput::make($name)->numeric(),
            SettingType::SENSITIVE => TextInput::make($name)
                ->password()
                ->autocomplete('new-password')
                ->placeholder(fn (): string => (string) __(settings()->get($setting->key) !== null ? 'tbe-settings::web.secret_set' : 'tbe-settings::web.secret_unset')),
            SettingType::ENUM => Select::make($name)->options(fn (): array => self::enumOptions($setting)),
            SettingType::SELECT => Select::make($name)->options(fn (): array => self::selectOptions($setting)),
            SettingType::MULTISELECT => Select::make($name)->multiple()->options(fn (): array => self::selectOptions($setting)),
            default => TextInput::make($name),
        };

        return $field->label($setting->getLabel())->helperText($setting->getDescription());
    }

    /** @return array<string, mixed> the form's state, secrets left out */
    private function current(): array
    {
        $state = [];
        foreach ($this->values() as $setting) {
            if ($setting->type === SettingType::SENSITIVE) {
                $state[self::field($setting->key)] = null;

                continue;
            }

            $value = settings()->get($setting->key);
            $state[self::field($setting->key)] = match ($setting->type) {
                SettingType::CHECKBOX => (bool) $value,
                SettingType::MULTISELECT => array_values(array_map(fn (mixed $v): string => is_scalar($v) ? (string) $v : '', is_array($value) ? $value : [])),
                default => is_scalar($value) ? (string) $value : null,
            };
        }

        return $state;
    }

    private function forStorage(Setting $setting, mixed $value): mixed
    {
        return match ($setting->type) {
            SettingType::CHECKBOX => (bool) $value,
            // Only offered keys: the list is stored comma-joined, and its rule checks just "array".
            SettingType::MULTISELECT => array_values(array_intersect(
                array_map(fn (mixed $v): string => is_scalar($v) ? (string) $v : '', is_array($value) ? $value : []),
                array_keys(self::selectOptions($setting)),
            )),
            default => $value === '' ? null : $value,
        };
    }

    private function same(mixed $a, mixed $b): bool
    {
        if (is_array($a) || is_array($b)) {
            $text = fn (mixed $v): string => is_scalar($v) ? (string) $v : '';
            $a = is_array($a) ? array_map($text, array_values($a)) : [];
            $b = is_array($b) ? array_map($text, array_values($b)) : [];
            sort($a);
            sort($b);

            return $a === $b;
        }

        return (is_scalar($a) ? (string) $a : null) === (is_scalar($b) ? (string) $b : null);
    }

    /** @return list<Setting> every setting that holds a value */
    private function values(): array
    {
        $values = [];
        foreach (settings()->getSettings() as $setting) {
            if ($setting instanceof Setting && $setting->type !== SettingType::DIRECTORY) {
                $values[] = $setting;
            }
        }

        return $values;
    }

    /** @return list<Setting> the settings one level under $directory */
    private function childrenOf(?string $directory): array
    {
        $depth = $directory === null ? 0 : count(explode('.', $directory));
        $children = [];
        foreach (settings()->getSettings() as $setting) {
            if ($setting instanceof Setting
                && count(explode('.', $setting->key)) === $depth + 1
                && ($directory === null || str_starts_with($setting->key, $directory.'.'))) {
                $children[] = $setting;
            }
        }

        return $children;
    }

    /** Dots would nest the form's state, so a key is stored under a flat name. */
    public static function field(string $key): string
    {
        return str_replace('.', '__', $key);
    }

    /** @return array<string, string> */
    private static function selectOptions(Setting $setting): array
    {
        $options = [];
        foreach ($setting->getOptions() as $value => $label) {
            $options[(string) $value] = is_scalar($label) ? (string) $label : (string) $value;
        }

        return $options;
    }

    /** @return array<string, string> an enum's options are plain values */
    private static function enumOptions(Setting $setting): array
    {
        $options = [];
        foreach ($setting->getOptions() as $value) {
            if (is_scalar($value)) {
                $options[(string) $value] = (string) $value;
            }
        }

        return $options;
    }
}
