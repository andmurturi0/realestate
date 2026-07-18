<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

test('allAsArray returns the whole table as a key value array', function () {
    Setting::create(['key' => 'agency_name', 'value' => 'Agjencia Demo']);
    Setting::create(['key' => 'watermark_enabled', 'value' => false]);

    expect(Setting::allAsArray())->toBe([
        'agency_name' => 'Agjencia Demo',
        'watermark_enabled' => false,
    ]);
});

test('allAsArray is cached and does not see rows inserted behind its back', function () {
    Setting::create(['key' => 'agency_name', 'value' => 'Agjencia Demo']);

    Setting::allAsArray();

    expect(Cache::has(Setting::CACHE_KEY))->toBeTrue();

    // Bypasses model events, so the cache must NOT pick this up.
    DB::table('settings')->insert([
        'key' => 'phone',
        'value' => json_encode('+38344123456'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(Setting::allAsArray())->toBe(['agency_name' => 'Agjencia Demo']);
});

test('saving a setting invalidates the cache', function () {
    Setting::create(['key' => 'agency_name', 'value' => 'Agjencia Demo']);
    Setting::allAsArray();

    Setting::create(['key' => 'phone', 'value' => '+38344123456']);

    expect(Cache::has(Setting::CACHE_KEY))->toBeFalse()
        ->and(Setting::allAsArray())->toBe([
            'agency_name' => 'Agjencia Demo',
            'phone' => '+38344123456',
        ]);
});

test('updating an existing setting invalidates the cache', function () {
    $setting = Setting::create(['key' => 'agency_name', 'value' => 'Agjencia Demo']);
    Setting::allAsArray();

    $setting->update(['value' => 'Emri i Ri']);

    expect(Setting::allAsArray())->toBe(['agency_name' => 'Emri i Ri']);
});

test('deleting a setting invalidates the cache', function () {
    $setting = Setting::create(['key' => 'agency_name', 'value' => 'Agjencia Demo']);
    Setting::allAsArray();

    $setting->delete();

    expect(Cache::has(Setting::CACHE_KEY))->toBeFalse()
        ->and(Setting::allAsArray())->toBe([]);
});
