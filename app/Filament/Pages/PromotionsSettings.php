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

class PromotionsSettings extends Page
{
    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-gift';

    protected static UnitEnum|string|null $navigationGroup = 'Promotions';

    protected static ?string $navigationLabel = 'Order discounts';

    protected ?string $heading = 'Order discount promotions';

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
            Section::make('First 100 orders')
                ->schema($this->first100OrdersFields()),
        ]);
    }

    /**
     * @return array<Component>
     */
    protected function first100OrdersFields(): array
    {
        return [
            Forms\Components\Toggle::make('first_100_orders_enabled')
                ->label('Enable first 100 orders discount')
                ->helperText('First-time customers get 25% off. Returning customers get 10% off while the promotion is active.'),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                FormComponent::make([EmbeddedSchema::make('form')])
                    ->id('promotions-settings-form')
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

        $this->callHook('afterSave');

        Notification::make()
            ->title('Promotions updated')
            ->success()
            ->body('Promotion settings are now active.')
            ->send();
    }

    /**
     * @return array<string, string>
     */
    protected function getSettingMap(): array
    {
        return [
            'first_100_orders_enabled' => 'promotions.first_100_orders_enabled',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getInitialFormState(): array
    {
        return [
            'first_100_orders_enabled' => $this->resolveBoolSetting('promotions.first_100_orders_enabled', false),
        ];
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
