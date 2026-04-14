<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::where('email', 'usuariop@example.com')->first();
if (!$user) {
    echo "User not found\n";
    exit;
}

$activeTripsAsParticipant = App\Models\TravelEvent::where('status', 'active')
    ->whereHas('participants', function($q) use ($user) {
        $q->where('users.id', $user->id);
    })->get();

$activeTripsAsOwner = App\Models\TravelEvent::where('status', 'active')
    ->where('user_id', $user->id)
    ->get();

echo "User: " . $user->email . " (ID: " . $user->id . ")\n";
echo "Active Trips as Participant: " . $activeTripsAsParticipant->count() . "\n";
foreach($activeTripsAsParticipant as $t) {
    echo " - ID: " . $t->id . ", Name: " . $t->name . "\n";
}

echo "Active Trips as Owner: " . $activeTripsAsOwner->count() . "\n";
foreach($activeTripsAsOwner as $t) {
    echo " - ID: " . $t->id . ", Name: " . $t->name . "\n";
}
