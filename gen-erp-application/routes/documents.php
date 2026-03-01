<?php

use App\Http\Controllers;
use App\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    // Documents
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('/documents/{document}/thumbnail', [DocumentController::class, 'thumbnail'])->name('documents.thumbnail');
    Route::get('/documents/{document}/preview', [DocumentController::class, 'preview'])->name('documents.preview');
});
