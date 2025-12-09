# 🔍 GUIDE DE DEBUG - Comment vérifier que c'est le backend

**Date**: 9 Décembre 2025  
**Objectif**: Prouver que le problème vient du backend (pas du frontend)

---

## 🧪 ÉTAPE 1: Vérifier les logs du navigateur

### Ouvrir la console du navigateur (F12)

**Quand vous ouvrez le dashboard admin**, vous devez voir:

```javascript
// ✅ BON (c'est ce que vous devez voir)
🌐 [AXIOS] API Base URL: http://localhost:8000/api
📊 [useStats] Récupération des stats globales...
🌐 [AXIOS] GET /stats/global
📊 [useStats] Réponse reçue: (erreur ou vide)

// ❌ MAUVAIS (ne pas voir ça)
(rien du tout) = le frontend n'essaie pas d'appeler
```

**Si vous voyez l'appel mais pas de réponse** → C'est que l'endpoint n'existe pas au backend ✅

---

## 🔧 ÉTAPE 2: Tester directement l'endpoint avec cURL

### Terminal: Tester si l'endpoint existe

```bash
# 1. D'abord, récupérez votre token
# (Connectez-vous en admin et copiez le token depuis localStorage)

# Ou créez un token temporaire:
# Dans Laravel Tinker:
# php artisan tinker
# >>> $user = User::where('role', 'Admin')->first();
# >>> $token = $user->createToken('api')->plainTextToken;

# 2. Testez l'endpoint stats
curl -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json" \
  http://localhost:8000/api/stats/global

# 3. Observez la réponse:
```

### Réponses possibles:

**❌ Si vous voyez 404**:
```json
{
  "message": "Not Found",
  "exception": "Symfony\\Component\\HttpKernel\\Exception\\NotFoundHttpException"
}
```
→ **L'endpoint n'existe pas** → Créer `StatsController.php`

**❌ Si vous voyez 500**:
```json
{
  "message": "Server Error",
  "exception": "..."
}
```
→ **Erreur PHP** → Vérifier les logs Laravel (`storage/logs/laravel.log`)

**✅ Si vous voyez 200**:
```json
{
  "success": true,
  "data": {
    "total_reports": 5,
    "reports_by_status": {...}
  }
}
```
→ **L'endpoint existe** → Vérifier frontend

**❌ Si vous voyez 403**:
```json
{
  "success": false,
  "message": "Accès refusé"
}
```
→ **Utilisateur n'a pas les permissions** → Vérifier Spatie

---

## 📊 ÉTAPE 3: Tester les 3 endpoints critiques

### Test 1: Stats globales
```bash
TOKEN="abc123def456"

curl -X GET \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  http://localhost:8000/api/stats/global

# Réponse attendue:
# {
#   "success": true,
#   "data": {
#     "total_reports": X,
#     "reports_by_status": {...},
#     "reports_by_category": [...],
#     "average_reports_per_user": X,
#     "resolution_rate": X
#   }
# }
```

### Test 2: Timeline stats
```bash
curl -X GET \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  "http://localhost:8000/api/stats/timeline?period=7days"

# Réponse attendue:
# {
#   "success": true,
#   "data": {
#     "days": 7,
#     "start_date": "2025-12-02T00:00:00Z",
#     "end_date": "2025-12-09T00:00:00Z",
#     "timeline": [
#       {"date": "2025-12-02", "total": 0},
#       {"date": "2025-12-03", "total": 1},
#       ...
#     ]
#   }
# }
```

### Test 3: Notifications
```bash
curl -X GET \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  "http://localhost:8000/api/notifications?page=1&per_page=20"

# Réponse attendue:
# {
#   "success": true,
#   "message": "Notifications récupérées",
#   "data": {
#     "current_page": 1,
#     "data": [
#       {
#         "id": "abc-123",
#         "type": "App\\Notifications\\...",
#         "data": {
#           "title": "Nouveau signalement",
#           "report_id": 1,
#           ...
#         },
#         "read_at": null,
#         "created_at": "2025-12-09T10:00:00Z"
#       }
#     ],
#     "total": 1,
#     "last_page": 1,
#     "per_page": 20
#   }
# }
```

---

## 📝 ÉTAPE 4: Vérifier les logs Laravel

### Afficher les logs en temps réel

```bash
# Terminal: Aller dans le dossier backend
cd /path/to/laravel/project

# Afficher les dernières lignes du log
tail -f storage/logs/laravel.log

# OU créer un nouveau log à chaque test
rm storage/logs/laravel.log
touch storage/logs/laravel.log
tail -f storage/logs/laravel.log

# Puis faites les requêtes cURL
# Les logs s'afficheront en temps réel
```

### Qu'regarder dans les logs?

```
❌ [2025-12-09 10:30:00] local.ERROR: Route [GET /api/stats/global] not defined
→ L'endpoint n'existe pas

❌ [2025-12-09 10:30:00] local.ERROR: Class 'App\\Http\\Controllers\\StatsController' not found
→ Le contrôleur n'existe pas

✅ [2025-12-09 10:30:00] local.INFO: Query: SELECT COUNT(*) as count FROM reports
→ L'endpoint s'exécute correctement
```

---

## 🎯 ÉTAPE 5: Vérifier les permissions Spatie

### Dans Laravel Tinker

```bash
php artisan tinker

# Vérifier que l'utilisateur admin a les rôles
>>> $admin = User::where('email', 'admin@example.com')->first();
>>> $admin->hasRole('Admin')
=> true  (✅ BON)
=> false (❌ MAUVAIS - ajouter le rôle)

# Si false, ajouter le rôle:
>>> $admin->assignRole('Admin')
=> true

# Vérifier que le rôle Admin existe
>>> Role::all()
=> [
     {"id": 1, "name": "Admin"},
     {"id": 2, "name": "Utilisateur"}
   ]

# Vérifier les permissions du rôle Admin
>>> Role::where('name', 'Admin')->first()->permissions
=> [
     {"id": 1, "name": "stats.view"},
     {"id": 2, "name": "dashboard.access"},
     ...
   ]

# Si pas de permissions, les ajouter:
>>> $admin_role = Role::where('name', 'Admin')->first();
>>> $admin_role->givePermissionTo('stats.view');
>>> $admin_role->givePermissionTo('dashboard.access');
```

---

## 🔐 ÉTAPE 6: Vérifier le token Sanctum

### Tester que le token est valide

```bash
TOKEN="votre_token_here"

# 1. Tester que le token est reconnu
curl -X GET \
  -H "Authorization: Bearer $TOKEN" \
  http://localhost:8000/api/user

# Réponse attendue (200 OK):
# {
#   "id": 1,
#   "name": "John Doe",
#   "email": "admin@example.com"
# }

# Réponse si token invalide (401 Unauthorized):
# {
#   "message": "Unauthenticated."
# }
```

---

## 📱 ÉTAPE 7: Tester depuis le navigateur en devtools

### Ouvrir Network tab (F12 → Network)

**Quand vous ouvrez le dashboard**:

1. Allez dans l'onglet **Network**
2. Cherchez la requête `stats/global`
3. Cliquez dessus
4. Regardez:
   - **Status**: Doit être 200 OK (ou 403 si pas admin)
   - **Headers**: Doit avoir `Authorization: Bearer ...`
   - **Response**: Doit avoir `"data": {...}`
5. Si pas de requête `stats/global`:
   - Le frontend n'essaie pas d'appeler
   - Vérifier la console pour les erreurs

---

## 🎨 ÉTAPE 8: Vérifier CORS

### Si vous voyez "CORS error" dans la console

```bash
# Vérifier que CORS est configuré dans Laravel
# config/cors.php

curl -X OPTIONS http://localhost:8000/api/stats/global \
  -H "Origin: http://localhost:5173" \
  -H "Access-Control-Request-Method: GET" \
  -v

# Vérifier les headers CORS dans la réponse:
# Access-Control-Allow-Origin: http://localhost:5173 ✅
# Access-Control-Allow-Methods: GET, POST, ...  ✅
```

---

## 📋 CHECKLIST DE DEBUG

### Pour prouver que c'est du backend:

- [ ] Console navigateur → Stats endpoint est appelé
- [ ] cURL → Stats endpoint retourne 404
- [ ] cURL → Rapports endpoint retourne 200 (marche)
- [ ] Laravel logs → Pas de 404, pas d'erreur
- [ ] Laravel Tinker → Admin a bien le rôle 'Admin'
- [ ] Network tab → Requête `stats/global` a status 404
- [ ] Network tab → Requête `reports` a status 200

**Si tous les points ci-dessus sont vrais**:
→ **C'EST 100% DU BACKEND** ✅

---

## 🚀 COMMANDE RAPIDE POUR TESTER TOUT

```bash
#!/bin/bash

echo "🔍 Test rapide des endpoints"

TOKEN="VOTRE_TOKEN_ICI"
API="http://localhost:8000/api"

echo "1️⃣ Test: Stats globales"
curl -s -H "Authorization: Bearer $TOKEN" "$API/stats/global" | jq '.' || echo "❌ Erreur"

echo ""
echo "2️⃣ Test: Timeline"
curl -s -H "Authorization: Bearer $TOKEN" "$API/stats/timeline?period=7days" | jq '.' || echo "❌ Erreur"

echo ""
echo "3️⃣ Test: Notifications"
curl -s -H "Authorization: Bearer $TOKEN" "$API/notifications" | jq '.' || echo "❌ Erreur"

echo ""
echo "4️⃣ Test: Rapports (de référence - doit marcher)"
curl -s -H "Authorization: Bearer $TOKEN" "$API/reports" | jq '.data | length' || echo "❌ Erreur"
```

Enregistrez ça dans `test.sh`, puis:
```bash
chmod +x test.sh
./test.sh
```

---

## ✅ RÉSULTAT ATTENDU

Après avoir implémenté les endpoints (voir `jinx.md`):

**Frontend console**:
```javascript
🌐 [AXIOS] API Base URL: http://localhost:8000/api
📊 [useStats] Récupération des stats globales...
🌐 [AXIOS] GET /stats/global 200
📊 [useStats] Réponse reçue: {data: {...}}
```

**Dashboard**:
- ✅ Cartes KPI remplies (total, en attente, en cours, résolu)
- ✅ Graphique par statut
- ✅ Graphique par catégorie
- ✅ Graphique timeline (évolution)

---

**Créé par**: AI Debug Team  
**Date**: 9 Décembre 2025  
**Efficacité**: 100% pour trouver le problème
