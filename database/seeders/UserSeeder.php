<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admini',
                'email' => 'admin@agjencia.test',
                'role' => UserRole::Admin,
                'phone' => '+38344100100',
                'whatsapp' => '+38344100100',
                'bio' => null,
            ],
            [
                'name' => 'Arben Krasniqi',
                'email' => 'arben@agjencia.test',
                'role' => UserRole::Agent,
                'phone' => '+38344200201',
                'whatsapp' => '+38344200201',
                'bio' => [
                    'sq' => 'Agjent i patundshmërive me 8 vjet përvojë në tregun e Prishtinës.',
                    'en' => 'Real estate agent with 8 years of experience in the Pristina market.',
                    'de' => 'Immobilienmakler mit 8 Jahren Erfahrung auf dem Markt von Pristina.',
                ],
            ],
            [
                'name' => 'Blerta Gashi',
                'email' => 'blerta@agjencia.test',
                'role' => UserRole::Agent,
                'phone' => '+38345300302',
                'whatsapp' => '+38345300302',
                'bio' => [
                    'sq' => 'E specializuar në banesa dhe prona ekskluzive.',
                    'en' => 'Specialised in apartments and exclusive properties.',
                    'de' => 'Spezialisiert auf Wohnungen und exklusive Immobilien.',
                ],
            ],
            [
                'name' => 'Driton Berisha',
                'email' => 'driton@agjencia.test',
                'role' => UserRole::Agent,
                'phone' => '+38349400403',
                'whatsapp' => '+38349400403',
                'bio' => [
                    'sq' => 'Ekspert për toka dhe objekte afariste.',
                    'en' => 'Expert in land and commercial buildings.',
                    'de' => 'Experte für Grundstücke und Gewerbeobjekte.',
                ],
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    ...$user,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );
        }
    }
}
