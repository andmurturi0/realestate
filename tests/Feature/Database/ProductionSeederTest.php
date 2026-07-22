<?php

use App\Enums\UserRole;
use App\Models\Feature;
use App\Models\Location;
use App\Models\Property;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\ProductionSeeder;
use Illuminate\Support\Facades\Hash;

test('production seeder throws when admin credentials are missing', function () {
    config(['services.admin.email' => null, 'services.admin.password' => null]);

    expect(fn () => (new ProductionSeeder)->run())->toThrow(RuntimeException::class);
});

test('production seeder creates exactly one admin from config, reference data, and empty settings', function () {
    config([
        'services.admin.email' => 'owner@agency.test',
        'services.admin.password' => 'super-secret-password',
    ]);

    (new ProductionSeeder)->run();

    $admin = User::sole();
    expect($admin->email)->toBe('owner@agency.test')
        ->and($admin->role)->toBe(UserRole::Admin)
        ->and($admin->is_active)->toBeTrue()
        ->and(Hash::check('super-secret-password', $admin->password))->toBeTrue();

    expect(Location::count())->toBeGreaterThan(0)
        ->and(Feature::count())->toBeGreaterThan(0)
        ->and(Property::count())->toBe(0);

    expect(Setting::where('key', 'agency_name')->value('value'))->toBe('');
    expect(Setting::where('key', 'watermark_enabled')->value('value'))->toBeFalse();
});
