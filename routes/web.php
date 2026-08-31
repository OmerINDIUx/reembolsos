<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReimbursementController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CostCenterController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DeviceAuditController;

Route::get('/', function () {
    return redirect()->route('panel');
});

// Invitation Routes
Route::get('invitation/accept/{token}', [\App\Http\Controllers\Auth\InvitationController::class, 'accept'])->name('invitation.accept');
Route::post('invitation/complete', [\App\Http\Controllers\Auth\InvitationController::class, 'complete'])->name('invitation.complete');

Route::get('/panel', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'permission:dashboard.view_own'])
    ->name('panel');

Route::middleware('auth')->group(function () {
    //
});

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/device-audit', [DeviceAuditController::class, 'index'])
        ->middleware('admin')
        ->name('admin.device-audit.index');
    Route::get('/admin/device-audit/users/export', [DeviceAuditController::class, 'exportUsers'])
        ->middleware('admin')
        ->name('admin.device-audit.users.export');
    Route::get('/admin/device-audit/approvers/export', [DeviceAuditController::class, 'exportApproverMatrix'])
        ->middleware('admin')
        ->name('admin.device-audit.approvers.export');
    Route::get('/admin/device-audit/reimbursements-dashboard', [DeviceAuditController::class, 'reimbursementsDashboard'])
        ->middleware('admin')
        ->name('admin.device-audit.reimbursements-dashboard');
    Route::get('/admin/device-audit/reimbursements-dashboard/details/{report}', [DeviceAuditController::class, 'reimbursementsDashboardDetails'])
        ->middleware('admin')
        ->name('admin.device-audit.reimbursements-dashboard.details');
    Route::post('/admin/device-audit/users/{user}/block', [DeviceAuditController::class, 'block'])
        ->middleware('admin')
        ->name('admin.device-audit.block');
    Route::delete('/admin/device-audit/users/{user}/block', [DeviceAuditController::class, 'unblock'])
        ->middleware('admin')
        ->name('admin.device-audit.unblock');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/notifications', [ProfileController::class, 'updateNotificationPreferences'])->name('profile.notifications.update');
    Route::post('/profile/personal-info/remind-later', [ProfileController::class, 'remindPersonalInfoLater'])->name('profile.personal_info.remind_later');

    Route::post('reimbursements/bulk/store', [ReimbursementController::class, 'bulkStore'])->name('reimbursements.bulk_store');
    Route::post('reimbursements/bulk/approve', [ReimbursementController::class, 'bulkApprove'])->name('reimbursements.bulk_approve');
    Route::post('reimbursements/bulk-audit', [ReimbursementController::class, 'bulkAuditAction'])->name('reimbursements.bulk_audit_action');
    Route::post('reimbursements/bulk-delete', [ReimbursementController::class, 'bulkDestroy'])->name('reimbursements.bulk_destroy');
    Route::get('reimbursements/export', [ReimbursementController::class, 'export'])->name('reimbursements.export');
    Route::get('reimbursements/payment-file', [ReimbursementController::class, 'exportPaymentFile'])->name('reimbursements.payment_file');
    Route::get('reimbursements/payment-policy', [ReimbursementController::class, 'exportPaymentPolicy'])->name('reimbursements.payment_policy');
    Route::post('reimbursements/payment/return-to-previous-step', [ReimbursementController::class, 'returnPaymentToPreviousStep'])->name('reimbursements.payment_return');
    Route::get('reimbursements/export/xml', [ReimbursementController::class, 'exportXml'])->name('reimbursements.export_xml');
    Route::get('reimbursements/audit', [ReimbursementController::class, 'audit'])->name('reimbursements.audit');
    Route::patch('reimbursements/{reimbursement}/admin-flow', [ReimbursementController::class, 'adminFlowUpdate'])->name('reimbursements.admin_flow_update');
    Route::post('reimbursements/{reimbursement}/request-clarification', [ReimbursementController::class, 'requestClarification'])
        ->middleware('throttle:5,1')
        ->name('reimbursements.request_clarification');
    Route::resource('reimbursements', ReimbursementController::class);
    Route::post('reimbursements/parse-xml', [ReimbursementController::class, 'parseCfdi'])->name('reimbursements.parse');
    Route::post('reimbursements/auto-save', [ReimbursementController::class, 'autoStore'])->name('reimbursements.auto_save');

    Route::middleware('permission:users.view')->group(function() {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserController::class, 'create'])->middleware('permission:users.create')->name('users.create');
        Route::post('users', [UserController::class, 'store'])->middleware('permission:users.create')->name('users.store');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->middleware('permission:users.edit')->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->middleware('permission:users.edit')->name('users.update');
        Route::patch('users/{user}', [UserController::class, 'update'])->middleware('permission:users.edit');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete')->name('users.destroy');
        Route::get('users/{user}/deactivation', [UserController::class, 'deactivation'])->middleware('permission:users.delete')->name('users.deactivation');
        Route::post('users/{user}/resend-invitation', [UserController::class, 'resendInvitation'])->middleware('permission:users.edit')->name('users.resend_invitation');
        
        // Profiles & Permissions Management
        Route::resource('profiles', \App\Http\Controllers\ProfileManagementController::class);

        // Substitutes
        Route::post('users/{user}/substitutes', [UserController::class, 'addSubstitute'])->middleware('permission:users.edit')->name('users.substitutes.add');
        Route::post('users/{user}/substitutes/{substitute_id}/toggle', [UserController::class, 'toggleSubstitute'])->middleware('permission:users.edit')->name('users.substitutes.toggle');
        Route::delete('users/{user}/substitutes/{substitute_id}', [UserController::class, 'removeSubstitute'])->middleware('permission:users.edit')->name('users.substitutes.remove');
    });

    Route::middleware('permission:cost_centers.view')->group(function() {
        Route::resource('companies', CompanyController::class)->except(['show']);
        Route::get('cost_centers', [CostCenterController::class, 'index'])->name('cost_centers.index');
        Route::middleware('permission:cost_centers.create')->group(function () {
            Route::get('cost_centers/create', [CostCenterController::class, 'create'])->name('cost_centers.create');
            Route::post('cost_centers', [CostCenterController::class, 'store'])->name('cost_centers.store');
        });
        Route::middleware('permission:cost_centers.edit')->group(function () {
            Route::get('cost_centers/{cost_center}/edit', [CostCenterController::class, 'edit'])->name('cost_centers.edit');
            Route::match(['put', 'patch'], 'cost_centers/{cost_center}', [CostCenterController::class, 'update'])->name('cost_centers.update');
            Route::post('cost_centers/{cost_center}/renew-budget', [CostCenterController::class, 'renewBudget'])->name('cost_centers.renew_budget');
        });
        Route::middleware('permission:cost_centers.delete')->group(function () {
            Route::get('cost_centers/{cost_center}/deactivation', [CostCenterController::class, 'deactivation'])->name('cost_centers.deactivation');
            Route::patch('cost_centers/{cost_center}/toggle-status', [CostCenterController::class, 'toggleStatus'])->name('cost_centers.toggle_status');
        });
        Route::get('cost_centers/{cost_center}/category-matrix', [CostCenterController::class, 'categoryMatrix'])->name('cost_centers.category_matrix');
        Route::get('cost_centers/{cost_center}/activity', [CostCenterController::class, 'activity'])->name('cost_centers.activity');
        Route::get('cost_centers/{cost_center}/fixed-fund-history', [CostCenterController::class, 'fixedFundHistory'])->name('cost_centers.fixed_fund_history');
        Route::get('cost_centers/{cost_center}', [CostCenterController::class, 'show'])->name('cost_centers.show');
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
