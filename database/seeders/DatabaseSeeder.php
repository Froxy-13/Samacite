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

        // Create test users
        $admin = User::factory()->create([
            'nom' => 'Admin',
            'prenom' => 'Utilisateur',
            'email' => 'admin@example.com',
            'telephone' => '+221701111111',
            'password' => bcrypt('password123'),
        ]);
        $admin->assignRole('Admin');

        $user = User::factory()->create([
            'nom' => 'Test',
            'prenom' => 'Utilisateur',
            'email' => 'user@example.com',
            'telephone' => '+221702222222',
            'password' => bcrypt('password123'),
        ]);
        $user->assignRole('Utilisateur');
    }
}
