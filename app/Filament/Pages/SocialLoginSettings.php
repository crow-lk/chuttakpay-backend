<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\SocialiteConfig;
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
use Illuminate\Support\Arr;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class SocialLoginSettings extends Page
{
    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-user-circle';

    protected static UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Social login';

    protected ?string $heading = 'Social login settings';

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
            Section::make('Google')
                ->schema($this->providerFields('google', 'Google')),

            Section::make('Facebook')
                ->schema($this->providerFields('facebook', 'Facebook')),
        ]);
    }

    /**
     * @return array<Component>
     */
    protected function providerFields(string $provider, string $labelPrefix): array
    {
        return [
            Forms\Components\TextInput::make("{$provider}_client_id")
                ->label("{$labelPrefix} client ID")
                ->maxLength(255)
                ->nullable(),

            Forms\Components\TextInput::make("{$provider}_client_secret")
                ->label("{$labelPrefix} client secret")
                ->password()
                ->revealable()
                ->maxLength(255)
                ->nullable(),

            Forms\Components\TextInput::make("{$provider}_redirect")
                ->label("{$labelPrefix} redirect URL")
                ->url()
                ->maxLength(255)
                ->nullable()
                ->helperText('This must match the callback URL configured with the provider.'),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                FormComponent::make([EmbeddedSchema::make('form')])
                    ->id('social-login-form')
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
            $value = Arr::get($data, $field);

            Setting::setValue($settingKey, blank($value) ? null : (string) $value);
        }

        SocialiteConfig::apply();

        $this->callHook('afterSave');

        Notification::make()
            ->title('Social login settings updated')
            ->success()
            ->body('New credentials are now active.')
            ->send();
    }

    /**
     * @return array<string, string>
     */
    protected function getSettingMap(): array
    {
        return [
            'google_client_id' => 'socialite.google.client_id',
            'google_client_secret' => 'socialite.google.client_secret',
            'google_redirect' => 'socialite.google.redirect',
            'facebook_client_id' => 'socialite.facebook.client_id',
            'facebook_client_secret' => 'socialite.facebook.client_secret',
            'facebook_redirect' => 'socialite.facebook.redirect',
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
