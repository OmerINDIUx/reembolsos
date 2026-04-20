<?php
$files = ['app/Models/Reimbursement.php', 'app/Http/Controllers/ReimbursementController.php'];
foreach($files as $f) {
    $c = file_get_contents($f);
    if (substr($c, 0, 3) === "\xEF\xBB\xBF") {
        file_put_contents($f, substr($c, 3));
        echo "Stripped BOM from $f\n";
    } else {
        echo "No BOM in $f\n";
    }
}
