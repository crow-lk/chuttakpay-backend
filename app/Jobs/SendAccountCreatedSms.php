<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Notifications\NotifyLkSmsService;
use App\Services\Notifications\NotifyLkSmsTemplateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendAccountCreatedSms implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly User $user) {}

    /**
     * Execute the job.
     */
    public function handle(NotifyLkSmsTemplateService $templateService, NotifyLkSmsService $smsService): void
    {
        if (! $templateService->shouldSendAccountCreated()) {
            return;
        }

        if (! $smsService->hasCredentials()) {
            return;
        }

        $recipient = $smsService->normalizeRecipient((string) ($this->user->mobile ?? ''));

        if ($recipient === '' || ! $smsService->isValidRecipient($recipient)) {
            return;
        }

        $message = $templateService->accountCreatedMessage($this->user);

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

    /**
     * @return array{first_name: string, last_name: string, email: string}
     */
    private function contactPayload(): array
    {
        $name = trim((string) ($this->user->name ?? ''));
        $parts = preg_split('/\s+/', $name, 2) ?: [];

        return [
            'first_name' => (string) ($parts[0] ?? ''),
            'last_name' => (string) ($parts[1] ?? ''),
            'email' => (string) ($this->user->email ?? ''),
        ];
    }
}
