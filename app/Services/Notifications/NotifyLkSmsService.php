<?php

namespace App\Services\Notifications;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class NotifyLkSmsService
{
    private const DEFAULT_BASE_URL = 'https://app.notify.lk/api/v1';

    /**
     * @param  array{first_name?: string|null, last_name?: string|null, email?: string|null, address?: string|null, group?: int|null}  $contact
     * @return array<string, mixed>
     */
    public function send(string $to, string $message, array $contact = [], ?string $type = null): array
    {
        $response = $this->request()->post(
            $this->resolveUrl('/send'),
            $this->buildPayload($to, $message, $contact, $type)
        );

        $response->throw();

        return $response->json();
    }

    public function hasCredentials(): bool
    {
        return filled(config('services.notifylk.user_id'))
            && filled(config('services.notifylk.api_key'));
    }

    public function normalizeRecipient(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if (Str::startsWith($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (
            Str::startsWith($digits, '94')
            && Str::length($digits) === 12
            && substr($digits, 2, 1) === '0'
        ) {
            $digits = '94'.substr($digits, 3);
        }

        if (Str::startsWith($digits, '0') && Str::length($digits) === 10) {
            $digits = '94'.substr($digits, 1);
        }

        if (Str::length($digits) === 9 && Str::startsWith($digits, '7')) {
            $digits = '94'.$digits;
        }

        return $digits;
    }

    public function isValidRecipient(string $recipient): bool
    {
        return (bool) preg_match('/^\d{11}$/', $recipient);
    }

    private function request(): PendingRequest
    {
        return Http::asForm()->timeout(10);
    }

    private function resolveUrl(string $path): string
    {
        return $this->baseUrl().'/'.ltrim($path, '/');
    }

    /**
     * @param  array{first_name?: string|null, last_name?: string|null, email?: string|null, address?: string|null, group?: int|null}  $contact
     * @return array<string, mixed>
     */
    private function buildPayload(string $to, string $message, array $contact = [], ?string $type = null): array
    {
        $payload = [
            'user_id' => (string) config('services.notifylk.user_id'),
            'api_key' => (string) config('services.notifylk.api_key'),
            'message' => $message,
            'to' => $to,
            'sender_id' => (string) config('services.notifylk.sender_id'),
            'contact_fname' => (string) data_get($contact, 'first_name', ''),
            'contact_lname' => (string) data_get($contact, 'last_name', ''),
            'contact_email' => (string) data_get($contact, 'email', ''),
            'contact_address' => (string) data_get($contact, 'address', ''),
            'contact_group' => (int) data_get($contact, 'group', 0),
        ];

        if (filled($type)) {
            $payload['type'] = $type;
        }

        return $payload;
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.notifylk.base_url', self::DEFAULT_BASE_URL), '/');
    }
}
