<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Fortify;
use PragmaRX\Google2FA\Google2FA;

// Security follow-up: TOTP two-factor auth, on top of Fortify's actions/TOTP
// provider/recovery codes. Enabling/disabling/regenerating recovery codes all
// sit behind the `password.confirm` middleware, mirrored here by seeding
// `auth.password_confirmed_at` instead of walking the confirm-password screen.

function currentOtpFor(User $user): string
{
    return (new Google2FA)->getCurrentOtp(
        Fortify::currentEncrypter()->decrypt($user->fresh()->two_factor_secret)
    );
}

test('a user can enable two-factor authentication and confirm it with a valid code', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('two-factor.enable'))
        ->assertRedirect();

    $user->refresh();
    expect($user->two_factor_secret)->not->toBeNull()
        ->and($user->two_factor_recovery_codes)->not->toBeNull()
        ->and($user->two_factor_confirmed_at)->toBeNull()
        ->and($user->hasEnabledTwoFactorAuthentication())->toBeFalse();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('two-factor.confirm'), ['code' => currentOtpFor($user)])
        ->assertRedirect();

    expect($user->fresh()->hasEnabledTwoFactorAuthentication())->toBeTrue();
});

test('confirming two-factor authentication with an invalid code fails', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('two-factor.enable'));

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('two-factor.confirm'), ['code' => '000000'])
        ->assertSessionHasErrors();

    expect($user->fresh()->hasEnabledTwoFactorAuthentication())->toBeFalse();
});

test('a user can disable two-factor authentication', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('two-factor.enable'));

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('two-factor.confirm'), ['code' => currentOtpFor($user)]);

    expect($user->fresh()->hasEnabledTwoFactorAuthentication())->toBeTrue();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('two-factor.disable'))
        ->assertRedirect();

    $user->refresh();
    expect($user->two_factor_secret)->toBeNull()
        ->and($user->hasEnabledTwoFactorAuthentication())->toBeFalse();
});

test('enabling and confirming two-factor authentication requires a recently confirmed password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('two-factor.enable'))
        ->assertRedirect(route('password.confirm'));

    expect($user->fresh()->two_factor_secret)->toBeNull();
});

/**
 * Enables and confirms two-factor authentication for the given user directly
 * (bypassing HTTP), returning the decrypted recovery codes.
 *
 * @return list<string>
 */
function enableTwoFactorFor(User $user): array
{
    app(EnableTwoFactorAuthentication::class)($user);
    app(ConfirmTwoFactorAuthentication::class)($user, currentOtpFor($user));

    return $user->fresh()->recoveryCodes();
}

test('logging in with two-factor authentication enabled requires a code before the session is authenticated', function () {
    $user = User::factory()->create();
    enableTwoFactorFor($user);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $this->assertGuest();
    expect(session('login.id'))->toBe($user->id);
});

test('a valid two-factor code completes the login', function () {
    $user = User::factory()->create();
    enableTwoFactorFor($user);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    // Fortify's TOTP provider caches each verified code+timestamp pair to
    // reject replays. The confirm step above already spent this 30-second
    // window's code, so without clearing that cache entry, resubmitting the
    // same still-current code here would be (correctly) rejected as a replay.
    Cache::flush();

    $response = $this->post(route('two-factor.login.store'), [
        'code' => currentOtpFor($user),
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('an invalid two-factor code does not complete the login', function () {
    $user = User::factory()->create();
    enableTwoFactorFor($user);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response = $this->post(route('two-factor.login.store'), [
        'code' => '000000',
    ]);

    $response->assertSessionHasErrors();
    $this->assertGuest();
});

test('a valid recovery code completes the login and cannot be reused', function () {
    $user = User::factory()->create();
    $recoveryCodes = enableTwoFactorFor($user);
    $code = $recoveryCodes[0];

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response = $this->post(route('two-factor.login.store'), [
        'recovery_code' => $code,
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false));
    expect($user->fresh()->recoveryCodes())->not->toContain($code);

    Auth::guard('web')->logout();
    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $reuse = $this->post(route('two-factor.login.store'), [
        'recovery_code' => $code,
    ]);

    $reuse->assertSessionHasErrors();
    $this->assertGuest();
});

test('users without two-factor authentication enabled log in directly', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false));
});
