<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Routes & Chaussées',
                'description' => 'Problèmes liés aux routes, nids-de-poule, dégradation de chaussée',
            ],
            [
                'name' => 'Eau & Assainissement',
                'description' => 'Fuites d\'eau, problèmes d\'approvisionnement, eaux usées',
            ],
            [
                'name' => 'Électricité & Éclairage',
                'description' => 'Pannes électriques, lampadaires défaillants, lignes électriques endommagées',
            ],
            [
                'name' => 'Déchets & Propreté',
                'description' => 'Accumulation de déchets, propreté des espaces publics, collecte insuffisante',
            ],
            [
                'name' => 'Sécurité Publique',
                'description' => 'Incivilités, criminalité, zones dangereuses, surveillance insuffisante',
            ],
            [
                'name' => 'Autre',
                'description' => 'Autres catégories non mentionnées ci-dessus',
            ],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['name' => $category['name']],
                ['description' => $category['description']]
            );
        }
    }
}
