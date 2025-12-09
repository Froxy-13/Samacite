# 📋 PROMPT D'INTÉGRATION BACKEND - SamaCité

**Date** : Décembre 2025  
**Frontend** : React + Vite  
**Backend** : Laravel  
**Versions compatibles** : Laravel 10+ avec Sanctum + Spatie Permissions

---

## 🎯 CONTEXTE

Le frontend React est maintenant **complet et prêt à être intégré** avec le backend Laravel.

**État actuel du frontend** :
- ✅ Authentification via Sanctum
- ✅ Hooks React Query pour les données
- ✅ Suppression de signalements (DELETE /api/reports/{id})
- ✅ Archivage de signalements résolus (PATCH /api/reports/{id}/archive)
- ✅ Notifications pour admins seulement
- ✅ Statistiques globales pour admins
- ✅ Support multi-environnement (local/production)
- ⚠️ CORS à configurer
- ⚠️ Permissions à vérifier

**Problèmes observés en production** :
1. Notifications n'apparaissent pas → CORS + permissions admin
2. Statistiques n'apparaissent pas → CORS + permissions admin
3. Graphes s'affichent mais pas les cartes stats
4. Besoin de suppression unitaire des rapports
5. Besoin d'archivage des rapports résolus (disparaissent de la carte après 7 jours)

---

## ✅ CONFIGURATION CORS (CRITICAL)

Le frontend envoie des requêtes API depuis un domaine différent du backend.

### Pour LOCAL (développement)

**Laravel** : `http://localhost:8000`  
**Frontend** : `http://localhost:5173`

Dans `config/cors.php` de Laravel :

```php
'allowed_origins' => [
    'http://localhost:5173',      // Frontend local Vite
    'http://127.0.0.1:5173',      // Alternative localhost
    'http://localhost:3000',      // Si utilisation de port différent
],
'allowed_methods' => ['*'],           // GET, POST, PATCH, DELETE, etc.
'allowed_headers' => ['*'],           // Tous les headers
'max_age' => 86400,                   // Résultats en cache 24h
'supports_credentials' => true,       // Pour les cookies de session
```

### Pour PRODUCTION

**Replace** avec les vrais domaines :

```php
'allowed_origins' => [
    'http://localhost:5173',                              // Garder pour tests
    'https://VOTRE-DOMAINE-FRONTEND.com',                 // ⚠️ À remplacer
    'https://admin.VOTRE-DOMAINE-FRONTEND.com',           // Si sous-domaine
],
```

### Vérifier le middleware

Dans `app/Http/Middleware/HandleCors.php` ou `Kernel.php` :

```php
protected $middleware = [
    // ...
    \Fruitcake\Cors\HandleCors::class,  // ✅ Doit être présent
    // ...
];
```

---

## 🔐 AUTHENTIFICATION SANCTUM

Le frontend utilise `Bearer Token` dans l'header `Authorization`.

### Flux d'authentification attendu

**Frontend envoie** :
```
POST /api/auth/login
Content-Type: application/json

{
  "email": "admin@example.com",
  "password": "password123"
}
```

**Backend retourne** :
```json
{
  "success": true,
  "message": "Authentification réussie",
  "data": {
    "user": {
      "id": 1,
      "email": "admin@example.com",
      "name": "Admin User",
      "roles": ["Admin"],
      "permissions": [
        "dashboard.access",
        "user.view",
        "report.create",
        "report.view.any",
        "report.delete",
        "stats.view",
        "notification.send"
      ]
    },
    "token": "bearer-token-here-very-long-string..."
  }
}
```

### Configuration Sanctum

Dans `.env` :
```env
SANCTUM_STATEFUL_DOMAINS=localhost:5173,localhost:8000,votre-domaine.com
SESSION_DRIVER=cookie
```

### Vérification du token

Le frontend inclut automatiquement :
```
Authorization: Bearer {token}
```

**Vérifiez** que Laravel accepte ce header avec :
```php
// Dans app/Http/Kernel.php ou middleware
'sanctum:api', // ✅ Doit être présent
```

---

## 📊 ENDPOINT - STATISTIQUES GLOBALES

**Route** : `GET /api/stats/global`  
**Permission requise** : `stats.view` (Admin seulement)  
**Cache** : 1 minute (Redis recommandé)

### Format de réponse attendu

```json
{
  "success": true,
  "message": "Statistiques récupérées",
  "data": {
    "total_reports": 542,
    "reports_by_status": {
      "En attente": {
        "count": 120,
        "percentage": 22.1
      },
      "En cours": {
        "count": 280,
        "percentage": 51.7
      },
      "Résolu": {
        "count": 142,
        "percentage": 26.2
      }
    },
    "reports_by_category": [
      {
        "name": "Routier",
        "count": 230
      },
      {
        "name": "Assainissement",
        "count": 180
      },
      {
        "name": "Électricité",
        "count": 132
      }
    ],
    "average_reports_per_user": 15.3,
    "resolution_rate": 26.2
  }
}
```

### Implémentation Laravel

```php
// app/Http/Controllers/StatsController.php

public function globalStats(Request $request)
{
    // ✅ VÉRIFIER PERMISSION
    if (!auth()->user()->hasRole('Admin')) {
        return response()->json([
            'success' => false,
            'message' => 'Accès refusé : permissions insuffisantes'
        ], 403);
    }

    // Cache de 1 minute
    return Cache::remember('stats.global', now()->addMinute(), function () {
        $totalReports = Report::count();
        
        // Statistiques par statut
        $reportsByStatus = Report::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function($item) use ($totalReports) {
                $percentage = ($item->count / $totalReports) * 100;
                return [
                    $item->status => [
                        'count' => $item->count,
                        'percentage' => round($percentage, 1)
                    ]
                ];
            });

        // Statistiques par catégorie
        $reportsByCategory = Category::withCount('reports')
            ->get()
            ->map(fn($cat) => [
                'name' => $cat->name,
                'count' => $cat->reports_count
            ])
            ->toArray();

        // Moyenne par utilisateur
        $userCount = User::count();
        $avgReportsPerUser = $userCount > 0 
            ? round($totalReports / $userCount, 2) 
            : 0;

        // Taux de résolution
        $resolutionRate = $totalReports > 0 
            ? round(($reportsByStatus['Résolu']['count'] ?? 0) / $totalReports * 100, 1) 
            : 0;

        return [
            'total_reports' => $totalReports,
            'reports_by_status' => $reportsByStatus->toArray(),
            'reports_by_category' => $reportsByCategory,
            'average_reports_per_user' => $avgReportsPerUser,
            'resolution_rate' => $resolutionRate,
        ];
    });
}
```

**Route** :
```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/stats/global', [StatsController::class, 'globalStats']);
});
```

---

## 🔔 ENDPOINT - NOTIFICATIONS POUR ADMINS

**Route** : `GET /api/notifications`  
**Permission requise** : Admin seulement  
**Paramètres** :
- `unread=1` (optionnel) - filtrer les non-lues
- `type=report_comment` (optionnel) - filtrer par type
- `page=1` (optionnel)
- `per_page=20` (optionnel)

### ⚠️ Notifications depuis appareils mobiles

Les notifications peuvent être envoyées depuis :
- **Frontend web** (React admin)
- **Appareils mobiles** (Flutter)
- **Backend** (jobs/events)

**Important** : Chaque notification doit contenir `report_id` pour permettre au frontend de naviguer vers le signalement correspondant.

### Format de réponse attendu

```json
{
  "success": true,
  "message": "Notifications récupérées",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "user_id": null,
        "message": "Nouveau signalement reçu",
        "type": "report_created",
        "read_at": null,
        "created_at": "2024-12-09T10:30:00Z",
        "data": {
          "report_id": 123,
          "user_name": "Jean Dupont",
          "category": "Routier"
        }
      },
      {
        "id": 2,
        "user_id": null,
        "message": "Signalement #123 a un nouveau commentaire",
        "type": "report_comment",
        "read_at": null,
        "created_at": "2024-12-09T11:45:00Z",
        "data": {
          "report_id": 123,
          "comment_author": "Support Team"
        }
      }
    ],
    "total": 45,
    "per_page": 20,
    "current_page": 1,
    "last_page": 3
  }
}
```

### Implémentation Laravel

```php
// app/Http/Controllers/NotificationController.php

public function index(Request $request)
{
    // ✅ VÉRIFIER QUE C'EST UN ADMIN
    if (!auth()->user()->hasRole('Admin')) {
        return response()->json([
            'success' => false,
            'message' => 'Accès refusé : seuls les admins voient les notifications'
        ], 403);
    }

    $query = Notification::query();
    
    // Filtrer les non-lues
    if ($request->unread) {
        $query->whereNull('read_at');
    }
    
    // Filtrer par type
    if ($request->type) {
        $query->where('type', $request->type);
    }
    
    // Pagination
    $per_page = $request->per_page ?? 20;
    $notifications = $query->orderBy('created_at', 'DESC')
        ->paginate($per_page);
    
    return response()->json([
        'success' => true,
        'message' => 'Notifications récupérées',
        'data' => [
            'current_page' => $notifications->currentPage(),
            'data' => $notifications->items(),
            'total' => $notifications->total(),
            'per_page' => $per_page,
            'last_page' => $notifications->lastPage(),
        ]
    ]);
}
```

**Route** :
```php
Route::middleware(['auth:sanctum', 'role:Admin'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::delete('/notifications', [NotificationController::class, 'destroyAll']);
});
```

---

## 🗑️ ENDPOINT - SUPPRIMER UN SIGNALEMENT

**Route** : `DELETE /api/reports/{id}`  
**Permission requise** : Admin seulement  
**Statut** : 200 OK ou 204 No Content

### Réponse attendue

```json
{
  "success": true,
  "message": "Signalement supprimé avec succès"
}
```

### Implémentation Laravel

```php
// app/Http/Controllers/ReportController.php

public function destroy($id)
{
    // ✅ VÉRIFIER QUE C'EST UN ADMIN
    if (!auth()->user()->hasRole('Admin')) {
        return response()->json([
            'success' => false,
            'message' => 'Seuls les admins peuvent supprimer'
        ], 403);
    }

    $report = Report::findOrFail($id);
    
    // Supprimer les images associées
    $report->images()->delete();
    
    // Supprimer les commentaires
    $report->comments()->delete();
    
    // Supprimer le signalement
    $report->delete();

    return response()->json([
        'success' => true,
        'message' => 'Signalement supprimé avec succès'
    ]);
}
```

**Route** :
```php
Route::middleware(['auth:sanctum', 'role:Admin'])->delete('/reports/{id}', [ReportController::class, 'destroy']);
```

---

## 📦 ENDPOINT - ARCHIVER UN SIGNALEMENT

**Route** : `PATCH /api/reports/{id}/archive`  
**Permission requise** : Admin seulement  
**Validation** : Le signalement doit être au statut "Résolu"

### Payload

```json
{
  "archived_at": "2024-12-09T10:30:00Z"
}
```

### Réponse attendue

```json
{
  "success": true,
  "message": "Signalement archivé. Il disparaîtra de la carte après 7 jours."
}
```

### Implémentation Laravel

```php
// app/Http/Controllers/ReportController.php

public function archive(Request $request, $id)
{
    // ✅ VÉRIFIER PERMISSION & ADMIN
    if (!auth()->user()->hasRole('Admin')) {
        return response()->json([
            'success' => false,
            'message' => 'Accès refusé : seuls les admins peuvent archiver'
        ], 403);
    }

    $report = Report::findOrFail($id);

    // ✅ VÉRIFIER QUE C'EST RÉSOLU
    if ($report->status !== 'Résolu') {
        return response()->json([
            'success' => false,
            'message' => 'Seuls les signalements résolus peuvent être archivés'
        ], 422);
    }

    // ✅ ARCHIVER AVEC TIMESTAMP
    $report->update([
        'archived_at' => now(),
        'hidden_from_map_at' => now()->addDays(7)  // ⚠️ Caché de la carte après 7 jours
    ]);

    // ✅ INVALIDER LE CACHE
    Cache::forget('stats.global');

    return response()->json([
        'success' => true,
        'message' => 'Signalement archivé. Il disparaîtra de la carte après 7 jours.'
    ]);
}
```

**Route** :
```php
Route::middleware(['auth:sanctum', 'role:Admin'])->patch('/reports/{id}/archive', [ReportController::class, 'archive']);
```

### Migration - Ajouter les colonnes

```php
// database/migrations/YYYY_MM_DD_add_archive_columns_to_reports.php

Schema::table('reports', function (Blueprint $table) {
    $table->timestamp('archived_at')->nullable()->after('deleted_at');
    $table->timestamp('hidden_from_map_at')->nullable()->after('archived_at');
    $table->index('archived_at');
    $table->index('hidden_from_map_at');
});
```

Exécuter : `php artisan migrate`

---

## 🗺️ FILTRER LES RAPPORTS SUR LA CARTE

Quand le frontend récupère les rapports pour afficher sur la **CARTE** :

```php
// Ne JAMAIS afficher les rapports archivés depuis plus de 7 jours
public function index(Request $request)
{
    $query = Report::query()
        ->where(function($q) {
            $q->whereNull('archived_at')  // Non archivés
              ->orWhere('hidden_from_map_at', '>', now());  // Archivés mais < 7j
        });

    return $query->paginate(20);
}
```

**Pour la LISTE** (ReportsPage) : affiche TOUS les rapports (archivés ou pas)  
**Pour la CARTE** (MapPage) : exclut les rapports archivés depuis > 7 jours

---

## 🧹 JOB - NETTOYER LES ANCIENS RAPPORTS ARCHIVÉS

Après 180 jours, on peut supprimer physiquement les vieux rapports archivés.

### Créer le Job

```php
// app/Jobs/CleanupArchivedReports.php

<?php

namespace App\Jobs;

use App\Models\Report;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CleanupArchivedReports implements ShouldQueue
{
    use Queueable;

    public function handle()
    {
        // Supprimer les rapports archivés depuis plus de 180 jours
        Report::where('archived_at', '<', now()->subDays(180))
            ->forceDelete();

        logger()->info('🧹 Cleanup archivés : ' . Report::onlyTrashed()->count() . ' rapports supprimés');
    }
}
```

### Enregistrer dans le scheduler

```php
// app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
    $schedule->job(new CleanupArchivedReports())
        ->daily()
        ->at('02:00');  // 2 AM chaque jour
}
```

---

## 📝 MIGRATION DE DONNÉES

### Ajouter les colonnes à la table `reports`

```php
// database/migrations/YYYY_MM_DD_add_archive_columns_to_reports.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('reports', function (Blueprint $table) {
            // Colonne pour marquer la date d'archivage
            $table->timestamp('archived_at')
                ->nullable()
                ->after('updated_at')
                ->comment('Date d\'archivage du signalement');

            // Colonne pour marquer quand disparaître de la carte (7j après archivage)
            $table->timestamp('hidden_from_map_at')
                ->nullable()
                ->after('archived_at')
                ->comment('Date de disparition de la carte (7j après archivage)');

            // Index pour les requêtes de filtrage
            $table->index('archived_at');
            $table->index('hidden_from_map_at');
            $table->index('status');  // Aussi utile pour les stats
        });
    }

    public function down()
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex(['archived_at']);
            $table->dropIndex(['hidden_from_map_at']);
            $table->dropColumn(['archived_at', 'hidden_from_map_at']);
        });
    }
};
```

---

## 🔒 PERMISSIONS SPATIE À VÉRIFIER

Assurez-vous que ces permissions existent dans votre base :

```php
// database/seeders/PermissionSeeder.php

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

public function run()
{
    // Créer les permissions
    $permissions = [
        'dashboard.access',
        'user.view',
        'user.manage',
        'report.create',
        'report.view.own',
        'report.view.any',
        'report.update',
        'report.delete',        // ✅ Pour DELETE /api/reports/{id}
        'report.assign',
        'report.change_state',
        'report.comment',
        'notification.send',
        'stats.view',           // ✅ Pour GET /api/stats/global
        'settings.manage',
        'file.upload',
    ];

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission);
    }

    // Créer les rôles et assigner les permissions
    $adminRole = Role::findOrCreate('Admin');
    $adminRole->syncPermissions($permissions);  // Admin a toutes les permissions

    $userRole = Role::findOrCreate('Utilisateur');
    $userRole->syncPermissions([
        'report.create',
        'report.view.own',
        'report.comment',
        'file.upload',
    ]);
}
```

Exécuter :
```bash
php artisan db:seed PermissionSeeder
```

---

## 🧪 TEST LOCAL

### Avant de passer à la production

1. **Authentification**
   ```bash
   curl -X POST http://localhost:8000/api/auth/login \
     -H "Content-Type: application/json" \
     -d '{"email":"admin@example.com","password":"password123"}'
   ```

2. **Statistiques** (avec le token)
   ```bash
   curl -X GET http://localhost:8000/api/stats/global \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json"
   ```

3. **Notifications**
   ```bash
   curl -X GET http://localhost:8000/api/notifications \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json"
   ```

4. **Suppression d'un signalement**
   ```bash
   curl -X DELETE http://localhost:8000/api/reports/1 \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json"
   ```

5. **Archivage d'un signalement**
   ```bash
   curl -X PATCH http://localhost:8000/api/reports/1/archive \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{}' \
   ```

---

## ✅ CHECKLIST D'IMPLÉMENTATION

- [ ] Configurer CORS dans `config/cors.php`
- [ ] Vérifier Sanctum dans `config/sanctum.php`
- [ ] Créer endpoint `GET /api/stats/global`
- [ ] Créer endpoint `GET /api/notifications`
- [ ] Créer endpoint `DELETE /api/reports/{id}`
- [ ] Créer endpoint `PATCH /api/reports/{id}/archive`
- [ ] Ajouter les colonnes `archived_at` et `hidden_from_map_at` aux rapports
- [ ] Créer le Job `CleanupArchivedReports`
- [ ] Enregistrer le scheduler
- [ ] Vérifier les permissions Spatie pour Admin
- [ ] Tester localement avec le frontend Vite
- [ ] Tester en production avec les bons domaines

---

## 🚀 DÉPLOIEMENT

Une fois tout testé en local :

1. Mettre à jour `.env.production` du frontend avec les bons domaines
2. Déployer le backend sur le serveur de production
3. Configurer CORS avec les domaines de production
4. Exécuter les migrations : `php artisan migrate`
5. Déployer le frontend
6. Tester les endpoints en production

---

## 📞 SUPPORT

**Problèmes courants** :

| Problème | Cause | Solution |
|----------|-------|----------|
| CORS error en production | Domaines non configurés | Vérifier `config/cors.php` avec les bons domaines |
| 403 Forbidden sur stats | Utilisateur n'est pas Admin | Vérifier le rôle dans `users_roles` |
| 401 Unauthorized | Token invalide/expiré | Refonte authentification |
| Notifications vides | Pas d'admin | Vérifier que l'utilisateur a le rôle Admin |
| Rapports archivés s'affichent | Pas de filtrage côté backend | Ajouter le `where` pour exclure les > 7 jours |

---

**Date de création** : 9 Décembre 2025  
**Auteur** : Frontend Samacité  
**Version** : 1.0
