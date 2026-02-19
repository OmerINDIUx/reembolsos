<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReimbursementController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CostCenterController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('reimbursements', ReimbursementController::class);
    Route::post('reimbursements/parse-xml', [ReimbursementController::class, 'parseCfdi'])->name('reimbursements.parse');
    Route::resource('users', UserController::class);
    Route::resource('cost_centers', CostCenterController::class);
    
    // Viewing and Downloading Files
    Route::get('reimbursements/{reimbursement}/view-file/{type}', [ReimbursementController::class, 'viewFile'])->name('reimbursements.view_file');
    Route::get('reimbursements/{reimbursement}/download-zip', [ReimbursementController::class, 'downloadZip'])->name('reimbursements.download_zip');
    Route::post('reimbursements/{reimbursement}/validate', [ReimbursementController::class, 'validateStoredFiles'])->name('reimbursements.validate');
});

require __DIR__.'/auth.php';
