import axios from 'axios';

const callForm = document.getElementById('callForm');

if (callForm) {
    const statusBox = document.getElementById('callStatus');
    const incomingBox = document.getElementById('incomingCall');
    const incomingText = document.getElementById('incomingCallText');
    const mediaBox = document.getElementById('callMedia');
    const localVideo = document.getElementById('localVideo');
    const remoteVideo = document.getElementById('remoteVideo');
    const localAudioPlaceholder = document.getElementById('localAudioPlaceholder');
    const remoteAudioPlaceholder = document.getElementById('remoteAudioPlaceholder');
    const remoteParticipant = document.getElementById('remoteParticipant');
    const recipientSelect = document.getElementById('recipient_id');
    const audioBtn = document.getElementById('audioCallBtn');
    const videoBtn = document.getElementById('videoCallBtn');
    const acceptBtn = document.getElementById('acceptCallBtn');
    const rejectBtn = document.getElementById('rejectCallBtn');
    const muteBtn = document.getElementById('muteBtn');
    const cameraBtn = document.getElementById('cameraBtn');
    const endBtn = document.getElementById('endCallBtn');

    let peer = null;
    let localStream = null;
    let remoteStream = null;
    let currentCall = null;
    let pendingOffer = null;
    let queuedCandidates = [];
    let muted = false;
    let cameraOff = false;
    let startingCall = false;
    let realtimeAvailable = false;
    let callAnswered = false;
    let ringTimeout = null;

    axios.defaults.headers.common['X-CSRF-TOKEN'] =
        document.querySelector('meta[name="csrf-token"]')?.content;
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

    function setStatus(message, type = 'secondary') {
        statusBox.className = `alert alert-${type} mt-4 mb-3`;
        statusBox.textContent = message;
    }

    function updateCallControls() {
        const inCall = Boolean(currentCall);
        audioBtn.disabled = !realtimeAvailable || inCall || startingCall ||
            !recipientSelect.value;
        videoBtn.disabled = !realtimeAvailable || inCall || startingCall ||
            !recipientSelect.value;
        muteBtn.disabled = !localStream;
        cameraBtn.disabled = !localStream ||
            currentCall?.callType !== 'video';
        endBtn.disabled = !inCall;
    }

    async function postSignal(call, type, payload = {}) {
        await axios.post('/calls/signal', {
            recipient_id: call.peerId,
            call_id: call.callId,
            signal_type: type,
            payload,
        });
    }

    async function sendSignal(type, payload = {}) {
        if (!currentCall) {
            throw new Error('There is no active call.');
        }

        await postSignal(currentCall, type, payload);
    }

    async function startMedia(callType) {
        if (!navigator.mediaDevices?.getUserMedia) {
            throw new Error(
                'Your browser cannot access the microphone or camera. Use a supported browser over HTTPS.'
            );
        }

        localStream = await navigator.mediaDevices.getUserMedia({
            audio: true,
            video: callType === 'video',
        });

        localVideo.srcObject = localStream;
        const audioOnly = callType === 'audio';
        localVideo.classList.toggle('d-none', audioOnly);
        remoteVideo.classList.toggle('d-none', audioOnly);
        localAudioPlaceholder.classList.toggle('d-none', !audioOnly);
        remoteAudioPlaceholder.classList.toggle('d-none', !audioOnly);
        localAudioPlaceholder.classList.toggle('d-flex', audioOnly);
        remoteAudioPlaceholder.classList.toggle('d-flex', audioOnly);
        mediaBox.classList.remove('d-none');
        updateCallControls();
    }

    async function addQueuedCandidates() {
        if (!peer?.remoteDescription) {
            return;
        }

        const candidates = queuedCandidates;
        queuedCandidates = [];

        for (const candidate of candidates) {
            await peer.addIceCandidate(candidate);
        }
    }

    function createPeer() {
        peer = new RTCPeerConnection({
            iceServers: window.callIceServers || [
                { urls: 'stun:stun.l.google.com:19302' },
            ],
        });

        localStream.getTracks().forEach(track => {
            peer.addTrack(track, localStream);
        });

        peer.ontrack = event => {
            if (event.streams[0]) {
                remoteVideo.srcObject = event.streams[0];
            } else {
                remoteStream ||= new MediaStream();
                remoteStream.addTrack(event.track);
                remoteVideo.srcObject = remoteStream;
            }

            remoteVideo.play().catch(() => {
                setStatus(
                    'Call connected. Tap the remote video to start audio playback.',
                    'info'
                );
            });
        };

        peer.onicecandidate = event => {
            if (event.candidate && currentCall) {
                sendSignal('ice-candidate', {
                    candidate: event.candidate.toJSON(),
                }).catch(error => {
                    console.error('Unable to send network candidate.', error);
                    setStatus('Connection setup failed. Please try again.', 'danger');
                });
            }
        };

        peer.onconnectionstatechange = () => {
            if (peer?.connectionState === 'connected') {
                setStatus('Call connected.', 'success');
            } else if (peer?.connectionState === 'connecting') {
                setStatus('Connecting call...', 'info');
            } else if (peer?.connectionState === 'failed') {
                setStatus(
                    'Could not connect. Check your network or configure a TURN server.',
                    'danger'
                );
            } else if (peer?.connectionState === 'disconnected') {
                setStatus('Connection interrupted. Waiting for the network...', 'warning');
            }
        };
    }

    async function startCall(callType) {
        const recipientId = Number(recipientSelect.value);

        if (!recipientId) {
            setStatus('Choose a user to call first.', 'warning');
            return;
        }

        startingCall = true;
        updateCallControls();

        try {
            await startMedia(callType);

            const response = await axios.post('/calls/start', {
                recipient_id: recipientId,
                call_type: callType,
            });

            currentCall = {
                callId: response.data.call_id,
                peerId: recipientId,
                peerName: response.data.recipient_name,
                callType,
                incoming: false,
            };
            remoteParticipant.textContent = currentCall.peerName;
            createPeer();

            const offer = await peer.createOffer();
            await peer.setLocalDescription(offer);
            await sendSignal('offer', {
                call_type: callType,
                description: peer.localDescription.toJSON(),
            });

            setStatus(`Calling ${currentCall.peerName}...`, 'info');
            callAnswered = false;
            const callId = currentCall.callId;
            ringTimeout = window.setTimeout(async () => {
                if (currentCall?.callId !== callId || callAnswered) {
                    return;
                }

                try {
                    await sendSignal('end');
                } catch (error) {
                    console.error('Unable to notify the recipient that the call timed out.', error);
                }
                await cleanup();
                setStatus('No answer. The call has ended.', 'warning');
            }, 45_000);
            updateCallControls();
        } catch (error) {
            await cleanup();
            setStatus(
                error.response?.data?.message || error.message ||
                    'Unable to start the call.',
                'danger'
            );
        } finally {
            startingCall = false;
            updateCallControls();
        }
    }

    async function acceptCall() {
        if (!currentCall || !pendingOffer) {
            return;
        }

        acceptBtn.disabled = true;

        try {
            await startMedia(currentCall.callType);
            createPeer();
            await peer.setRemoteDescription(pendingOffer);
            await addQueuedCandidates();

            const answer = await peer.createAnswer();
            await peer.setLocalDescription(answer);
            await sendSignal('answer', {
                description: peer.localDescription.toJSON(),
            });

            pendingOffer = null;
            incomingBox.classList.add('d-none');
            setStatus('Connecting call...', 'info');
            updateCallControls();
        } catch (error) {
            setStatus(
                error.response?.data?.message || error.message ||
                    'Unable to accept the call.',
                'danger'
            );
            try {
                await sendSignal('reject');
            } catch (signalError) {
                console.error('Unable to notify caller that the call was declined.', signalError);
            }
            await cleanup();
        }
    }

    async function handleSignal(data) {
        if (!currentCall || data.call_id !== currentCall.callId) {
            if (data.signal_type !== 'offer' || currentCall) {
                return;
            }

            currentCall = {
                callId: data.call_id,
                peerId: data.sender_id,
                peerName: data.payload.caller_name || `User ${data.sender_id}`,
                callType: data.payload.call_type || 'audio',
                incoming: true,
            };
            remoteParticipant.textContent = currentCall.peerName;
        }

        if (Number(data.sender_id) !== Number(currentCall.peerId)) {
            return;
        }

        switch (data.signal_type) {
            case 'offer':
                if (!currentCall.incoming) {
                    return;
                }
                pendingOffer = data.payload.description;
                currentCall.callType = data.payload.call_type || currentCall.callType;
                incomingText.textContent =
                    `Incoming ${currentCall.callType} call from ${currentCall.peerName}`;
                acceptBtn.disabled = false;
                incomingBox.classList.remove('d-none');
                updateCallControls();
                setStatus('Incoming call...', 'info');
                break;

            case 'answer':
                if (peer && data.payload.description) {
                    await peer.setRemoteDescription(data.payload.description);
                    await addQueuedCandidates();
                    callAnswered = true;
                    window.clearTimeout(ringTimeout);
                    ringTimeout = null;
                    setStatus('Call accepted. Connecting...', 'info');
                }
                break;

            case 'ice-candidate':
                if (data.payload.candidate) {
                    if (peer?.remoteDescription) {
                        await peer.addIceCandidate(data.payload.candidate);
                    } else {
                        queuedCandidates.push(data.payload.candidate);
                    }
                }
                break;

            case 'accept':
                setStatus('Call accepted. Connecting...', 'info');
                break;

            case 'reject':
                await cleanup();
                setStatus('Call declined.', 'secondary');
                break;

            case 'end':
                await cleanup();
                setStatus('The other user ended the call.', 'secondary');
                break;
        }
    }

    async function cleanup() {
        if (peer) {
            peer.onicecandidate = null;
            peer.close();
            peer = null;
        }

        if (localStream) {
            localStream.getTracks().forEach(track => track.stop());
            localStream = null;
        }

        localVideo.srcObject = null;
        remoteVideo.srcObject = null;
        localVideo.classList.remove('d-none');
        remoteVideo.classList.remove('d-none');
        localAudioPlaceholder.classList.add('d-none');
        remoteAudioPlaceholder.classList.add('d-none');
        localAudioPlaceholder.classList.remove('d-flex');
        remoteAudioPlaceholder.classList.remove('d-flex');
        mediaBox.classList.add('d-none');
        incomingBox.classList.add('d-none');

        currentCall = null;
        pendingOffer = null;
        queuedCandidates = [];
        remoteStream = null;
        callAnswered = false;
        window.clearTimeout(ringTimeout);
        ringTimeout = null;
        muted = false;
        cameraOff = false;

        muteBtn.textContent = 'Mute microphone';
        cameraBtn.textContent = 'Turn camera off';
        acceptBtn.disabled = false;
        updateCallControls();
    }

    audioBtn.addEventListener('click', () => startCall('audio'));
    videoBtn.addEventListener('click', () => startCall('video'));
    recipientSelect.addEventListener('change', updateCallControls);
    acceptBtn.addEventListener('click', acceptCall);

    rejectBtn.addEventListener('click', async () => {
        try {
            await sendSignal('reject');
            await cleanup();
            setStatus('Call declined.', 'secondary');
        } catch (error) {
            setStatus(
                error.response?.data?.message || 'Unable to decline the call.',
                'danger'
            );
        }
    });

    endBtn.addEventListener('click', async () => {
        try {
            await sendSignal('end');
            await cleanup();
            setStatus('Call ended.', 'secondary');
        } catch (error) {
            setStatus(
                error.response?.data?.message || 'Unable to end the call.',
                'danger'
            );
        }
    });

    muteBtn.addEventListener('click', () => {
        muted = !muted;
        localStream?.getAudioTracks().forEach(track => {
            track.enabled = !muted;
        });

        muteBtn.textContent = muted ? 'Unmute microphone' : 'Mute microphone';
    });

    cameraBtn.addEventListener('click', () => {
        cameraOff = !cameraOff;
        localStream?.getVideoTracks().forEach(track => {
            track.enabled = !cameraOff;
        });

        cameraBtn.textContent = cameraOff ? 'Turn camera on' : 'Turn camera off';
    });

    if (window.Echo && window.currentUserId) {
        const channel = window.Echo.private(`users.${window.currentUserId}`)
            .listen('.incoming.call', event => {
                if (currentCall?.callId === event.call_id) {
                    currentCall.peerName = event.caller_name;
                    remoteParticipant.textContent = event.caller_name;
                    incomingText.textContent =
                        `Incoming ${event.call_type} call from ${event.caller_name}`;
                    return;
                }

                if (currentCall || startingCall) {
                    postSignal({
                        callId: event.call_id,
                        peerId: event.caller_id,
                    }, 'reject').catch(error => {
                        console.error('Unable to decline the incoming call.', error);
                    });
                    return;
                }

                currentCall = {
                    callId: event.call_id,
                    peerId: event.caller_id,
                    peerName: event.caller_name,
                    callType: event.call_type,
                    incoming: true,
                };
                pendingOffer = null;
                acceptBtn.disabled = true;
                incomingText.textContent =
                    `Incoming ${event.call_type} call from ${event.caller_name}`;
                incomingBox.classList.remove('d-none');
                remoteParticipant.textContent = event.caller_name;
                setStatus('Incoming call...', 'info');
                updateCallControls();
            })
            .listen('.call.signal', event => {
                handleSignal(event).catch(error => {
                    console.error('Unable to process call signal.', error);
                    setStatus('Unable to process the call signal.', 'danger');
                });
            });

        channel.subscribed(() => {
            realtimeAvailable = true;
            updateCallControls();
            setStatus('Ready to call.', 'secondary');
        });
        channel.error(error => {
            realtimeAvailable = false;
            updateCallControls();
            console.error('Unable to subscribe to the private call channel.', error);
            setStatus('Could not connect to the calling service. Check Reverb configuration.', 'danger');
        });
        window.Echo.connector.pusher.connection.bind(
            'state_change',
            ({ current }) => {
                if (current !== 'connected' && realtimeAvailable) {
                    realtimeAvailable = false;
                    updateCallControls();
                    setStatus('Connection to the calling service was lost.', 'warning');
                }
            }
        );
    } else {
        setStatus(
            'Realtime calling is not configured. Configure Laravel Reverb and reload this page.',
            'warning'
        );
    }

    updateCallControls();
    window.addEventListener('pagehide', () => {
        if (peer) {
            peer.close();
        }
        localStream?.getTracks().forEach(track => track.stop());
    });
}
