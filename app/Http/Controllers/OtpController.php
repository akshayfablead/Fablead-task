<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendOtpRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Services\TwilioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OtpController extends Controller
{
    public function __construct(
        private TwilioService $twilio
    ) {
    }

    public function create(): View
    {
        return view('auth.phone');
    }

    public function send(SendOtpRequest $request): RedirectResponse
    {
        $phone = $request->validated('phone');

        $this->twilio->sendOtp($phone);

        session([
            'otp.phone' => $phone,
        ]);

        return redirect()
            ->route('otp.verify')
            ->with('success', 'OTP sent successfully.');
    }

    public function showVerify(): View|RedirectResponse
    {
        $phone = session('otp.phone');

        if (!$phone) {
            return redirect()
                ->route('otp.phone')
                ->with('error', 'Please enter your phone number first.');
        }

        return view('auth.verify-otp', compact('phone'));
    }

    public function verify(VerifyOtpRequest $request): RedirectResponse
    {
        $phone = session('otp.phone');

        if (!$phone || $phone !== $request->validated('phone')) {
            return back()
                ->withErrors([
                    'phone' => 'Invalid OTP verification session.',
                ])
                ->withInput();
        }

        $verified = $this->twilio->verifyOtp(
            $phone,
            $request->validated('otp')
        );

        if (!$verified) {
            return back()
                ->withErrors([
                    'otp' => 'Invalid or expired OTP.',
                ])
                ->withInput();
        }

        session()->forget('otp.phone');

        session([
            'phone_verified' => true,
            'verified_phone' => $phone,
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Phone number verified successfully.');
    }

    public function resend(): RedirectResponse
    {
        $phone = session('otp.phone');

        if (!$phone) {
            return redirect()
                ->route('otp.phone')
                ->with('error', 'Please enter your phone number first.');
        }

        $this->twilio->sendOtp($phone);

        return back()
            ->with('success', 'A new OTP has been sent.');
    }
}
