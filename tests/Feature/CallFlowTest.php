<?php

namespace Tests\Feature;

use App\Events\CallSignal;
use App\Events\IncomingCall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class CallFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_call_page_lists_other_users_but_not_the_current_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user)
            ->get('/calls')
            ->assertOk()
            ->assertSee('<option value="'.$otherUser->id.'">', false)
            ->assertDontSee('<option value="'.$user->id.'">', false);
    }

    public function test_user_can_start_an_audio_or_video_call_to_another_user(): void
    {
        Event::fake();

        $caller = User::factory()->create();
        $recipient = User::factory()->create();

        $response = $this->actingAs($caller)
            ->postJson('/calls/start', [
                'recipient_id' => $recipient->id,
                'call_type' => 'video',
            ])
            ->assertOk()
            ->assertJsonPath('recipient_id', $recipient->id)
            ->assertJsonPath('recipient_name', $recipient->name);

        Event::assertDispatched(
            IncomingCall::class,
            fn (IncomingCall $event): bool => $event->recipientId === $recipient->id
                && $event->callerId === $caller->id
                && $event->callType === 'video'
                && $event->callId === $response->json('call_id')
        );
    }

    public function test_user_cannot_start_a_call_to_themselves(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/calls/start', [
                'recipient_id' => $user->id,
                'call_type' => 'audio',
            ])
            ->assertUnprocessable();
    }

    public function test_call_signals_are_sent_to_the_intended_user(): void
    {
        Event::fake();

        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $callId = (string) Str::uuid();

        $this->actingAs($sender)
            ->postJson('/calls/signal', [
                'recipient_id' => $recipient->id,
                'call_id' => $callId,
                'signal_type' => 'offer',
                'payload' => [
                    'call_type' => 'video',
                    'description' => [
                        'type' => 'offer',
                        'sdp' => 'test-sdp',
                    ],
                ],
            ])
            ->assertOk();

        Event::assertDispatched(
            CallSignal::class,
            fn (CallSignal $event): bool => $event->recipientId === $recipient->id
                && $event->senderId === $sender->id
                && $event->callId === $callId
                && $event->signalType === 'offer'
        );
    }

    public function test_broadcast_events_use_snake_case_payloads_for_the_call_client(): void
    {
        $incoming = new IncomingCall(2, 1, 'Caller', 'video', 'call-id');
        $signal = new CallSignal(2, 1, 'call-id', 'offer', [
            'call_type' => 'video',
        ]);

        $this->assertSame([
            'call_id' => 'call-id',
            'caller_id' => 1,
            'caller_name' => 'Caller',
            'call_type' => 'video',
        ], $incoming->broadcastWith());

        $this->assertSame([
            'call_id' => 'call-id',
            'sender_id' => 1,
            'signal_type' => 'offer',
            'payload' => [
                'call_type' => 'video',
            ],
        ], $signal->broadcastWith());
    }
}
