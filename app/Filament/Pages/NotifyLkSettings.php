<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\Notifications\NotifyLkSmsTemplateService;
use App\Support\NotifyLkConfig;
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
class NotifyLkSettings extends Page
{
    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Notify.lk SMS';

    protected ?string $heading = 'Notify.lk SMS settings';

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
            Section::make('Credentials')
                ->schema($this->credentialsFields()),
            Section::make('Automation')
                ->schema($this->automationFields()),
            Section::make('Templates')
                ->schema($this->templateFields()),
        ]);
    }

    /**
     * @return array<Component>
     */
    protected function credentialsFields(): array
    {
        return [
            Forms\Components\TextInput::make('user_id')
                ->label('User ID')
                ->maxLength(255)
                ->nullable(),
            Forms\Components\TextInput::make('api_key')
                ->label('API key')
                ->password()
                ->revealable()
                ->maxLength(255)
                ->nullable(),
            Forms\Components\TextInput::make('sender_id')
                ->label('Sender ID')
                ->maxLength(255)
                ->nullable()
                ->helperText('Use your approved sender ID or NotifyDEMO for testing.'),
            Forms\Components\TextInput::make('base_url')
                ->label('Base URL')
                ->url()
                ->maxLength(255)
                ->nullable()
                ->helperText('Defaults to https://app.notify.lk/api/v1.'),
        ];
    }

    /**
     * @return array<Component>
     */
    protected function automationFields(): array
    {
        return [
            Forms\Components\Toggle::make('auto_send_account_created')
                ->label('Send SMS on account creation')
                ->helperText('Send a welcome message when a customer account is created.'),
            Forms\Components\Toggle::make('auto_send_order_placed')
                ->label('Send SMS on order placed')
                ->helperText('Send an order confirmation message after a purchase.'),
        ];
    }

    /**
     * @return array<Component>
     */
    protected function templateFields(): array
    {
        return [
            Forms\Components\Textarea::make('template_account_created')
                ->label('Account created template')
                ->rows(4)
                ->maxLength(320)
                ->helperText('Placeholders: {customer_name}, {store_name}.'),
            Forms\Components\Textarea::make('template_order_placed')
                ->label('Order placed template')
                ->rows(4)
                ->maxLength(320)
                ->helperText('Placeholders: {customer_name}, {store_name}, {order_number}, {currency}, {grand_total}.'),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                FormComponent::make([EmbeddedSchema::make('form')])
                    ->id('notify-lk-settings-form')
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

            if (is_bool($value)) {
                Setting::setValue($settingKey, $value ? '1' : '0');

                continue;
            }

            Setting::setValue($settingKey, blank($value) ? null : (string) $value);
        }

        NotifyLkConfig::apply();

        $this->callHook('afterSave');

        Notification::make()
            ->title('Notify.lk settings updated')
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
            'user_id' => 'notifylk.user_id',
            'api_key' => 'notifylk.api_key',
            'sender_id' => 'notifylk.sender_id',
            'base_url' => 'notifylk.base_url',
            'auto_send_account_created' => 'notifylk.auto_send_account_created',
            'auto_send_order_placed' => 'notifylk.auto_send_order_placed',
            'template_account_created' => 'notifylk.template_account_created',
            'template_order_placed' => 'notifylk.template_order_placed',
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

        $templateService = app(NotifyLkSmsTemplateService::class);

        $state['auto_send_account_created'] = $this->resolveBoolSetting('notifylk.auto_send_account_created', true);
        $state['auto_send_order_placed'] = $this->resolveBoolSetting('notifylk.auto_send_order_placed', true);
        $state['template_account_created'] ??= $templateService->defaultAccountCreatedTemplate();
        $state['template_order_placed'] ??= $templateService->defaultOrderPlacedTemplate();

        return $state;
    }

    private function resolveBoolSetting(string $key, bool $default): bool
    {
        $value = Setting::getValue($key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
