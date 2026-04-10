<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'logout',
        ]);
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        $schedule->call(function () {
            $drafts = \App\Models\Reimbursement::where('status', 'borrador')->get();
            foreach ($drafts as $draft) {
                if ($draft->xml_path) \Illuminate\Support\Facades\Storage::delete($draft->xml_path);
                if ($draft->pdf_path) \Illuminate\Support\Facades\Storage::delete($draft->pdf_path);
                if ($draft->ticket_path) \Illuminate\Support\Facades\Storage::delete($draft->ticket_path);
                $draft->delete();
            }
        })->weeklyOn(6, '00:00'); // Sábados a la medianoche
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
