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
class HeroImageSettings extends Page
{
    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-photo';

    protected static UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Hero image';

    protected ?string $heading = 'Hero image settings';

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
            Section::make('Hero image')
                ->schema($this->heroFields()),
        ]);
    }

    /**
     * @return array<Component>
     */
    protected function heroFields(): array
    {
        return [
            Forms\Components\FileUpload::make('image_path')
                ->label('Image')
                ->image()
                ->disk('public')
                ->directory('hero-images')
                ->nullable()
                ->helperText('Recommended: 1920x1080 or larger.'),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                FormComponent::make([EmbeddedSchema::make('form')])
                    ->id('hero-image-settings-form')
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
            ->title('Hero image updated')
            ->success()
            ->body('The hero image has been saved.')
            ->send();
    }

    /**
     * @return array<string, string>
     */
    protected function getSettingMap(): array
    {
        return [
            'image_path' => 'hero.image_path',
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
