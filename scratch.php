<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = App\Models\User::where('email', 'like', '%prueba_luise%')->first();
$result = $user->canPerform('travel_events.view');

file_put_contents('test_output.txt', $result ? 'true' : 'false');
