# 🔧 JINX.MD - DEBUG STATISTIQUES ET NOTIFICATIONS

**Date** : 9 Décembre 2025  
**Problème** : Les statistiques ne s'affichent PAS côté admin, les notifications arrivent mais ne s'affichent pas non plus  
**Causes potentielles** : Backend manquant + Permissions + CORS

---

## 🎯 DIAGNOSTIC RAPID

### État actuel observé:
```
✅ Signalement créé par utilisateur mobile → Apparaît côté admin
✅ Notification envoyée à utilisateur → Reçue côté mobile
❌ Statistiques côté admin → VIDE
❌ Notifications côté admin → NE S'AFFICHENT PAS (probablement)
```

### Conclusion
**C'EST DU CÔTÉ BACKEND QUE ÇA NE MARCHE PAS ⚠️**

Le frontend :
- ✅ Appelle les bon endpoints
- ✅ A les permissions correctes (admin check en place)
- ✅ Gère les erreurs correctement
- ❌ Reçoit rien du backend (endpoints non implémentés)

---

## 🔍 ANALYSA DÉTAILLÉE

### Frontend appelle:
```javascript
// useStats.js ligne 24
GET /api/stats/global

// useStats.js ligne 120  
GET /api/stats/timeline?period=7days

// useNotifications.js ligne 31
GET /api/notifications?page=1&per_page=20
```

### Backend DOIT retourner:

| Endpoint | Méthode | Statut | Implémenté? |
|----------|---------|--------|------------|
| `/api/stats/global` | GET | ❌ MANQUANT | Non |
| `/api/stats/timeline` | GET | ❌ MANQUANT | Non |
| `/api/notifications` | GET | ❌ MANQUANT | Non |
| `/api/reports/{id}` | GET | ✅ OK | Oui |
| `/api/reports` | POST | ✅ OK | Oui |

---

## 🛠️ SOLUTION: IMPLÉMENTATION BACKEND

### ⚠️ PRÉREQUIS
```bash
# Vérifier que ces packages sont installés
composer require laravel/sanctum
composer require spatie/laravel-permission
composer require symfony/http-client
```

---

## 1️⃣ ENDPOINT: GET /api/stats/global

**Route à ajouter** : `routes/api.php`

```php
<?php
// routes/api.php

// ✅ AJOUTER CETTE LIGNE dans le groupe middleware auth:sanctum
Route::middleware('auth:sanctum')->group(function () {
    // ... autres routes ...
    
    // 🟢 NOUVELLE ROUTE - Statistiques globales
    Route::get('/stats/global', [StatsController::class, 'globalStats']);
    
});
?>
```

**Contrôleur** : `app/Http/Controllers/StatsController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StatsController extends Controller
{
    /**
     * Récupère les statistiques globales
     * Permission requise: Admin seulement
     * 
     * Retourne:
     * - total_reports: Total signalements
     * - reports_by_status: { "En attente": {count, percentage}, "En cours": {...}, "Résolu": {...} }
     * - reports_by_category: [{name, count}, ...]
     * - average_reports_per_user: float
     * - resolution_rate: float (pourcentage)
     */
    public function globalStats(Request $request)
    {
        // 🔒 VÉRIFICATION PERMISSION ADMIN
        if (!auth()->user() || !auth()->user()->hasRole('Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé : vous devez être Admin'
            ], 403);
        }

        // 💾 CACHE 1 MINUTE pour perf
        $stats = Cache::remember('stats.global', 60, function () {
            $totalReports = Report::count();
            
            if ($totalReports === 0) {
                return [
                    'total_reports' => 0,
                    'reports_by_status' => [
                        'En attente' => ['count' => 0, 'percentage' => 0],
                        'En cours' => ['count' => 0, 'percentage' => 0],
                        'Résolu' => ['count' => 0, 'percentage' => 0],
                    ],
                    'reports_by_category' => [],
                    'average_reports_per_user' => 0,
                    'resolution_rate' => 0,
                ];
            }

            // 📊 Statistiques par statut
            $reportsByStatus = Report::selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->get()
                ->mapWithKeys(function($item) use ($totalReports) {
                    // Mapper les statuts vers le français si nécessaire
                    $statusMap = [
                        'pending' => 'En attente',
                        'in_progress' => 'En cours',
                        'resolved' => 'Résolu',
                        'en_attente' => 'En attente',
                        'en_cours' => 'En cours',
                        'resolu' => 'Résolu',
                    ];
                    
                    $label = $statusMap[$item->status] ?? $item->status;
                    $percentage = ($item->count / $totalReports) * 100;
                    
                    return [
                        $label => [
                            'count' => $item->count,
                            'percentage' => round($percentage, 1)
                        ]
                    ];
                })
                ->toArray();

            // 📂 Statistiques par catégorie
            $reportsByCategory = Category::withCount('reports')
                ->get()
                ->map(fn($cat) => [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'count' => $cat->reports_count ?? 0
                ])
                ->filter(fn($cat) => $cat['count'] > 0) // Exclure les catégories vides
                ->values()
                ->toArray();

            // 👥 Moyenne par utilisateur
            $userCount = User::count();
            $avgReportsPerUser = $userCount > 0 
                ? round($totalReports / $userCount, 2) 
                : 0;

            // ✅ Taux de résolution
            $resolvedCount = $reportsByStatus['Résolu']['count'] ?? 0;
            $resolutionRate = $totalReports > 0 
                ? round(($resolvedCount / $totalReports) * 100, 1) 
                : 0;

            return [
                'total_reports' => $totalReports,
                'reports_by_status' => $reportsByStatus,
                'reports_by_category' => $reportsByCategory,
                'average_reports_per_user' => $avgReportsPerUser,
                'resolution_rate' => $resolutionRate,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
?>
```

---

## 2️⃣ ENDPOINT: GET /api/stats/timeline

**Route à ajouter** : `routes/api.php`

```php
<?php
// routes/api.php

Route::middleware('auth:sanctum')->group(function () {
    // ... autres routes ...
    
    // 🟢 NOUVELLE ROUTE - Timeline d'évolution
    Route::get('/stats/timeline', [StatsController::class, 'timelineStats']);
    
});
?>
```

**Méthode à ajouter dans** `StatsController.php`

```php
<?php
// Ajouter cette méthode dans la classe StatsController

    /**
     * Récupère les stats par période (timeline)
     * Query params:
     * - period: 7days (default), 30days, 90days
     */
    public function timelineStats(Request $request)
    {
        // 🔒 VÉRIFICATION PERMISSION ADMIN
        if (!auth()->user() || !auth()->user()->hasRole('Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        $period = $request->query('period', '7days');
        
        // Déterminer le nombre de jours
        $days = match($period) {
            '30days' => 30,
            '90days' => 90,
            default => 7
        };

        $endDate = now();
        $startDate = $endDate->copy()->subDays($days);

        // 📊 Récupérer les signalements par jour
        $timeline = Report::selectRaw('DATE(created_at) as date, count(*) as total')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->toArray();

        // 🔄 Créer un tableau complet avec tous les jours (même les 0)
        $fullTimeline = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $dateStr = $date->format('Y-m-d');
            
            // Chercher si ce jour existe dans les données
            $dayData = collect($timeline)->firstWhere('date', $dateStr);
            
            $fullTimeline[] = [
                'date' => $dateStr,
                'total' => $dayData['total'] ?? 0
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'days' => $days,
                'start_date' => $startDate->toIso8601String(),
                'end_date' => $endDate->toIso8601String(),
                'timeline' => $fullTimeline
            ]
        ]);
    }

?>
```

---

## 3️⃣ ENDPOINT: GET /api/notifications

**Route à ajouter** : `routes/api.php`

```php
<?php
// routes/api.php

Route::middleware('auth:sanctum')->group(function () {
    // ... autres routes ...
    
    // 🟢 NOUVELLE ROUTE - Récupérer les notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'delete']);
    Route::delete('/notifications', [NotificationController::class, 'deleteAll']);
    
});
?>
```

**Contrôleur** : `app/Http/Controllers/NotificationController.php`

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Liste les notifications de l'admin connecté
     * Seuls les ADMINS peuvent voir les notifications
     */
    public function index(Request $request)
    {
        // 🔒 Vérifier que l'utilisateur est admin
        if (!auth()->user() || !auth()->user()->hasRole('Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé : notifications réservées aux admins'
            ], 403);
        }

        $perPage = $request->query('per_page', 20);
        $page = $request->query('page', 1);
        $unread = $request->query('unread'); // true = seulement non-lues

        $query = DatabaseNotification::where('notifiable_id', auth()->user()->id)
            ->orderBy('created_at', 'desc');

        // Filtrer les non-lues si demandé
        if ($unread === '1' || $unread === 'true') {
            $query->whereNull('read_at');
        }

        $notifications = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'message' => 'Notifications récupérées',
            'data' => [
                'current_page' => $notifications->currentPage(),
                'data' => $notifications->items(),
                'total' => $notifications->total(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
            ]
        ]);
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = DatabaseNotification::where('id', $id)
            ->where('notifiable_id', auth()->user()->id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification non trouvée'
            ], 404);
        }

        $notification->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Notification marquée comme lue'
        ]);
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead(Request $request)
    {
        DatabaseNotification::where('notifiable_id', auth()->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Toutes les notifications marquées comme lues'
        ]);
    }

    /**
     * Supprimer une notification
     */
    public function delete(Request $request, $id)
    {
        $notification = DatabaseNotification::where('id', $id)
            ->where('notifiable_id', auth()->user()->id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification non trouvée'
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification supprimée'
        ]);
    }

    /**
     * Supprimer toutes les notifications
     */
    public function deleteAll(Request $request)
    {
        DatabaseNotification::where('notifiable_id', auth()->user()->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Toutes les notifications supprimées'
        ]);
    }
}

?>
```

---

## 📋 CHECKLIST D'IMPLÉMENTATION

- [ ] **Créer** `app/Http/Controllers/StatsController.php`
- [ ] **Ajouter** méthodes `globalStats()` et `timelineStats()`
- [ ] **Créer** `app/Http/Controllers/NotificationController.php`
- [ ] **Ajouter** les 5 routes à `routes/api.php`
- [ ] **Tester** avec cURL ou Postman
- [ ] **Vérifier** les permissions Admin (Spatie)
- [ ] **Tester** depuis le frontend React

---

## 🧪 TESTS CURL

### Test 1: Vérifier token Admin
```bash
curl -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  http://localhost:8000/api/stats/global
```

**Réponse attendue** (200 OK):
```json
{
  "success": true,
  "data": {
    "total_reports": 5,
    "reports_by_status": {
      "En attente": {"count": 2, "percentage": 40.0},
      "En cours": {"count": 2, "percentage": 40.0},
      "Résolu": {"count": 1, "percentage": 20.0}
    },
    "reports_by_category": [
      {"id": 1, "name": "Route", "count": 3},
      {"id": 2, "name": "Eau", "count": 2}
    ],
    "average_reports_per_user": 2.5,
    "resolution_rate": 20.0
  }
}
```

### Test 2: Récupérer les notifications
```bash
curl -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  http://localhost:8000/api/notifications?page=1&per_page=20
```

**Réponse attendue** (200 OK):
```json
{
  "success": true,
  "message": "Notifications récupérées",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": "abc-123",
        "type": "App\\Notifications\\ReportCreated",
        "notifiable_id": 1,
        "data": {
          "title": "Nouveau signalement",
          "report_id": 5,
          "user_name": "Jean Dupont"
        },
        "read_at": null,
        "created_at": "2025-12-09T10:30:00Z"
      }
    ],
    "total": 1,
    "last_page": 1,
    "per_page": 20
  }
}
```

### Test 3: Non-admin (devrait retourner 403)
```bash
curl -H "Authorization: Bearer USER_TOKEN" \
  http://localhost:8000/api/stats/global
```

**Réponse attendue** (403 Forbidden):
```json
{
  "success": false,
  "message": "Accès refusé : vous devez être Admin"
}
```

---

## 🔒 VÉRIFICATIONS IMPORTANTES

### 1. Permissions Spatie
S'assurer que les admins ont les rôles:
```php
// database/seeders/RoleSeeder.php ou dans votre setup
$admin = Role::create(['name' => 'Admin', 'guard_name' => 'web']);
$user = Role::create(['name' => 'Utilisateur', 'guard_name' => 'web']);

// Attribuer des permissions
$admin->givePermissionTo(['stats.view', 'notification.send']);
```

### 2. User model
Vérifier que la table `users` a la colonne `roles`:
```php
// app/Models/User.php
use Spatie\Permission\Traits\HasRoles;

class User extends Model {
    use HasRoles;
}
```

### 3. Middleware CORS
Vérifier dans `config/cors.php`:
```php
'allowed_origins' => ['http://localhost:5173', 'http://localhost:5174'],
```

---

## 🚀 ÉTAPES DE DÉPLOIEMENT

1. **Créer les contrôleurs** (copier-coller le code ci-dessus)
2. **Ajouter les routes** à `routes/api.php`
3. **Tester avec cURL** (voir tests ci-dessus)
4. **Activer les logs** Laravel pour déboguer
5. **Recharger le frontend** (npm run dev)
6. **Vérifier que les stats s'affichent** dans le dashboard

---

## 📝 NOTES IMPORTANTES

### Frontend attend:
- ✅ `/api/stats/global` → Retourne `{ data: {...} }`
- ✅ `/api/stats/timeline?period=7days` → Retourne `{ data: { timeline: [...] } }`
- ✅ `/api/notifications?page=1` → Retourne `{ data: { data: [...] } }`

### Frontend fait:
- ✅ Appel `/stats/global` depuis `useStats()`
- ✅ Appel `/stats/timeline` depuis `useTimelineStats()`
- ✅ Appel `/notifications` depuis `useNotifications()`
- ✅ Vérifie le rôle Admin AVANT d'appeler
- ✅ Gère les erreurs 403 correctement

### Le backend DOIT:
- ✅ Retourner les stats normalisées en français
- ✅ Vérifier que l'utilisateur est Admin
- ✅ Retourner 403 si pas admin
- ✅ Respecter le format de réponse (structure `data`)
- ✅ Inclure les notifications avec le `report_id` pour navigation

---

## 🆘 DÉPANNAGE

### Symptôme: "Stats vides côté admin"
**Cause**: `/api/stats/global` retourne vide ou erreur  
**Solution**: Vérifier que le StatsController existe et est appelé

### Symptôme: "Erreur 403 Forbidden"
**Cause**: Utilisateur n'a pas le rôle Admin  
**Solution**: Vérifier que l'utilisateur a `hasRole('Admin')`

### Symptôme: "Notifications n'apparaissent pas"
**Cause**: `/api/notifications` ne retourne rien  
**Solution**: Vérifier que les notifications sont créées en BDD

### Symptôme: "CORS error"
**Cause**: Frontend en `localhost:5173`, backend refuse la requête  
**Solution**: Ajouter le domaine à `config/cors.php`

---

## ✅ PROCHAINES ÉTAPES

Une fois ces endpoints implémentés:

1. ✅ Frontend recevra les stats → Dashboard rempli
2. ✅ Frontend recevra les notifications → Panel actif
3. ✅ Navigation notification → Report fonctionne
4. ✅ Suppression/Archivage des rapports marche

**Résultat final**: Une application complète et fonctionnelle! 🎉

---

**Créé par**: AI Assistant  
**Pour**: Backend Laravel Team  
**Date**: 9 Décembre 2025  
**Statut**: PRÊT À IMPLÉMENTER ✅
