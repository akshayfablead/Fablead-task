@extends('layouts.app')

@section('content')
<script>
    window.currentUserId = @json(auth()->id());
    window.callIceServers = @json(config('calls.ice_servers'));
</script>

<div class="card workspace-surface">
    <div class="card-header bg-white py-3">
        <h1 class="h4 mb-1">Audio &amp; Video Calls</h1>
        <p class="text-muted mb-0">Make a private, peer-to-peer call with another user.</p>
    </div>

    <div class="card-body">
        <form id="callForm" class="row g-3 align-items-end">
            @csrf

            <div class="col-md-8">
                <label for="recipient_id" class="form-label">Call a user</label>
                <select id="recipient_id" name="recipient_id" class="form-select" required>
                    <option value="">Choose a user...</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} (ID: {{ $user->id }})</option>
                    @endforeach
                </select>
                @if ($users->isEmpty())
                    <div class="form-text">No other user accounts are available to call yet.</div>
                @endif
            </div>

            <div class="col-md-4 d-flex gap-2">
                <button type="button" id="audioCallBtn" class="btn btn-success flex-fill" @disabled($users->isEmpty())>
                    <i class="bi bi-telephone" aria-hidden="true"></i> Audio call
                </button>
                <button type="button" id="videoCallBtn" class="btn btn-primary flex-fill" @disabled($users->isEmpty())>
                    <i class="bi bi-camera-video" aria-hidden="true"></i> Video call
                </button>
            </div>
        </form>

        <div id="callStatus" class="alert alert-secondary mt-4 mb-3" role="status" aria-live="polite">
            Connecting to the calling service...
        </div>

        <section id="incomingCall" class="alert alert-info d-none" aria-labelledby="incomingCallText">
            <p id="incomingCallText" class="fw-semibold mb-3"></p>
            <div class="d-flex gap-2">
                <button id="acceptCallBtn" type="button" class="btn btn-success">Accept</button>
                <button id="rejectCallBtn" type="button" class="btn btn-outline-danger">Decline</button>
            </div>
        </section>

        <div id="callMedia" class="row g-3 mt-1 d-none">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-white fw-semibold">You</div>
                    <div class="ratio ratio-16x9 bg-dark">
                        <div id="localAudioPlaceholder" class="d-none align-items-center justify-content-center text-white">
                            Microphone ready
                        </div>
                        <video id="localVideo" autoplay muted playsinline class="w-100 h-100 object-fit-cover"></video>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-white fw-semibold" id="remoteParticipant">Other user</div>
                    <div class="ratio ratio-16x9 bg-dark">
                        <div id="remoteAudioPlaceholder" class="d-none align-items-center justify-content-center text-white">
                            Audio call
                        </div>
                        <video id="remoteVideo" autoplay playsinline class="w-100 h-100 object-fit-cover"></video>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3">
            <button id="muteBtn" type="button" class="btn btn-outline-secondary" disabled>Mute microphone</button>
            <button id="cameraBtn" type="button" class="btn btn-outline-secondary" disabled>Turn camera off</button>
            <button id="endCallBtn" type="button" class="btn btn-danger" disabled>End call</button>
        </div>
    </div>
</div>
@endsection
