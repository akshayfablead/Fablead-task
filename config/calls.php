<?php

return [
    'ice_servers' => array_values(array_filter([
        [
            'urls' => 'stun:stun.l.google.com:19302',
        ],
        env('CALL_TURN_URL') ? [
            'urls' => env('CALL_TURN_URL'),
            'username' => env('CALL_TURN_USERNAME'),
            'credential' => env('CALL_TURN_CREDENTIAL'),
        ] : null,
    ])),
];
