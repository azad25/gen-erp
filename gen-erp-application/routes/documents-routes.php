<?php

use Illuminate\Support\Facades\Route;

// Documents routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('documents')->group(function () {
        Route::get('/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
        Route::get('/{document}/thumbnail', [DocumentController::class, 'thumbnail'])->name('documents.thumbnail');
        Route::get('/{document}/preview', [DocumentController::class, 'preview'])->name('documents.preview');
    });
});
