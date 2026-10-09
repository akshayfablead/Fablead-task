<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WhatsAppService
{
    public function sendTemplateMessage(
        string $to,
        string $templateName,
        string $languageCode = 'en_US',
        array $components = []
    ): array {
        return $this->sendMessage([
            'to' => $this->normalizePhoneNumber($to),
            'type' => 'template',
            'template' => array_filter([
                'name' => $templateName,
                'language' => [
                    'code' => $languageCode,
                ],
                'components' => $components,
            ]),
        ]);
    }

    public function sendTextMessage(string $to, string $message): array
    {
        return $this->sendMessage([
            'to' => $this->normalizePhoneNumber($to),
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $message,
            ],
        ]);
    }

    private function sendMessage(array $payload): array
    {
        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->asJson()
            ->timeout(15)
            ->retry(2, 300, throw: false)
            ->post($this->messagesUrl(), [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                ...$payload,
            ]);

        if ($response->failed()) {
            $this->throwApiException($response);
        }

        return $response->json();
    }

    private function messagesUrl(): string
    {
        return sprintf(
            'https://graph.facebook.com/%s/%s/messages',
            $this->config('api_version'),
            $this->config('phone_number_id')
        );
    }

    private function accessToken(): string
    {
        return $this->config('access_token');
    }

    private function config(string $key): string
    {
        $value = config("services.whatsapp.{$key}");

        if (! is_string($value) || trim($value) === '') {
            throw new RuntimeException("WhatsApp API configuration value [{$key}] is missing.");
        }

        return trim($value);
    }



    private function normalizePhoneNumber(string $phoneNumber): string
    {
        return ltrim(preg_replace('/\D+/', '', $phoneNumber), '0');
    }

    private function throwApiException(Response $response): never
    {
        $message = $response->json('error.message') ?: $response->body();

        throw new RuntimeException("WhatsApp API request failed: {$message}");
    }
}
