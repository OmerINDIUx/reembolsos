<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReimbursementController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CostCenterController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('panel');
});

// Invitation Routes
Route::get('invitation/accept/{token}', [\App\Http\Controllers\Auth\InvitationController::class, 'accept'])->name('invitation.accept');
Route::post('invitation/complete', [\App\Http\Controllers\Auth\InvitationController::class, 'complete'])->name('invitation.complete');

Route::get('/panel', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('panel');

Route::middleware('auth')->group(function () {
    //
});

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('reimbursements/bulk/store', [ReimbursementController::class, 'bulkStore'])->name('reimbursements.bulk_store');
    Route::post('reimbursements/bulk/approve', [ReimbursementController::class, 'bulkApprove'])->name('reimbursements.bulk_approve');
    Route::post('reimbursements/bulk-audit', [ReimbursementController::class, 'bulkAuditAction'])->name('reimbursements.bulk_audit_action');
    Route::get('reimbursements/export', [ReimbursementController::class, 'export'])->name('reimbursements.export');
    Route::get('reimbursements/export/xml', [ReimbursementController::class, 'exportXml'])->name('reimbursements.export_xml');
    Route::get('reimbursements/audit', [ReimbursementController::class, 'audit'])->name('reimbursements.audit');
    Route::resource('reimbursements', ReimbursementController::class);
    Route::post('reimbursements/parse-xml', [ReimbursementController::class, 'parseCfdi'])->name('reimbursements.parse');
    Route::post('reimbursements/auto-save', [ReimbursementController::class, 'autoStore'])->name('reimbursements.auto_save');

    Route::middleware('permission:users.view')->group(function() {
        Route::resource('users', UserController::class);
        Route::post('users/{user}/resend-invitation', [UserController::class, 'resendInvitation'])->name('users.resend_invitation');
        
        // Profiles & Permissions Management
        Route::resource('profiles', \App\Http\Controllers\ProfileManagementController::class);

        // Substitutes
        Route::post('users/{user}/substitutes', [UserController::class, 'addSubstitute'])->name('users.substitutes.add');
        Route::post('users/{user}/substitutes/{substitute_id}/toggle', [UserController::class, 'toggleSubstitute'])->name('users.substitutes.toggle');
        Route::delete('users/{user}/substitutes/{substitute_id}', [UserController::class, 'removeSubstitute'])->name('users.substitutes.remove');
    });

    Route::middleware('permission:cost_centers.view')->group(function() {
        Route::resource('cost_centers', CostCenterController::class);
        Route::patch('cost_centers/{cost_center}/toggle-status', [CostCenterController::class, 'toggleStatus'])->name('cost_centers.toggle_status');
        Route::post('cost_centers/{cost_center}/renew-budget', [CostCenterController::class, 'renewBudget'])->name('cost_centers.renew_budget');
    });

    Route::middleware('permission:travel_events.view')->group(function() {
        Route::resource('travel_events', \App\Http\Controllers\TravelEventController::class);
        Route::post('travel_events/{travel_event}/close', [\App\Http\Controllers\TravelEventController::class, 'closeEvent'])->name('travel_events.close');
    });
    
    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    // Notification actions
    Route::post('/notifications/mark-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark_all');
    Route::get('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark_read');
    
    // Viewing and Downloading Files
    Route::get('reimbursements/{reimbursement}/view-file/{type}', [ReimbursementController::class, 'viewFile'])->name('reimbursements.view_file');
    Route::get('reimbursements/{reimbursement}/download-file/{type}', [ReimbursementController::class, 'downloadFile'])->name('reimbursements.download_file');
    Route::get('reimbursements/{reimbursement}/download-zip', [ReimbursementController::class, 'downloadZip'])->name('reimbursements.download_zip');
    Route::match(['get', 'post'], 'reimbursements/bulk/download-caratula', [ReimbursementController::class, 'downloadCaratula'])->name('reimbursements.download_caratula');
    Route::post('reimbursements/{reimbursement}/validate', [ReimbursementController::class, 'validateStoredFiles'])->name('reimbursements.validate');
    Route::post('reimbursements/{reimbursement}/validate-pdf-correction', [ReimbursementController::class, 'validatePdfCorrection'])->name('reimbursements.validate_pdf_correction');
});

require __DIR__.'/auth.php';
