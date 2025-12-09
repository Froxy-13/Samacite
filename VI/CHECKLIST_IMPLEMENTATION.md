# ✅ CHECKLIST D'IMPLÉMENTATION - Step by step

**Format**: Checklist détaillée avec chaque étape  
**Destinataire**: Backend developers  
**Temps total**: ~1 heure

---

## 📋 PRÉ-REQUIS

### Avant de commencer, vérifier:

- [ ] Vous avez accès au backend Laravel (`/path/to/laravel/project`)
- [ ] Vous avez un utilisateur admin créé
- [ ] Laravel Sanctum est installé (`composer require laravel/sanctum`)
- [ ] Spatie Permission est installé (`composer require spatie/laravel-permission`)
- [ ] Les migrations sont lancées (`php artisan migrate`)
- [ ] Vous avez un token admin valide

**Si pas d'utilisateur admin**:
```bash
php artisan tinker
>>> $user = User::first();
>>> $user->assignRole('Admin');
>>> exit
```

---

## 🔧 PHASE 1: Créer les contrôleurs

### Étape 1.1: Créer StatsController.php

```bash
# Terminal:
php artisan make:controller StatsController
```

**Fichier créé**: `app/Http/Controllers/StatsController.php`

- [ ] Fichier créé sans erreur

### Étape 1.2: Copier le code du StatsController

1. Ouvrir `jinx.md`
2. Chercher la section: "## 1️⃣ ENDPOINT: GET /api/stats/global"
3. Copier le code Laravel (dans le bloc php)
4. Coller dans `app/Http/Controllers/StatsController.php`
5. Remplacer le contenu existant

- [ ] Code copié dans StatsController.php
- [ ] Pas d'erreur de syntaxe (PHP)

### Étape 1.3: Vérifier le StatsController

```bash
# Terminal: Valider la syntaxe
php -l app/Http/Controllers/StatsController.php
# Doit afficher: "No syntax errors detected"
```

- [ ] Aucune erreur de syntaxe

### Étape 1.4: Créer NotificationController.php

```bash
# Terminal:
php artisan make:controller NotificationController
```

**Fichier créé**: `app/Http/Controllers/NotificationController.php`

- [ ] Fichier créé sans erreur

### Étape 1.5: Copier le code du NotificationController

1. Ouvrir `jinx.md`
2. Chercher la section: "## 3️⃣ ENDPOINT: GET /api/notifications"
3. Copier le code Laravel (dans le bloc php)
4. Coller dans `app/Http/Controllers/NotificationController.php`
5. Remplacer le contenu existant

- [ ] Code copié dans NotificationController.php
- [ ] Pas d'erreur de syntaxe (PHP)

### Étape 1.6: Vérifier le NotificationController

```bash
# Terminal: Valider la syntaxe
php -l app/Http/Controllers/NotificationController.php
# Doit afficher: "No syntax errors detected"
```

- [ ] Aucune erreur de syntaxe

---

## 🛣️ PHASE 2: Ajouter les routes

### Étape 2.1: Ouvrir routes/api.php

- [ ] Fichier `routes/api.php` ouvert dans l'éditeur

### Étape 2.2: Trouver le groupe middleware auth:sanctum

Chercher dans le fichier:
```php
Route::middleware('auth:sanctum')->group(function () {
    // routes ici
});
```

- [ ] Groupe trouvé et identifié

### Étape 2.3: Ajouter les imports (en haut du fichier)

Vérifier que ces imports existent (sinon les ajouter):

```php
use App\Http\Controllers\StatsController;
use App\Http\Controllers\NotificationController;
```

- [ ] Imports présents au top du fichier

### Étape 2.4: Ajouter les routes STATS

Dans le groupe `middleware('auth:sanctum')`, ajouter:

```php
// Statistiques
Route::get('/stats/global', [StatsController::class, 'globalStats']);
Route::get('/stats/timeline', [StatsController::class, 'timelineStats']);
```

- [ ] Routes stats ajoutées

### Étape 2.5: Ajouter les routes NOTIFICATIONS

Dans le groupe `middleware('auth:sanctum')`, ajouter:

```php
// Notifications
Route::get('/notifications', [NotificationController::class, 'index']);
Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
Route::delete('/notifications/{id}', [NotificationController::class, 'delete']);
Route::delete('/notifications', [NotificationController::class, 'deleteAll']);
```

- [ ] Routes notifications ajoutées

### Étape 2.6: Vérifier la syntaxe du fichier

```bash
# Terminal: Valider la syntaxe
php -l routes/api.php
# Doit afficher: "No syntax errors detected"
```

- [ ] Aucune erreur de syntaxe

---

## 🧪 PHASE 3: Tester les endpoints

### Étape 3.1: Obtenir un token admin

```bash
# Terminal: Se connecter en tant qu'admin
php artisan tinker

# Dans Tinker:
>>> $admin = User::where('email', 'admin@example.com')->first();
>>> $admin->createToken('api')->plainTextToken
# Copier le token affiché
>>> exit
```

- [ ] Token admin obtenu
- [ ] Token copié dans le presse-papiers

### Étape 3.2: Tester l'endpoint /stats/global

```bash
# Terminal: Remplacer TOKEN par votre token
curl -X GET \
  -H "Authorization: Bearer TOKEN_HERE" \
  -H "Accept: application/json" \
  http://localhost:8000/api/stats/global
```

**Réponse attendue**:
```json
{
  "success": true,
  "data": {
    "total_reports": X,
    "reports_by_status": {...},
    ...
  }
}
```

- [ ] Requête retourne 200 OK
- [ ] Réponse contient "success": true
- [ ] Réponse contient "data" avec stats

### Étape 3.3: Tester l'endpoint /stats/timeline

```bash
# Terminal:
curl -X GET \
  -H "Authorization: Bearer TOKEN_HERE" \
  http://localhost:8000/api/stats/timeline?period=7days
```

**Réponse attendue**:
```json
{
  "success": true,
  "data": {
    "days": 7,
    "timeline": [...]
  }
}
```

- [ ] Requête retourne 200 OK
- [ ] Réponse contient timeline

### Étape 3.4: Tester l'endpoint /notifications

```bash
# Terminal:
curl -X GET \
  -H "Authorization: Bearer TOKEN_HERE" \
  http://localhost:8000/api/notifications
```

**Réponse attendue**:
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [...],
    "total": X
  }
}
```

- [ ] Requête retourne 200 OK
- [ ] Réponse contient pagination

### Étape 3.5: Tester permission (non-admin devrait recevoir 403)

```bash
# Créer un token pour un utilisateur NON-ADMIN
php artisan tinker

>>> $user = User::where('email', 'user@example.com')->first();
>>> $user->createToken('api')->plainTextToken
# Copier ce token

# Tester avec ce token (devrait retourner 403):
curl -X GET \
  -H "Authorization: Bearer USER_TOKEN" \
  http://localhost:8000/api/stats/global
```

**Réponse attendue**: 403 Forbidden

- [ ] Non-admin reçoit 403 (Forbidden)

### Étape 3.6: Tester sans token (devrait recevoir 401)

```bash
# Tester sans Authorization header:
curl -X GET \
  http://localhost:8000/api/stats/global
```

**Réponse attendue**: 401 Unauthorized

- [ ] Sans token reçoit 401 (Unauthorized)

---

## 🔍 PHASE 4: Vérifier les logs

### Étape 4.1: Afficher les logs Laravel

```bash
# Terminal 1: Regarder les logs en temps réel
tail -f storage/logs/laravel.log
```

### Étape 4.2: Refaire un appel test

```bash
# Terminal 2: Faire un appel API
curl -H "Authorization: Bearer TOKEN" \
  http://localhost:8000/api/stats/global
```

**Voir dans Terminal 1**:
```
[2025-12-09 10:30:00] local.INFO: Récupération stats globales...
[2025-12-09 10:30:00] local.DEBUG: Query: SELECT COUNT(*) FROM reports
```

- [ ] Les logs montrent les appels à la base de données
- [ ] Pas d'erreur PHP
- [ ] Pas d'erreur SQL

---

## 📱 PHASE 5: Valider depuis le frontend

### Étape 5.1: Recharger le frontend

```bash
# Si le frontend est en dev mode (npm run dev)
# Il devrait se recharger automatiquement

# Sinon, recharger le navigateur:
# http://localhost:5173
# Appuyer sur F5
```

- [ ] Frontend rechargé

### Étape 5.2: Se connecter en admin

1. Ouvrir http://localhost:5173
2. Se connecter avec vos identifiants admin
3. Accédé au dashboard (/dashboard)

- [ ] Connecté en admin
- [ ] Navigateur affiche /dashboard

### Étape 5.3: Vérifier que les stats s'affichent

Ouvrir F12 (DevTools) → Console

Chercher les logs:
```
📊 [useStats] Récupération des stats globales...
🌐 [AXIOS] GET /stats/global 200
📊 [useStats] Réponse reçue: {...}
```

- [ ] Logs montrent 200 OK
- [ ] Pas d'erreur 404
- [ ] Données reçues

### Étape 5.4: Vérifier l'affichage visuel

Regarder le dashboard:
- [ ] Carte "Total signalements" affiche un nombre
- [ ] Carte "En attente" affiche un nombre
- [ ] Carte "En cours" affiche un nombre
- [ ] Carte "Résolu" affiche un nombre
- [ ] Graphique pie chart affiche les statuts
- [ ] Graphique timeline affiche l'évolution

### Étape 5.5: Vérifier les notifications

1. Ouvrir la barre des notifications (cloche en haut)
2. Regarder s'il y a des notifications

- [ ] Notifications affichées
- [ ] Pas d'erreur dans la console

---

## 🐛 PHASE 6: Debug si problème

### Si vous voyez une erreur:

#### Erreur: "404 Not Found"

- [ ] Vérifier que les routes sont dans `routes/api.php`
- [ ] Vérifier qu'elles sont dans le groupe `middleware('auth:sanctum')`
- [ ] Vérifier les imports des contrôleurs
- [ ] Relancer: `php artisan route:cache` ou `php artisan route:clear`

#### Erreur: "403 Forbidden"

- [ ] Vérifier que l'utilisateur a le rôle 'Admin'
  ```bash
  php artisan tinker
  >>> $user = User::find(1);
  >>> $user->hasRole('Admin')
  >>> $user->assignRole('Admin');
  ```
- [ ] Relancer les tests

#### Erreur: "500 Server Error"

- [ ] Vérifier les logs: `tail storage/logs/laravel.log`
- [ ] Chercher l'erreur PHP
- [ ] Corriger la syntaxe

#### Les stats s'affichent mais sont vides

- [ ] Vérifier qu'il y a des signalements en BDD
  ```bash
  php artisan tinker
  >>> Report::count()
  ```
- [ ] Si 0, créer des signalements depuis le frontend

---

## ✅ PHASE 7: Validation finale

### Checklist de fermeture:

```
[ ] StatsController.php créé et testé
[ ] NotificationController.php créé et testé
[ ] Routes ajoutées à routes/api.php
[ ] Test cURL /stats/global → 200 OK
[ ] Test cURL /stats/timeline → 200 OK
[ ] Test cURL /notifications → 200 OK
[ ] Test permission: non-admin → 403
[ ] Test permission: sans token → 401
[ ] Frontend affiche les stats
[ ] Frontend affiche les notifications
[ ] Dashboard complet et fonctionnel
```

---

## 🎉 RÉSULTAT FINAL

Si toutes les cases sont cochées:

✅ **Implémentation terminée!**

**Résultat visible**:
- Stats affichées au dashboard
- Graphiques remplis
- Notifications présentes
- Admin heureux 🎉

---

## 📞 Aide rapide

| Problème | Solution |
|----------|----------|
| 404 Not Found | Vérifier routes/api.php |
| 403 Forbidden | Vérifier rôle Admin |
| 500 Error | Vérifier logs |
| Données vides | Vérifier BDD |
| Frontend ne voit rien | F12 Console → logs |

---

## ⏱️ Timing

| Phase | Durée | Statut |
|-------|-------|--------|
| Phase 1: Créer contrôleurs | 15 min | [ ] |
| Phase 2: Ajouter routes | 5 min | [ ] |
| Phase 3: Tester endpoints | 10 min | [ ] |
| Phase 4: Vérifier logs | 5 min | [ ] |
| Phase 5: Valider frontend | 10 min | [ ] |
| Phase 6: Debug si besoin | 30 min | [ ] |
| Phase 7: Validation finale | 5 min | [ ] |

**Total**: ~80 minutes (ou ~1 heure si tout ok du premier coup)

---

**Créé par**: AI Implementation Team  
**Date**: 9 Décembre 2025  
**Format**: Checklist complète et détaillée  
**Complétude**: 100% actionnable ✅
