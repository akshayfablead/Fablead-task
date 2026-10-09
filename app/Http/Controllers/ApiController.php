<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendOtpRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Models\Record;
use App\Models\User;
use App\Services\GoogleCalendarService;
use App\Services\TwilioService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;

class ApiController extends Controller
{
    public function home(): JsonResponse
    {
        return response()->json([
            'message' => 'API is running.',
            'dashboard' => route('api.dashboard'),
        ]);
    }

    public function loginForm(): JsonResponse
    {
        return response()->json([
            'message' => 'Submit email and password to login.',
            'fields' => [
                'email',
                'password',
            ],
        ]);
    }

    public function registerForm(): JsonResponse
    {
        return response()->json([
            'message' => 'Submit registration details to receive OTP.',
            'fields' => [
                'name',
                'email',
                'phone',
                'password',
                'password_confirmation',
            ],
        ]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Dashboard loaded successfully.',
            'user' => $request->user()->load('roles'),
            'phone_verified' => (bool) session('phone_verified', false),
            'verified_phone' => session('verified_phone'),
        ]);
    }

    public function login(Request $request): JsonResponse
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

        return response()->json([
            'message' => 'Logged in successfully.',
            'user' => $request->user()->load('roles'),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function sendRegisterOtp(
        Request $request,
        TwilioService $twilio
    ): JsonResponse {
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

            return response()->json([
                'message' => $message,
                'errors' => [
                    'phone' => [$message],
                ],
            ], 422);
        }

        return response()->json([
            'message' => 'OTP sent successfully.',
            'phone' => $data['phone'],
        ]);
    }

    public function showRegisterVerify(): JsonResponse
    {
        $phone = session('register.phone');

        if (! $phone) {
            return response()->json([
                'message' => 'Please complete registration details first.',
            ], 422);
        }

        return response()->json([
            'phone' => $phone,
        ]);
    }

    public function verifyRegisterOtp(
        Request $request,
        TwilioService $twilio
    ): JsonResponse {
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

        if (! $data || ! $phone || $phone !== $request->input('phone')) {
            return response()->json([
                'message' => 'Invalid registration OTP session. Please try again.',
            ], 422);
        }

        if (! $twilio->verifyOtp($phone, $request->input('otp'))) {
            return response()->json([
                'message' => 'Invalid or expired OTP.',
                'errors' => [
                    'otp' => ['Invalid or expired OTP.'],
                ],
            ], 422);
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

            return response()->json([
                'message' => 'Email or phone number is already registered.',
            ], 422);
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

        return response()->json([
            'message' => 'Registration completed successfully.',
            'user' => $user->load('roles'),
        ], 201);
    }

    public function resendRegisterOtp(TwilioService $twilio): JsonResponse
    {
        $phone = session('register.phone');

        if (! $phone) {
            return response()->json([
                'message' => 'Please complete registration details first.',
            ], 422);
        }

        $twilio->sendOtp($phone);

        return response()->json([
            'message' => 'A new OTP has been sent.',
            'phone' => $phone,
        ]);
    }

    public function googleRedirect(): JsonResponse
    {
        return response()->json([
            'redirect_url' => Socialite::driver('google')->redirect()->getTargetUrl(),
        ]);
    }

    public function googleCallback(Request $request): JsonResponse
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
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Logged in with Google successfully.',
            'user' => $user->load('roles'),
        ]);
    }

    public function phoneForm(): JsonResponse
    {
        return response()->json([
            'message' => 'Submit phone to receive OTP.',
            'phone' => session('otp.phone'),
        ]);
    }

    public function sendOtp(
        SendOtpRequest $request,
        TwilioService $twilio
    ): JsonResponse {
        $phone = $request->validated('phone');

        $twilio->sendOtp($phone);

        session([
            'otp.phone' => $phone,
        ]);

        return response()->json([
            'message' => 'OTP sent successfully.',
            'phone' => $phone,
        ]);
    }

    public function showVerifyOtp(): JsonResponse
    {
        $phone = session('otp.phone');

        if (! $phone) {
            return response()->json([
                'message' => 'Please enter your phone number first.',
            ], 422);
        }

        return response()->json([
            'phone' => $phone,
        ]);
    }

    public function verifyOtp(
        VerifyOtpRequest $request,
        TwilioService $twilio
    ): JsonResponse {
        $phone = session('otp.phone');

        if (! $phone || $phone !== $request->validated('phone')) {
            return response()->json([
                'message' => 'Invalid OTP verification session.',
                'errors' => [
                    'phone' => ['Invalid OTP verification session.'],
                ],
            ], 422);
        }

        if (! $twilio->verifyOtp($phone, $request->validated('otp'))) {
            return response()->json([
                'message' => 'Invalid or expired OTP.',
                'errors' => [
                    'otp' => ['Invalid or expired OTP.'],
                ],
            ], 422);
        }

        session()->forget('otp.phone');

        session([
            'phone_verified' => true,
            'verified_phone' => $phone,
        ]);

        return response()->json([
            'message' => 'Phone number verified successfully.',
            'phone' => $phone,
        ]);
    }

    public function resendOtp(
        TwilioService $twilio
    ): JsonResponse {
        $phone = session('otp.phone');

        if (! $phone) {
            return response()->json([
                'message' => 'Please enter your phone number first.',
            ], 422);
        }

        $twilio->sendOtp($phone);

        return response()->json([
            'message' => 'A new OTP has been sent.',
            'phone' => $phone,
        ]);
    }

    public function recordsPage(): JsonResponse
    {
        Gate::authorize('viewAny', Record::class);

        $spreadsheetId = config('services.google_sheets.spreadsheet_id');

        return response()->json([
            'google_sheet_embed_url' => $spreadsheetId
                ? sprintf(
                    'https://docs.google.com/spreadsheets/d/%s/preview?widget=true&headers=false',
                    rawurlencode($spreadsheetId)
                )
                : null,
            'google_sheet_open_url' => $spreadsheetId
                ? sprintf(
                    'https://docs.google.com/spreadsheets/d/%s/edit',
                    rawurlencode($spreadsheetId)
                )
                : null,
        ]);
    }

    public function recordsIndex(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Record::class);

        $filters = $this->validateRecordFilters($request);

        $records = $this->buildRecordQuery($request, $filters)
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return response()->json([
            'data' => $records,
        ]);
    }

    public function googleCalendarConnect(
        Request $request,
        GoogleCalendarService $calendar
    ): JsonResponse {
        $state = Str::random(40);

        $request->session()->put(
            'google_calendar_state',
            $state
        );

        return response()->json([
            'redirect_url' => $calendar->authUrl($state),
        ]);
    }

    public function googleCalendarCallback(
        Request $request,
        GoogleCalendarService $calendar
    ): JsonResponse {
        abort_if(
            $request->query('state') !== session('google_calendar_state'),
            403,
            'Invalid Google Calendar state.'
        );

        session()->forget('google_calendar_state');

        if (! $request->filled('code')) {
            return response()->json([
                'message' => 'Google Calendar connection was cancelled.',
            ], 422);
        }

        try {
            $calendar->saveTokenFromCode(
                $request->user(),
                $request->query('code')
            );
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Google Calendar could not be connected.',
            ], 422);
        }

        return response()->json([
            'message' => 'Google Calendar connected successfully.',
        ]);
    }

    public function adminIndex(): JsonResponse
    {
        return response()->json([
            'accounts' => User::query()
                ->with('roles')
                ->orderBy('id')
                ->paginate(15),
            'roles' => Role::query()
                ->where('guard_name', 'web')
                ->with('permissions')
                ->orderBy('name')
                ->get(),
            'permissions' => config('access.permissions', []),
        ]);
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

    private function validateRecordFilters(Request $request): array
    {
        return $request->validate([
            'q' => [
                'nullable',
                'string',
                'max:150',
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'draft',
                    'active',
                    'inactive',
                ]),
            ],

            'creator' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'role' => [
                'nullable',
                'string',
                'max:100',
            ],
        ]);
    }

    private function buildRecordQuery(
        Request $request,
        array $filters
    ): Builder {
        $user = $request->user();

        return Record::query()
            ->visibleTo($user)
            ->with([
                'creator.roles',
            ])
            ->when(
                $filters['q'] ?? null,
                function (
                    Builder $query,
                    string $search
                ): void {
                    $search = '%' . $search . '%';

                    $query->where(function (
                        Builder $query
                    ) use ($search): void {
                        $query
                            ->where(
                                'title',
                                'like',
                                $search
                            )
                            ->orWhere(
                                'description',
                                'like',
                                $search
                            );
                    });
                }
            )
            ->when(
                $filters['status'] ?? null,
                function (
                    Builder $query,
                    string $status
                ): void {
                    $query->where(
                        'status',
                        $status
                    );
                }
            )
            ->when(
                $user->isAdmin()
                    && ($filters['creator'] ?? null),
                function (Builder $query) use ($filters): void {
                    $query->where(
                        'created_by',
                        $filters['creator']
                    );
                }
            )
            ->when(
                $user->isAdmin()
                    && ($filters['role'] ?? null),
                function (Builder $query) use ($filters): void {
                    $query->whereHas(
                        'creator.roles',
                        function (
                            Builder $query
                        ) use ($filters): void {
                            $query->where(
                                'name',
                                $filters['role']
                            );
                        }
                    );
                }
            );
    }
}
