<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Fortify;

class TwoFactorAuthenticationController extends Controller
{
    /**
     * Show the user's two-factor authentication settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $pendingConfirmation = $user->two_factor_secret && ! $user->two_factor_confirmed_at;

        return Inertia::render('settings/TwoFactorAuthentication', [
            'status' => $request->session()->get('status'),
            'twoFactorEnabled' => $user->hasEnabledTwoFactorAuthentication(),
            'qrCodeSvg' => $pendingConfirmation ? $user->twoFactorQrCodeSvg() : null,
            'manualSetupKey' => $pendingConfirmation ? Fortify::currentEncrypter()->decrypt($user->two_factor_secret) : null,
            'recoveryCodes' => $user->two_factor_secret ? $user->recoveryCodes() : null,
        ]);
    }
}
