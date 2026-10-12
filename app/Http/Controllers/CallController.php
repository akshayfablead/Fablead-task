<?php

namespace App\Http\Controllers;

use App\Events\CallSignal;
use App\Events\IncomingCall;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CallController extends Controller
{
    public function index(Request $request): View
    {
        return view('calls.index', [
            'users' => User::query()
                ->whereKeyNot($request->user()->getKey())
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function start(Request $request): JsonResponse
    {
        $data = $request->validate([
            'recipient_id' => ['required', 'integer', 'exists:users,id'],
            'call_type' => ['required', Rule::in(['audio', 'video'])],
        ]);

        $caller = $request->user();

        abort_if(
            $caller->id === (int) $data['recipient_id'],
            422,
            'You cannot call yourself.'
        );

        $recipient = User::findOrFail($data['recipient_id']);
        $callId = (string) Str::uuid();

        IncomingCall::dispatch(
            recipientId: (int) $recipient->id,
            callerId: (int) $caller->id,
            callerName: (string) ($caller->name ?? 'User'),
            callType: $data['call_type'],
            callId: $callId,
        );

        return response()->json([
            'message' => 'Call invitation sent.',
            'call_id' => $callId,
            'recipient_id' => $recipient->id,
            'recipient_name' => $recipient->name,
        ]);
    }

    public function signal(Request $request): JsonResponse
    {
        $data = $request->validate([
            'recipient_id' => ['required', 'integer', 'exists:users,id'],
            'call_id' => ['required', 'uuid'],
            'signal_type' => [
                'required',
                Rule::in([
                    'accept',
                    'reject',
                    'end',
                    'offer',
                    'answer',
                    'ice-candidate',
                ]),
            ],
            'payload' => ['sometimes', 'array'],
        ]);

        $sender = $request->user();
        $recipientId = (int) $data['recipient_id'];

        abort_if(
            $sender->id === $recipientId,
            422,
            'You cannot send call signals to yourself.'
        );

        CallSignal::dispatch(
            recipientId: $recipientId,
            senderId: (int) $sender->id,
            callId: $data['call_id'],
            signalType: $data['signal_type'],
            payload: $data['payload'] ?? [],
        );

        return response()->json([
            'message' => 'Call signal sent.',
        ]);
    }
}
