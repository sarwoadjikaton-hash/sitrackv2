<?php

use App\Http\Controllers\AlurStatusController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataSuratController;
use App\Http\Controllers\DispositionLetterController;
use App\Http\Controllers\LetterAvailabilityController;
use App\Http\Controllers\LetterCategoryController;
use App\Http\Controllers\LetterNumberTypeController;
use App\Http\Controllers\LetterRelationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\PublicTrackingController;
use App\Http\Controllers\RekapMasterController;
use App\Http\Controllers\ScanQrController;
use App\Http\Controllers\SignatureLetterController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LetterImportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::redirect('/', '/tracking', 301);

Route::get('/tracking', [PublicTrackingController::class, 'index'])->name('tracking.index');
Route::get('/tracking/{code}', [PublicTrackingController::class, 'showByCode'])->name('tracking.show');
Route::get('/ajukan-surat', [PublicTrackingController::class, 'create'])->name('tracking.create');
Route::post('/ajukan-surat', [PublicTrackingController::class, 'store'])->name('tracking.store');
Route::get('/surat-berhasil/{code}', [PublicTrackingController::class, 'success'])->name('tracking.success');
Route::get('/data-surat/slots', [DataSuratController::class, 'getSlots'])->name('data-surat.slots.public');

// Cetak Dokumen Publik (Lembar Pendamping & Disposisi)
Route::get('/cetak/pendamping/{id}', [PrintController::class, 'pendamping'])->name('print.pendamping');
Route::get('/print/pendamping/{id}', [PrintController::class, 'pendamping'])->name('print.pendamping.alias');
Route::post('/cetak/pendamping/{id}/signature', [PrintController::class, 'saveSignature'])->name('print.pendamping.signature');
Route::post('/print/pendamping/{id}/signature', [PrintController::class, 'saveSignature'])->name('print.pendamping.signature.alias');
Route::get('/cetak/disposisi/{id}', [PrintController::class, 'disposisi'])->name('print.disposisi');
Route::get('/print/disposisi/{id}', [PrintController::class, 'disposisi'])->name('print.disposisi.alias');

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Protected Staff Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::put('/password', [AuthController::class, 'updatePassword'])->name('password.update');

    // In-App Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('permission:view dashboard')->name('dashboard');

    Route::post('/data-surat/import', [LetterImportController::class, 'importDataSurat'])
        ->name('letters.import.store');

    Route::get('/template-excel', [LetterImportController::class, 'downloadDataSuratTemplate'])
        ->middleware('permission:manage letter data')
        ->name('template.dataSurat');


    // Penomoran: Ketersediaan Nomor Surat
    Route::get('/ketersediaan-nomor', [LetterAvailabilityController::class, 'index'])->middleware('permission:view number availability')->name('ketersediaan-nomor.index');
    Route::post('/ketersediaan-nomor', [LetterAvailabilityController::class, 'store'])->middleware('permission:manage number availability')->name('ketersediaan-nomor.store');
    Route::post('/ketersediaan-nomor/sync-spreadsheet', [LetterAvailabilityController::class, 'syncSpreadsheet'])->middleware('permission:manage number availability')->name('ketersediaan-nomor.sync-spreadsheet');
    Route::delete('/ketersediaan-nomor/{id}', [LetterAvailabilityController::class, 'destroy'])->middleware('permission:manage number availability')->name('ketersediaan-nomor.destroy');
    Route::delete('/ketersediaan-nomor/number/bulk-destroy', [LetterAvailabilityController::class, 'destroyNumbersBulk'])->middleware('permission:manage number availability')->name('ketersediaan-nomor.number.bulk-destroy');
    Route::put('/ketersediaan-nomor/number/bulk-status', [LetterAvailabilityController::class, 'updateNumberStatusBulk'])->middleware('permission:manage number availability')->name('ketersediaan-nomor.number.bulk-status');
    Route::delete('/ketersediaan-nomor/number/{id}', [LetterAvailabilityController::class, 'destroyNumber'])->middleware('permission:manage number availability')->name('ketersediaan-nomor.number.destroy');
    Route::put('/ketersediaan-nomor/number/{id}/status', [LetterAvailabilityController::class, 'updateNumberStatus'])->middleware('permission:manage number availability')->name('ketersediaan-nomor.number.status');

    // Penomoran: Data Surat
    Route::get('/data-surat', [DataSuratController::class, 'index'])->middleware('permission:view letter data')->name('data-surat.index');
    Route::post('/data-surat', [DataSuratController::class, 'store'])->middleware('permission:manage letter data')->name('data-surat.store');
    Route::put('/data-surat/{id}', [DataSuratController::class, 'update'])->middleware('permission:manage letter data')->name('data-surat.update');
    Route::get('/data-surat/export', [DataSuratController::class, 'export'])
        ->name('data-surat.export');

    // Lajur Pertama: Tindak Lanjut / Penandatanganan
    Route::get('/tindak-lanjut', [SignatureLetterController::class, 'index'])->middleware('permission:view signature lane')->name('tindak-lanjut.index');
    Route::get('/tindak-lanjut/create', [SignatureLetterController::class, 'create'])->middleware('permission:manage signature lane')->name('tindak-lanjut.create');
    Route::post('/tindak-lanjut', [SignatureLetterController::class, 'store'])->middleware('permission:manage signature lane')->name('tindak-lanjut.store');
    Route::get('/tindak-lanjut/{id}/edit', [SignatureLetterController::class, 'edit'])->middleware('permission:manage signature lane')->name('tindak-lanjut.edit');
    Route::put('/tindak-lanjut/{id}', [SignatureLetterController::class, 'update'])->middleware('permission:manage signature lane')->name('tindak-lanjut.update');
    Route::post('/tindak-lanjut/{id}/status', [SignatureLetterController::class, 'updateStatus'])->middleware('permission:manage signature lane')->name('tindak-lanjut.update-status');
    Route::delete('/tindak-lanjut/{id}', [SignatureLetterController::class, 'destroy'])->middleware('permission:manage signature lane')->name('tindak-lanjut.destroy');

    // Lajur Kedua: Disposisi
    Route::get('/disposisi', [DispositionLetterController::class, 'index'])->middleware('permission:view disposition lane')->name('disposisi.index');
    Route::get('/disposisi/create', [DispositionLetterController::class, 'create'])->middleware('permission:manage disposition lane')->name('disposisi.create');
    Route::post('/disposisi', [DispositionLetterController::class, 'store'])->middleware('permission:manage disposition lane')->name('disposisi.store');
    Route::get('/disposisi/{id}', [DispositionLetterController::class, 'show'])->middleware('permission:view disposition lane')->name('disposisi.show');
    Route::post('/disposisi/{id}/add-instruction', [DispositionLetterController::class, 'addDisposition'])->middleware('permission:give disposition instruction')->name('disposisi.add-instruction');
    Route::put('/disposisi/{id}/item/{dispositionId}', [DispositionLetterController::class, 'updateDispositionItem'])->middleware('permission:update disposition progress')->name('disposisi.update-item');
    Route::post('/disposisi/{id}/status', [DispositionLetterController::class, 'updateLetterStatus'])->middleware('permission:update disposition progress')->name('disposisi.update-status');

    // Relasi Surat Lintas Lajur
    Route::post('/letter-relations', [LetterRelationController::class, 'store'])->middleware('permission:manage letter relations')->name('letter-relations.store');
    Route::delete('/letter-relations/{id}', [LetterRelationController::class, 'destroy'])->middleware('permission:manage letter relations')->name('letter-relations.destroy');
    Route::get('/letter-relations/search', [LetterRelationController::class, 'search'])->middleware('permission:manage letter relations')->name('letter-relations.search');

    // Scan QR Status
    Route::get('/scan-status', [ScanQrController::class, 'index'])->middleware('permission:update status qr')->name('scan-status.index');
    Route::post('/scan-status', [ScanQrController::class, 'update'])->middleware('permission:update status qr')->name('scan-status.update');



    // Panduan Alur Status
    Route::get('/alur-status', [AlurStatusController::class, 'index'])->middleware('permission:view dashboard')->name('alur-status.index');

    // Master Data Routes
    Route::prefix('master')->name('master.')->group(function () {
        Route::get('/units', [UnitController::class, 'index'])->middleware('permission:manage units')->name('units.index');
        Route::post('/units', [UnitController::class, 'store'])->middleware('permission:manage units')->name('units.store');
        Route::delete('/units/{id}', [UnitController::class, 'destroy'])->middleware('permission:manage units')->name('units.destroy');

        Route::get('/categories', [LetterCategoryController::class, 'index'])->middleware('permission:manage categories')->name('categories.index');
        Route::post('/categories', [LetterCategoryController::class, 'store'])->middleware('permission:manage categories')->name('categories.store');
        Route::put('/categories/{category}', [LetterCategoryController::class, 'update'])->middleware('permission:manage categories')->name('categories.update');
        Route::delete('/categories/{id}', [LetterCategoryController::class, 'destroy'])->middleware('permission:manage categories')->name('categories.destroy');

        Route::get('/number-types', [LetterNumberTypeController::class, 'index'])->middleware('permission:manage number types')->name('number-types.index');
        Route::post('/number-types', [LetterNumberTypeController::class, 'store'])->middleware('permission:manage number types')->name('number-types.store');
        Route::delete('/number-types/{id}', [LetterNumberTypeController::class, 'destroy'])->middleware('permission:manage number types')->name('number-types.destroy');

        Route::get('/users', [UserController::class, 'index'])->middleware('permission:manage users')->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->middleware('permission:manage users')->name('users.store');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->middleware('permission:manage users')->name('users.destroy');

        Route::get('/rekap', [RekapMasterController::class, 'index'])->middleware('permission:export master recap')->name('rekap.index');
        Route::get('/rekap/export', [RekapMasterController::class, 'exportCsv'])->middleware('permission:export master recap')->name('rekap.export');
    });
});

// Taruh di baris paling akhir routes/web.php
Route::fallback(function () {
    abort(404);
});
