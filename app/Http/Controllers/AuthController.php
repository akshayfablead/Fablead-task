<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TwilioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

use Laravel\Socialite\Facades\Socialite;


class AuthController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function register(): View
    {
        return view('auth.register');
    }

    public function sendRegisterOtp(
        Request $request,
        TwilioService $twilio
    ): JsonResponse|RedirectResponse
    {
        $data = $request->validate($this->registrationRules());

        session([
            'register.data' => $data,
            'register.phone' => $data['phone'],
        ]);

        session()->save();

        try {
            $twilio->sendOtp($data['phone']);
        } catch (\Throwable $exception) {
            Log::warning('Registration OTP send failed.', [
                'phone' => $data['phone'],
                'message' => $exception->getMessage(),
            ]);

            session()->forget([
                'register.data',
                'register.phone',
            ]);

            $message = 'OTP could not be sent. Please check the phone number and try again.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'errors' => [
                        'phone' => [$message],
                    ],
                ], 422);
            }

            return back()
                ->withInput($request->except([
                    'password',
                    'password_confirmation',
                ]))
                ->with('error', $message);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'OTP sent successfully.',
                'redirect' => route('register.verify'),
            ]);
        }

        return redirect()
            ->route('register.verify')
            ->with('success', 'OTP sent successfully.');
    }

    public function showRegisterVerify(): View|RedirectResponse
    {
        $phone = session('register.phone');

        if (!$phone) {
            return redirect()
                ->route('register')
                ->with('error', 'Please complete registration details first.');
        }

        return view('auth.register-verify', compact('phone'));
    }

    public function verifyRegisterOtp(
        Request $request,
        TwilioService $twilio
    ): JsonResponse|RedirectResponse
    {
        $request->validate([
            'phone' => [
                'required',
                'string',
                'regex:/^\+[1-9]\d{7,14}$/',
            ],

            'otp' => [
                'required',
                'digits:6',
            ],
        ]);

        $data = session('register.data');
        $phone = session('register.phone');

        if (!$data || !$phone || $phone !== $request->input('phone')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Invalid registration OTP session. Please try again.',
                    'redirect' => route('register'),
                ], 422);
            }

            return redirect()
                ->route('register')
                ->with('error', 'Invalid registration OTP session. Please try again.');
        }

        if (!$twilio->verifyOtp($phone, $request->input('otp'))) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Invalid or expired OTP.',
                    'errors' => [
                        'otp' => ['Invalid or expired OTP.'],
                    ],
                ], 422);
            }

            return back()
                ->withErrors([
                    'otp' => 'Invalid or expired OTP.',
                ])
                ->withInput();
        }

        if (
            User::query()
                ->where('email', $data['email'])
                ->orWhere('phone', $data['phone'])
                ->exists()
        ) {
            session()->forget([
                'register.data',
                'register.phone',
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Email or phone number is already registered.',
                    'redirect' => route('register'),
                ], 422);
            }

            return redirect()
                ->route('register')
                ->with('error', 'Email or phone number is already registered.');
        }

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'],
            ]);

            $role = Role::query()
                ->where('guard_name', 'web')
                ->where('name', 'User')
                ->first();

            if ($role) {
                $user->assignRole($role);
            }

            return $user;
        });

        session()->forget([
            'register.data',
            'register.phone',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Registration completed successfully.',
                'redirect' => route('dashboard'),
            ]);
        }

        return redirect()
            ->route('dashboard')
            ->with('success', 'Registration completed successfully.');
    }

    public function resendRegisterOtp(
        Request $request,
        TwilioService $twilio
    ): JsonResponse|RedirectResponse
    {
        $phone = session('register.phone');

        if (!$phone) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Please complete registration details first.',
                    'redirect' => route('register'),
                ], 422);
            }

            return redirect()
                ->route('register')
                ->with('error', 'Please complete registration details first.');
        }

        $twilio->sendOtp($phone);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'A new OTP has been sent.',
            ]);
        }

        return back()
            ->with('success', 'A new OTP has been sent.');
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $credentials = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
            ],

            'password' => [
                'required',
                'string',
            ],
        ]);

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => 'Email or password is incorrect.',
            ]);
        }

        $request->session()->regenerate();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Logged in successfully.',
                'redirect' => route('dashboard'),
            ]);
        }

        return redirect()->route('dashboard');
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function redirectToGoogle()
    {
         return Socialite::driver('google')->redirect();
    }


    public function handleGoogleCallback()
    {
         $googleUser = Socialite::driver('google')->user();

        $user = User::updateOrCreate(
            [
                'email' => $googleUser->getEmail(),
            ],
            [
                'name' => $googleUser->getName(),
                'google_id' => $googleUser->getId(),
            ]
        );

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    private function registrationRules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users'),
            ],

            'phone' => [
                'required',
                'string',
                'regex:/^\+[1-9]\d{7,14}$/',
                Rule::unique('users'),
            ],

            'password' => [
                'required',
                'string',
                Password::min(6),
                'confirmed',
            ],
        ];
    }
}
