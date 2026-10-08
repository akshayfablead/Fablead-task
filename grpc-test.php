<?php

$channel = new Grpc\Channel(
    'firestore.googleapis.com:443',
    [
        'credentials' => Grpc\ChannelCredentials::createSsl(),
    ]
);

echo "CHANNEL_CREATED" . PHP_EOL;

echo $channel->getConnectivityState(true) . PHP_EOL;

sleep(3);

echo $channel->getConnectivityState(true) . PHP_EOL;
