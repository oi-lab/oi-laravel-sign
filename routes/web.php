<?php

use Illuminate\Support\Facades\Route;
use OiLab\OiLaravelSign\Http\Controllers\SignatureController;

Route::get('{token}', [SignatureController::class, 'show'])->name('show');
Route::get('{token}/document', [SignatureController::class, 'document'])->name('document');
Route::get('{token}/attachments/{index}', [SignatureController::class, 'attachment'])->whereNumber('index')->name('attachment');
Route::post('{token}/otp', [SignatureController::class, 'otp'])->name('otp');
Route::post('{token}/otp/resend', [SignatureController::class, 'resend'])->name('resend');
Route::post('{token}/sign', [SignatureController::class, 'sign'])->name('sign');
Route::post('{token}/decline', [SignatureController::class, 'decline'])->name('decline');
Route::get('{token}/download', [SignatureController::class, 'download'])->name('download');
