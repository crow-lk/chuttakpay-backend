<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions as ActionsComponent;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form as FormComponent;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class BrandSettings extends Page
{
    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Brand';

    protected ?string $heading = 'Brand settings';

    protected static bool $shouldRegisterNavigation = true;

    public function mount(): void
    {
        $this->form->fill($this->getInitialFormState());
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Branding')
                ->schema($this->brandingFields())
                ->columns(2),
        ]);
    }

    /**
     * @return array<Component>
     */
    protected function brandingFields(): array
    {
        return [
            Forms\Components\FileUpload::make('logo_path')
                ->label('Logo')
                ->image()
                ->disk('public')
                ->directory('branding')
                ->nullable()
                ->helperText('Recommended: square logo, at least 256x256.'),
            Forms\Components\TextInput::make('company_name')
                ->label('Company name')
                ->maxLength(255)
                ->nullable()
                ->helperText('Leave blank to use the app name.'),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                FormComponent::make([EmbeddedSchema::make('form')])
                    ->id('brand-settings-form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        ActionsComponent::make($this->getFormActions())
                            ->alignment($this->getFormActionsAlignment())
                            ->fullWidth($this->hasFullWidthFormActions())
                            ->sticky($this->areFormActionsSticky())
                            ->key('form-actions'),
                    ]),
            ]);
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save changes')
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }

    public function getFormActionsAlignment(): string|Alignment
    {
        return Alignment::Start;
    }

    public function save(): void
    {
        $this->callHook('beforeValidate');

        $data = $this->form->getState();

        $this->callHook('afterValidate');

        $this->callHook('beforeSave');

        foreach ($this->getSettingMap() as $field => $settingKey) {
            $value = $data[$field] ?? null;

            Setting::setValue($settingKey, blank($value) ? null : (string) $value);
        }

        $this->callHook('afterSave');

        Notification::make()
            ->title('Brand settings updated')
            ->success()
            ->body('The logo and company name have been saved.')
            ->send();
    }

    /**
     * @return array<string, string>
     */
    protected function getSettingMap(): array
    {
        return [
            'logo_path' => 'brand.logo_path',
            'company_name' => 'brand.company_name',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getInitialFormState(): array
    {
        $state = [];

        foreach ($this->getSettingMap() as $field => $settingKey) {
            $state[$field] = Setting::getValue($settingKey);
        }

        return $state;
    }
}
