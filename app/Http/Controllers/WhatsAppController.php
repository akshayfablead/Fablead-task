<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppController extends Controller
{
    public function send(Request $request, WhatsAppService $whatsApp)
    {
        $data = $request->validate([
            'to' => ['required', 'regex:/^\+?[1-9]\d{7,14}$/'],
            'message' => ['required', 'string', 'max:4096'],
        ]);

        try {
            $response = $whatsApp->sendTextMessage(
                $data['to'],
                $data['message']
            );

            return response()->json([
                'success' => true,
                'message' => 'WhatsApp message accepted by Meta.',
                'data' => $response,
            ]);
        } catch (Throwable $exception) {
            Log::error('WhatsApp message sending failed.', [
                'error' => $exception->getMessage(),
            ]);

            return $this->whatsAppFailureResponse($exception, 'Unable to send WhatsApp message.');
        }
    }

    public function sendTemplate(Request $request, WhatsAppService $whatsApp)
    {
        $data = $request->validate([
            'to' => ['required', 'regex:/^\+?[1-9]\d{7,14}$/'],
            'template_name' => ['required', 'string', 'max:512'],
            'language_code' => ['nullable', 'string', 'max:20'],
            'components' => ['nullable', 'array'],
        ]);

        try {
            $response = $whatsApp->sendTemplateMessage(
                $data['to'],
                $data['template_name'],
                $data['language_code'] ?? 'en_US',
                $data['components'] ?? []
            );

            return response()->json([
                'success' => true,
                'message' => 'WhatsApp template message accepted by Meta.',
                'data' => $response,
            ]);
        } catch (Throwable $exception) {
            Log::error('WhatsApp template sending failed.', [
                'error' => $exception->getMessage(),
            ]);

            return $this->whatsAppFailureResponse($exception, 'Unable to send WhatsApp template message.');
        }
    }

    public function verifyWebhook(Request $request)
    {
        $mode = $request->query('hub_mode') ?? $request->query('hub.mode');
        $token = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');
        $challenge = $request->query('hub_challenge') ?? $request->query('hub.challenge');

        if ($mode === 'subscribe' && $token === config('services.whatsapp.webhook_verify_token')) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    public function webhook(Request $request)
    {
        if (! $this->hasValidSignature($request)) {
            return response()->json(['message' => 'Invalid webhook signature.'], 403);
        }

        $payload = $request->all();

        foreach (Arr::get($payload, 'entry', []) as $entry) {
            foreach (Arr::get($entry, 'changes', []) as $change) {
                $value = Arr::get($change, 'value', []);

                foreach (Arr::get($value, 'messages', []) as $message) {
                    Log::info('WhatsApp message received.', [
                        'from' => Arr::get($message, 'from'),
                        'type' => Arr::get($message, 'type'),
                        'message' => $message,
                    ]);
                }

                foreach (Arr::get($value, 'statuses', []) as $status) {
                    Log::info('WhatsApp message status received.', [
                        'id' => Arr::get($status, 'id'),
                        'status' => Arr::get($status, 'status'),
                        'recipient_id' => Arr::get($status, 'recipient_id'),
                    ]);
                }
            }
        }

        return response()->json(['success' => true]);
    }

    private function hasValidSignature(Request $request): bool
    {
        $appSecret = config('services.whatsapp.app_secret');

        if (! is_string($appSecret) || trim($appSecret) === '') {
            return true;
        }

        $signature = $request->header('X-Hub-Signature-256');

        if (! is_string($signature) || ! str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);

        return hash_equals($expected, $signature);
    }

    private function whatsAppFailureResponse(Throwable $exception, string $fallbackMessage)
    {
        $message = $exception->getMessage();

        if (str_starts_with($message, 'WhatsApp API request failed: ')) {
            $message = substr($message, strlen('WhatsApp API request failed: '));
        }

        return response()->json([
            'success' => false,
            'message' => $message ?: $fallbackMessage,
        ], 502);
    }
}
