<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$r = \App\Models\Reimbursement::find(8);
echo "Original Folio in DB: " . $r->folio . "\n";
echo "Type: " . $r->type . "\n";
