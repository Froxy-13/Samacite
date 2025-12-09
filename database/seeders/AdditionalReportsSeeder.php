<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdditionalReportsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get categories
        $categories = Category::all();
        if ($categories->isEmpty()) {
            $this->command->warn('No categories found. Run CategorySeeder first.');
            return;
        }

        // Create regular users if they don't exist
        $users = [
            [
                'nom' => 'Sall',
                'prenom' => 'Aminata',
                'email' => 'aminata.sall@samacite.local',
                'telephone' => '+221771111111',
            ],
            [
                'nom' => 'Ndiaye',
                'prenom' => 'Moussa',
                'email' => 'moussa.ndiaye@samacite.local',
                'telephone' => '+221772222222',
            ],
            [
                'nom' => 'Ba',
                'prenom' => 'Fatima',
                'email' => 'fatima.ba@samacite.local',
                'telephone' => '+221773333333',
            ],
            [
                'nom' => 'Diop',
                'prenom' => 'Ousmane',
                'email' => 'ousmane.diop@samacite.local',
                'telephone' => '+221774444444',
            ],
        ];

        $createdUsers = [];
        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'nom' => $userData['nom'],
                    'prenom' => $userData['prenom'],
                    'telephone' => $userData['telephone'],
                    'password' => bcrypt('password'),
                ]
            );
            $user->assignRole('Utilisateur');
            $createdUsers[] = $user;
        }

        // Create 4 reports with Dakar locations and existing users
        $reports = [
            [
                'title' => 'Cratère sur la Route de Ngor',
                'description' => 'Un profond cratère s\'est formé sur la route de Ngor après les pluies. Très dangereux pour les véhicules.',
                'latitude' => 14.7667,
                'longitude' => -17.5000,
                'status' => 'En attente',
                'category_id' => $categories->first()->id,
                'user_id' => $createdUsers[0]->id,
            ],
            [
                'title' => 'Absence d\'électricité à Sacré-Cœur',
                'description' => 'Le quartier de Sacré-Cœur est sans électricité depuis 3 jours. Les habitants demandent une intervention urgente.',
                'latitude' => 14.6667,
                'longitude' => -17.4500,
                'status' => 'En cours',
                'category_id' => $categories->count() > 2 ? $categories[2]->id : $categories->first()->id,
                'user_id' => $createdUsers[1]->id,
            ],
            [
                'title' => 'Égouts bouchés à Pikine',
                'description' => 'Les égouts sont complètement bouchés à Pikine, causant des inondations dans les rues.',
                'latitude' => 14.7600,
                'longitude' => -17.4200,
                'status' => 'En attente',
                'category_id' => $categories->count() > 1 ? $categories[1]->id : $categories->first()->id,
                'user_id' => $createdUsers[2]->id,
            ],
            [
                'title' => 'Éclairage public défaillant à Mermoz',
                'description' => 'L\'éclairage public est complètement éteint à Mermoz depuis une semaine. Zone devenue très insécurisée la nuit.',
                'latitude' => 14.7200,
                'longitude' => -17.5200,
                'status' => 'Résolu',
                'category_id' => $categories->count() > 3 ? $categories[3]->id : $categories->first()->id,
                'user_id' => $createdUsers[3]->id,
            ],
        ];

        foreach ($reports as $reportData) {
            Report::create($reportData);
        }

        $this->command->info('✅ 4 signalements supplémentaires créés avec des utilisateurs existants!');
        $this->command->info('📍 Tous localisés à Dakar');
        $this->command->info('👥 Créateurs:');
        foreach ($createdUsers as $user) {
            $this->command->line("   - {$user->nom} {$user->prenom} ({$user->email})");
        }
    }
}
