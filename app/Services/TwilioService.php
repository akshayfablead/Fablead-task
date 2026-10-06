<?php

namespace App\Services;

use Twilio\Rest\Client;

class TwilioService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client(
            config('services.twilio.account_sid'),
            config('services.twilio.auth_token')
        );
    }

    public function sendOtp(string $phone): void
    {
        $this->client->verify->v2
            ->services(config('services.twilio.verify_service_sid'))
            ->verifications
            ->create($phone, 'sms');
    }

    public function verifyOtp(string $phone, string $otp): bool
    {
        $verification = $this->client->verify->v2
            ->services(config('services.twilio.verify_service_sid'))
            ->verificationChecks
            ->create([
                'to' => $phone,
                'code' => $otp,
            ]);

        return $verification->status === 'approved';
    }
}
