<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$r = \App\Models\Reimbursement::find(268);
echo "Type: " . ($r ? $r->type : 'Not Found') . "\n";
echo "UUID: " . ($r ? $r->uuid : 'Not Found') . "\n";
echo "Folio: " . ($r ? $r->folio : 'Not Found') . "\n";
