<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

/**
 * Sends each user to their role's home after login (and after the 2FA challenge):
 * doctors to their daily worklist, everyone else to the dashboard.
 */
class LoginResponse implements LoginResponseContract, TwoFactorLoginResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        $user = $request->user();

        return redirect()->intended(route($user->homeRoute()));
    }
}
