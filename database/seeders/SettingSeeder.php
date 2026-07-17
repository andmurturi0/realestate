<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Placeholder values only — real branding is configured by the admin in
     * /dashboard/settings. Nothing here may ever be referenced from code.
     */
    public function run(): void
    {
        $settings = [
            'agency_name' => 'Agjencia Demo',
            'logo_path' => null,
            'logo_dark_path' => null,
            'favicon_path' => null,
            'primary_color' => '#1d4ed8',
            'phone' => '+38344123456',
            'whatsapp' => '+38344123456',
            'email' => 'info@agjencia-demo.test',
            'address' => 'Rr. Nëna Terezë 1, Prishtinë',
            'office_lat' => 42.6629,
            'office_lng' => 21.1655,
            'facebook' => 'https://facebook.com/agjenciademo',
            'instagram' => 'https://instagram.com/agjenciademo',
            'tiktok' => null,
            'linkedin' => null,
            'youtube' => null,
            'about_content' => [
                'sq' => 'Agjencia Demo është agjenci e patundshmërive me seli në Prishtinë. Ky tekst zëvendësohet nga administratori te Cilësimet.',
                'en' => 'Agjencia Demo is a real estate agency based in Pristina. This text is replaced by the administrator in Settings.',
                'de' => 'Agjencia Demo ist eine Immobilienagentur mit Sitz in Pristina. Dieser Text wird vom Administrator in den Einstellungen ersetzt.',
            ],
            'terms_content' => [
                'sq' => 'Kushtet e përdorimit — plotësohen nga administratori.',
                'en' => 'Terms of use — to be completed by the administrator.',
                'de' => 'Nutzungsbedingungen — vom Administrator auszufüllen.',
            ],
            'privacy_content' => [
                'sq' => 'Politika e privatësisë — plotësohet nga administratori.',
                'en' => 'Privacy policy — to be completed by the administrator.',
                'de' => 'Datenschutzerklärung — vom Administrator auszufüllen.',
            ],
            'faq_content' => [
                'sq' => 'Pyetjet e shpeshta — plotësohen nga administratori.',
                'en' => 'Frequently asked questions — to be completed by the administrator.',
                'de' => 'Häufig gestellte Fragen — vom Administrator auszufüllen.',
            ],
            'app_store_url' => null,
            'play_store_url' => null,
            'watermark_enabled' => false,
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
