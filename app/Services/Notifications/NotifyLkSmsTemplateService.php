<?php

namespace App\Services\Notifications;

use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Str;

class NotifyLkSmsTemplateService
{
    private const DEFAULT_ACCOUNT_CREATED_TEMPLATE = 'Welcome to {store_name}, {customer_name}. Your account is ready - consider this your private entry to refined pieces and attentive service.';

    private const DEFAULT_ORDER_PLACED_TEMPLATE = 'Thank you for your order {order_number} with {store_name}. We will prepare it with care and keep you updated. Total: {currency} {grand_total}.';

    public function accountCreatedMessage(User $user): string
    {
        $template = $this->templateValue('notifylk.template_account_created', self::DEFAULT_ACCOUNT_CREATED_TEMPLATE);

        return $this->finalizeMessage($this->renderTemplate($template, [
            '{customer_name}' => $this->resolveCustomerName($user->name ?? ''),
            '{store_name}' => $this->storeName(),
        ]));
    }

    public function orderPlacedMessage(Order $order): string
    {
        $template = $this->templateValue('notifylk.template_order_placed', self::DEFAULT_ORDER_PLACED_TEMPLATE);

        return $this->finalizeMessage($this->renderTemplate($template, [
            '{order_number}' => (string) ($order->order_number ?? ''),
            '{customer_name}' => $this->resolveCustomerName($this->resolveOrderCustomerName($order)),
            '{store_name}' => $this->storeName(),
            '{currency}' => (string) ($order->currency ?? 'LKR'),
            '{grand_total}' => number_format((float) ($order->grand_total ?? 0), 2, '.', ''),
        ]));
    }

    public function shouldSendAccountCreated(): bool
    {
        return $this->settingBool('notifylk.auto_send_account_created', true);
    }

    public function shouldSendOrderPlaced(): bool
    {
        return $this->settingBool('notifylk.auto_send_order_placed', true);
    }

    public function defaultAccountCreatedTemplate(): string
    {
        return self::DEFAULT_ACCOUNT_CREATED_TEMPLATE;
    }

    public function defaultOrderPlacedTemplate(): string
    {
        return self::DEFAULT_ORDER_PLACED_TEMPLATE;
    }

    private function templateValue(string $key, string $default): string
    {
        $value = Setting::getValue($key);

        return $value !== null ? $value : $default;
    }

    private function settingBool(string $key, bool $default): bool
    {
        $value = Setting::getValue($key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function renderTemplate(string $template, array $replacements): string
    {
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    private function finalizeMessage(string $message): string
    {
        $message = trim($message);

        if (Str::length($message) <= 320) {
            return $message;
        }

        return Str::limit($message, 320, '');
    }

    private function storeName(): string
    {
        return (string) config('app.name', 'Our store');
    }

    private function resolveCustomerName(string $name): string
    {
        $clean = trim($name);

        return $clean !== '' ? $clean : 'there';
    }

    private function resolveOrderCustomerName(Order $order): string
    {
        if (filled($order->customer_name)) {
            return $order->customer_name;
        }

        if (filled($order->user?->name)) {
            return (string) $order->user?->name;
        }

        $shippingFirst = data_get($order->shipping_address, 'first_name');
        $shippingLast = data_get($order->shipping_address, 'last_name');

        return trim(($shippingFirst ?? '').' '.($shippingLast ?? ''));
    }
}
