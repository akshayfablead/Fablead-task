<?php

namespace App\Http\Controllers;

use App\Services\GoogleCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GoogleCalendarController extends Controller
{
    public function connect(
        Request $request,
        GoogleCalendarService $calendar
    ): RedirectResponse
    {
        $state = Str::random(40);

        $request->session()->put(
            'google_calendar_state',
            $state
        );

        return redirect()->away(
            $calendar->authUrl($state)
        );
    }

    public function callback(
        Request $request,
        GoogleCalendarService $calendar
    ): RedirectResponse {
        abort_if(
            $request->query('state') !== session('google_calendar_state'),
            403,
            'Invalid Google Calendar state.'
        );

        session()->forget('google_calendar_state');

        if (! $request->filled('code')) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Google Calendar connection was cancelled.');
        }

        try {
            $calendar->saveTokenFromCode(
                $request->user(),
                $request->query('code')
            );
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('dashboard')
                ->with('error', 'Google Calendar could not be connected.');
        }

        return redirect()
            ->route('dashboard')
            ->with('success', 'Google Calendar connected successfully.');
    }

    public function storeEvent(
        Request $request,
        GoogleCalendarService $calendar
    ): JsonResponse {
        $data = $request->validate([
            'summary' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'location' => [
                'nullable',
                'string',
                'max:255',
            ],

            'start_at' => [
                'required',
                'date',
            ],

            'end_at' => [
                'required',
                'date',
                'after:start_at',
            ],

            'timezone' => [
                'nullable',
                'timezone',
            ],

            'calendar_id' => [
                'nullable',
                'string',
                'max:255',
            ],

            'attendees' => [
                'nullable',
                'array',
            ],

            'attendees.*' => [
                'required',
                'email',
            ],
        ]);

        try {
            $event = $calendar->createEvent(
                $request->user(),
                $data
            );
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Google Calendar event created successfully.',
            'event' => [
                'id' => $event->getId(),
                'html_link' => $event->getHtmlLink(),
                'summary' => $event->getSummary(),
            ],
        ], 201);
    }
}
