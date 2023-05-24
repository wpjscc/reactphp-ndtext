<?php

require './vendor/autoload.php';

$socket = new React\Socket\SocketServer('0.0.0.0:8080');

$socket->on('connection', function (React\Socket\ConnectionInterface $connection) {
    $ndtext = new \Wpjscc\NDText\Decoder($connection);
    $netext = new \Wpjscc\NDText\Encoder($connection);

    $ndtext->on('data', function($msg) use ($netext) {
        echo $msg."\n";
        $netext->write($msg);
    });

});