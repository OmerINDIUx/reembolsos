<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReimbursementController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CostCenterController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('reimbursements/bulk/store', [ReimbursementController::class, 'bulkStore'])->name('reimbursements.bulk_store');
    Route::resource('reimbursements', ReimbursementController::class);
    Route::post('reimbursements/parse-xml', [ReimbursementController::class, 'parseCfdi'])->name('reimbursements.parse');
    Route::resource('users', UserController::class);
    Route::resource('cost_centers', CostCenterController::class);
    
    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark_all');
    Route::get('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark_read');
    
    // Viewing and Downloading Files
    Route::get('reimbursements/{reimbursement}/view-file/{type}', [ReimbursementController::class, 'viewFile'])->name('reimbursements.view_file');
    Route::get('reimbursements/{reimbursement}/download-zip', [ReimbursementController::class, 'downloadZip'])->name('reimbursements.download_zip');
    Route::post('reimbursements/{reimbursement}/validate', [ReimbursementController::class, 'validateStoredFiles'])->name('reimbursements.validate');
    Route::post('reimbursements/{reimbursement}/validate-pdf-correction', [ReimbursementController::class, 'validatePdfCorrection'])->name('reimbursements.validate_pdf_correction');
});

require __DIR__.'/auth.php';
