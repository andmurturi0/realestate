<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class ProductionSeeder extends Seeder
{
    /**
     * Seeds only what a freshly deployed installation needs: reference data
     * (locations, features), empty settings defaults, and one admin account.
     * No test properties, agents, testimonials, or team members — the agency
     * adds all of that itself once it logs in.
     */
    public function run(): void
    {
        $this->call([
            LocationSeeder::class,
            FeatureSeeder::class,
        ]);

        $this->seedSettings();
        $this->seedAdmin();
    }

    protected function seedSettings(): void
    {
        $emptyContent = ['sq' => '', 'en' => '', 'de' => ''];

        $settings = [
            'agency_name' => '',
            'logo_path' => null,
            'logo_dark_path' => null,
            'favicon_path' => null,
            'primary_color' => '#1d4ed8',
            'phone' => null,
            'whatsapp' => null,
            'email' => null,
            'address' => null,
            'office_lat' => null,
            'office_lng' => null,
            'facebook' => null,
            'instagram' => null,
            'tiktok' => null,
            'linkedin' => null,
            'youtube' => null,
            'about_content' => $emptyContent,
            'terms_content' => $emptyContent,
            'privacy_content' => $emptyContent,
            'faq_content' => $emptyContent,
            'app_store_url' => null,
            'play_store_url' => null,
            'watermark_enabled' => false,
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    protected function seedAdmin(): void
    {
        $email = config('services.admin.email');
        $password = config('services.admin.password');

        if (empty($email) || empty($password)) {
            throw new RuntimeException(
                'ProductionSeeder requires ADMIN_EMAIL and ADMIN_PASSWORD environment variables to be set.'
            );
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Admin',
                'password' => Hash::make($password),
                'role' => UserRole::Admin,
                'phone' => null,
                'whatsapp' => null,
                'bio' => null,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
    }
}
