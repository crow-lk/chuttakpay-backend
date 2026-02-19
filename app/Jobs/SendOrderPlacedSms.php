<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\Notifications\NotifyLkSmsService;
use App\Services\Notifications\NotifyLkSmsTemplateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendOrderPlacedSms implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Order $order) {}

    /**
     * Execute the job.
     */
    public function handle(NotifyLkSmsTemplateService $templateService, NotifyLkSmsService $smsService): void
    {
        if (! $templateService->shouldSendOrderPlaced()) {
            return;
        }

        if (! $smsService->hasCredentials()) {
            return;
        }

        $recipient = $smsService->normalizeRecipient($this->resolveRecipient());

        if ($recipient === '' || ! $smsService->isValidRecipient($recipient)) {
            return;
        }

        $message = $templateService->orderPlacedMessage($this->order);

        if ($message === '') {
            return;
        }

        try {
            $smsService->send(
                to: $recipient,
                message: $message,
                contact: $this->contactPayload()
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function resolveRecipient(): string
    {
        if (filled($this->order->customer_phone)) {
            return (string) $this->order->customer_phone;
        }

        if (filled($this->order->user?->mobile)) {
            return (string) $this->order->user?->mobile;
        }

        return (string) data_get($this->order->shipping_address, 'phone', '');
    }

    /**
     * @return array{first_name: string, last_name: string, email: string}
     */
    private function contactPayload(): array
    {
        $name = trim((string) ($this->order->customer_name ?? $this->order->user?->name ?? ''));
        $parts = preg_split('/\s+/', $name, 2) ?: [];

        return [
            'first_name' => (string) ($parts[0] ?? ''),
            'last_name' => (string) ($parts[1] ?? ''),
            'email' => (string) ($this->order->customer_email ?? $this->order->user?->email ?? ''),
        ];
    }
}
