<?php

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/api/meta/webhook?hub.mode=subscribe&hub.verify_token=demo_verify_token&hub.challenge=challenge_123', 'GET');

var_dump($request->query->all());
var_dump($request->input('hub.mode'));
var_dump($request->input('hub.verify_token'));
var_dump($request->input('hub.challenge'));

$response = $app->handle($request);

echo "STATUS=" . $response->getStatusCode() . PHP_EOL;
echo "BODY=" . $response->getContent() . PHP_EOL;
