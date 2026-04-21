<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

echo "Testing Mail Configuration...\n";
echo "Mailer: " . Config::get('mail.default') . "\n";
echo "Host: " . Config::get('mail.mailers.smtp.host') . "\n";
echo "Port: " . Config::get('mail.mailers.smtp.port') . "\n";
echo "Username: " . Config::get('mail.mailers.smtp.username') . "\n";
echo "Encryption: " . Config::get('mail.mailers.smtp.encryption') . "\n";

try {
    Mail::raw('This is a test email to verify SMTP configuration.', function ($message) {
        $message->to(Config::get('mail.from.address'))
                ->subject('Test Email - Reimbursement System');
    });
    echo "Email sent successfully to " . Config::get('mail.from.address') . "\n";
} catch (\Exception $e) {
    echo "Failed to send email. Error: " . $e->getMessage() . "\n";
}
