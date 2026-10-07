<?php

namespace App\Services;

use App\Models\User;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Illuminate\Support\Carbon;

class GoogleCalendarService
{
    private function client(): Client
    {
        $client = new Client();

        $client->setClientId(
            config('services.google.client_id')
        );

        $client->setClientSecret(
            config('services.google.client_secret')
        );

        $client->setRedirectUri(
            config('services.google_calendar.redirect')
        );

        $client->setScopes([
            Calendar::CALENDAR_EVENTS,
        ]);

        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return $client;
    }

    public function authUrl(string $state): string
    {
        $client = $this->client();

        $client->setState($state);

        return $client->createAuthUrl();
    }

    public function saveTokenFromCode(
        User $user,
        string $code
    ): void {
        $client = $this->client();

        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            throw new \RuntimeException(
                $token['error_description']
                    ?? $token['error']
            );
        }

        $existingToken = $user->googleCalendarToken;

        $user->googleCalendarToken()->updateOrCreate(
            [
                'user_id' => $user->id,
            ],
            [
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token']
                    ?? $existingToken?->refresh_token,
                'expires_at' => now()->addSeconds(
                    $token['expires_in'] ?? 3600
                ),
            ]
        );
    }

    public function createEvent(
        User $user,
        array $data
    ): Event {
        $client = $this->authorizedClient($user);

        $calendar = new Calendar($client);

        $timezone = $data['timezone']
            ?? config(
                'services.google_calendar.timezone',
                'Asia/Kolkata'
            );

        $event = new Event([
            'summary' => $data['summary'],

            'description' => $data['description'] ?? null,

            'location' => $data['location'] ?? null,

            'start' => new EventDateTime([
                'dateTime' => $this->localDateTime(
                    $data['start_at'],
                    $timezone
                ),
                'timeZone' => $timezone,
            ]),

            'end' => new EventDateTime([
                'dateTime' => $this->localDateTime(
                    $data['end_at'],
                    $timezone
                ),
                'timeZone' => $timezone,
            ]),
        ]);

        if (! empty($data['attendees'])) {
            $event->setAttendees(
                collect($data['attendees'])
                    ->map(
                        fn (string $email): array => [
                            'email' => $email,
                        ]
                    )
                    ->values()
                    ->all()
            );
        }

        return $calendar->events->insert(
            $data['calendar_id'] ?? 'primary',
            $event
        );
    }

    private function localDateTime(
        string $value,
        string $timezone
    ): string {
        return Carbon::createFromFormat(
            'Y-m-d\TH:i',
            $value,
            $timezone
        )->format('Y-m-d\TH:i:s');
    }

    private function authorizedClient(User $user): Client
    {
        $token = $user->googleCalendarToken;

        if (! $token) {
            throw new \RuntimeException(
                'Google Calendar is not connected.'
            );
        }

        $client = $this->client();

        $client->setAccessToken([
            'access_token' => $token->access_token,
            'refresh_token' => $token->refresh_token,
            'expires_in' => $token->expires_at
                ? max(
                    1,
                    now()->diffInSeconds(
                        $token->expires_at,
                        false
                    )
                )
                : 3600,
            'created' => now()->timestamp,
        ]);

        if (
            $token->expires_at
            && $token->expires_at->lte(now()->addMinute())
        ) {
            if (! $token->refresh_token) {
                throw new \RuntimeException(
                    'Google Calendar token expired. Please reconnect.'
                );
            }

            $newToken = $client->fetchAccessTokenWithRefreshToken(
                $token->refresh_token
            );

            if (isset($newToken['error'])) {
                throw new \RuntimeException(
                    $newToken['error_description']
                        ?? $newToken['error']
                );
            }

            $token->update([
                'access_token' => $newToken['access_token'],
                'refresh_token' => $newToken['refresh_token']
                    ?? $token->refresh_token,
                'expires_at' => now()->addSeconds(
                    $newToken['expires_in'] ?? 3600
                ),
            ]);

            $client->setAccessToken([
                'access_token' => $token->fresh()->access_token,
                'refresh_token' => $token->refresh_token,
            ]);
        }

        return $client;
    }
}
