<?php

namespace App\Filament\Actions;

use App\Models\Order;
use App\Services\Notifications\NotifyLkSmsService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;
use Throwable;

class SendOrderSmsAction
{
    public static function make(): Action
    {
        return Action::make('sendSms')
            ->label('Send SMS')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->modalHeading('Send Notify.lk SMS')
            ->modalSubmitActionLabel('Send')
            ->visible(fn (?Order $record): bool => (bool) $record?->exists)
            ->form([
                TextInput::make('to')
                    ->label('Recipient')
                    ->required()
                    ->tel()
                    ->maxLength(20)
                    ->helperText('Use 07XXXXXXXX, +94XXXXXXXXX, or 9477XXXXXXX. We normalize to 11 digits.')
                    ->default(fn (Order $record): ?string => self::defaultRecipient($record)),
                Select::make('quick_message')
                    ->label('Quick message')
                    ->options(self::quickMessages())
                    ->native(false)
                    ->placeholder('Custom message')
                    ->live()
                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                        if (blank($state)) {
                            return;
                        }

                        $set('message', self::quickMessages()[$state] ?? '');
                    }),
                Textarea::make('message')
                    ->label('Message')
                    ->required()
                    ->rows(4)
                    ->maxLength(320)
                    ->helperText('Supports placeholders: {order_number}, {customer_name}, {store_name}.'),
            ])
            ->action(function (Order $record, array $data): void {
                $smsService = app(NotifyLkSmsService::class);

                if (! $smsService->hasCredentials()) {
                    Notification::make()
                        ->title('Notify.lk not configured')
                        ->danger()
                        ->body('Set NOTIFYLK_USER_ID and NOTIFYLK_API_KEY in your .env file.')
                        ->send();

                    return;
                }

                $recipient = $smsService->normalizeRecipient((string) ($data['to'] ?? ''));
                $message = self::renderMessage((string) ($data['message'] ?? ''), $record);

                if ($recipient === '' || $message === '') {
                    Notification::make()
                        ->title('Missing SMS details')
                        ->danger()
                        ->body('Recipient and message are required.')
                        ->send();

                    return;
                }

                if (! $smsService->isValidRecipient($recipient)) {
                    Notification::make()
                        ->title('Invalid phone number')
                        ->danger()
                        ->body('Phone must be 11 digits like 9471XXXXXXX. Normalized: '.$recipient)
                        ->send();

                    return;
                }

                if (Str::length($message) > 320) {
                    Notification::make()
                        ->title('SMS is too long')
                        ->danger()
                        ->body('Message must be 320 characters or fewer after placeholders are replaced.')
                        ->send();

                    return;
                }

                try {
                    $smsService->send(
                        to: $recipient,
                        message: $message,
                        contact: self::buildContactPayload($record)
                    );
                } catch (Throwable $exception) {
                    Notification::make()
                        ->title('Failed to send SMS')
                        ->danger()
                        ->body($exception->getMessage())
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('SMS sent')
                    ->success()
                    ->body('Message sent to '.$recipient.'.')
                    ->send();
            });
    }

    /**
     * @return array<string, string>
     */
    private static function quickMessages(): array
    {
        return [
            'order_received' => 'Thanks for your order {order_number}. We will update you soon. - {store_name}',
            'order_ready' => 'Your order {order_number} is ready for pickup. - {store_name}',
            'order_shipped' => 'Good news! Your order {order_number} is on the way. - {store_name}',
        ];
    }

    private static function defaultRecipient(Order $record): ?string
    {
        if (filled($record->customer_phone)) {
            return $record->customer_phone;
        }

        if (filled($record->user?->mobile)) {
            return $record->user?->mobile;
        }

        $shippingPhone = data_get($record->shipping_address, 'phone');

        if (filled($shippingPhone)) {
            return $shippingPhone;
        }

        return null;
    }

    /**
     * @return array{first_name: string, last_name: string, email: string}
     */
    private static function buildContactPayload(Order $record): array
    {
        $fullName = trim((string) self::resolveCustomerName($record));
        $nameParts = preg_split('/\s+/', $fullName, 2) ?: [];

        return [
            'first_name' => (string) ($nameParts[0] ?? ''),
            'last_name' => (string) ($nameParts[1] ?? ''),
            'email' => (string) self::resolveCustomerEmail($record),
        ];
    }

    private static function resolveCustomerName(Order $record): string
    {
        if (filled($record->customer_name)) {
            return $record->customer_name;
        }

        if (filled($record->user?->name)) {
            return $record->user?->name ?? '';
        }

        $shippingFirst = data_get($record->shipping_address, 'first_name');
        $shippingLast = data_get($record->shipping_address, 'last_name');
        $shippingName = trim(($shippingFirst ?? '').' '.($shippingLast ?? ''));

        return $shippingName;
    }

    private static function resolveCustomerEmail(Order $record): string
    {
        if (filled($record->customer_email)) {
            return $record->customer_email;
        }

        if (filled($record->user?->email)) {
            return $record->user?->email ?? '';
        }

        return '';
    }

    private static function renderMessage(string $message, Order $record): string
    {
        $replacements = [
            '{order_number}' => (string) ($record->order_number ?? ''),
            '{customer_name}' => self::resolveCustomerName($record),
            '{store_name}' => (string) config('app.name', 'Our store'),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }
}
