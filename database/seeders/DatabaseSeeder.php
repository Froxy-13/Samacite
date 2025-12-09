<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create roles and permissions first
        $this->call(RolesAndPermissionsSeeder::class);

        // Create categories
        $this->call(CategorySeeder::class);

        // Create admin users BEFORE reports
        $admins = [
            [
                'nom' => 'Thiane',
                'prenom' => 'Admin',
                'email' => 'thiane@samacite.sn',
                'telephone' => '+221701111111',
            ],
            [
                'nom' => 'Noé',
                'prenom' => 'Admin',
                'email' => 'noe@samacite.sn',
                'telephone' => '+221702222222',
            ],
            [
                'nom' => 'Alpha',
                'prenom' => 'Admin',
                'email' => 'alpha@samacite.sn',
                'telephone' => '+221703333333',
            ],
        ];

        foreach ($admins as $adminData) {
            $admin = User::firstOrCreate(
                ['email' => $adminData['email']],
                [
                    'nom' => $adminData['nom'],
                    'prenom' => $adminData['prenom'],
                    'telephone' => $adminData['telephone'],
                    'password' => bcrypt('password123'),
                ]
            );
            $admin->assignRole('Admin');
        }

        // Create additional reports with different users
        $this->call(AdditionalReportsSeeder::class);

        // Create notifications
        $this->call(NotificationSeeder::class);
    }
}
