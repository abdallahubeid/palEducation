<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| مسارات المصادقة
|--------------------------------------------------------------------------
| مسجَّل في bootstrap/app.php عبر then() ضمن مجموعة web — مثل web.php
| تماماً. الهوية بالبريد الإلكتروني + دخول جوجل. لا هاتف ولا SMS.
|
| شاشة دخول واحدة للأدوار الثلاثة (توصية م-7)، والتوجيه بعد الدخول
| حسب userable_type عبر User::homeRoute().
*/

Route::middleware('guest')->group(function () {

    // ── التسجيل ─────────────────────────────────────────────────
    Route::get('/register', [RegisteredUserController::class, 'create'])
        ->name('auth.register');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('auth.register.store');

    // ── تسجيل الدخول ────────────────────────────────────────────
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->name('auth.login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('auth.login.store');

    // ── جوجل ────────────────────────────────────────────────────
    Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])
        ->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])
        ->name('auth.google.callback');

    // اختيار الفرع — الخطوة الأخيرة لمن سجّل عبر جوجل (الشاشة 16).
    Route::get('/auth/google/branch', [GoogleController::class, 'showBranchSelection'])
        ->name('auth.google.branch');
    Route::post('/auth/google/branch', [GoogleController::class, 'completeRegistration'])
        ->name('auth.google.branch.store');
});

/*
| ⏳ استعادة كلمة المرور — الشاشة مبنية بصرياً (auth.forgot) لكن التدفّق
| الخلفي لم يُبنَ بعد. خارج نطاق هذه الدفعة عمداً.
*/
Route::view('/forgot-password', 'pages.auth.forgot-password')
    ->middleware('guest')
    ->name('auth.forgot');

Route::middleware('auth')->group(function () {

    // ── توثيق البريد ────────────────────────────────────────────
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])
        ->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // ── الخروج ──────────────────────────────────────────────────
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('auth.logout');
});
