<?php
namespace InstagramStalker;

include __DIR__ . '/src/Stalker.php';

$data[0] = "yourUsername";
$data[1] = "yourPassword";
$data[2] = "targetUsername";
$data[3] = "true";

$stalker = new Stalker($data);

echo "Stalking from: {$data[0]}.\n";
echo "User to stalk: {$data[2]}\n";

$stalker->start();
