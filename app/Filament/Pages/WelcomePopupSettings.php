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
class WelcomePopupSettings extends Page
{
    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-sparkles';

    protected static UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Welcome popup';

    protected ?string $heading = 'Welcome popup settings';

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
            Section::make('Popup content')
                ->schema($this->popupFields())
                ->columns(2),
        ]);
    }

    /**
     * @return array<Component>
     */
    protected function popupFields(): array
    {
        return [
            Forms\Components\FileUpload::make('image_path')
                ->label('Image')
                ->image()
                ->disk('public')
                ->directory('welcome-popup')
                ->nullable()
                ->helperText('Recommended: 1200x1200 or larger square image.'),
            Forms\Components\TextInput::make('link_url')
                ->label('Link URL')
                ->url()
                ->maxLength(255)
                ->nullable()
                ->helperText('Optional link that opens when the popup is clicked.'),
            Forms\Components\Textarea::make('description')
                ->label('Description')
                ->rows(4)
                ->maxLength(500)
                ->nullable()
                ->columnSpanFull(),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                FormComponent::make([EmbeddedSchema::make('form')])
                    ->id('welcome-popup-settings-form')
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
            ->title('Welcome popup updated')
            ->success()
            ->body('The welcome popup content has been saved.')
            ->send();
    }

    /**
     * @return array<string, string>
     */
    protected function getSettingMap(): array
    {
        return [
            'image_path' => 'welcome_popup.image_path',
            'description' => 'welcome_popup.description',
            'link_url' => 'welcome_popup.link_url',
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
