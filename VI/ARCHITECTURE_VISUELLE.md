# 🏗️ ARCHITECTURE - Visualisation du problème

**Visualisation**: Comment les données circulent dans l'application

---

## 📊 Schéma actuel (AVANT: Ça ne marche pas)

```
┌─────────────────────────────────────────────────────────────────┐
│                    NAVIGATEUR (Frontend React)                  │
│  http://localhost:5173                                          │
│                                                                 │
│  ┌──────────────────────────────────────────────────────┐      │
│  │ DashboardPage.jsx                                    │      │
│  │  ├─ useStats() → Appelle GET /api/stats/global      │      │
│  │  └─ useNotifications() → Appelle GET /api/notif...  │      │
│  └──────────────────────────────────────────────────────┘      │
│                           ↓                                      │
│  ┌──────────────────────────────────────────────────────┐      │
│  │ axiosInstance                                        │      │
│  │  ├─ Ajoute Authorization: Bearer TOKEN              │      │
│  │  ├─ Envoie requête HTTPS                            │      │
│  │  └─ Reçoit réponse                                  │      │
│  └──────────────────────────────────────────────────────┘      │
└─────────────────────────────────────────────────────────────────┘
                           ↓  (HTTP)
                    http://localhost:8000
                           ↓
┌─────────────────────────────────────────────────────────────────┐
│                   SERVER (Backend Laravel)                      │
│  http://localhost:8000                                          │
│                                                                 │
│  ┌──────────────────────────────────────────────────────┐      │
│  │ routes/api.php                                       │      │
│  │  ├─ GET /reports → ReportController ✅ EXISTS       │      │
│  │  ├─ GET /stats/global → StatsController ❌ MISSING  │      │
│  │  ├─ GET /notifications → NotifController ❌ MISSING │      │
│  │  └─ PATCH /reports/{id} → ✅ EXISTS                │      │
│  └──────────────────────────────────────────────────────┘      │
│                           ↓                                      │
│  ❌ Route /stats/global NOT FOUND                               │
│     Retourne: 404 Not Found                                     │
│                                                                 │
│  ❌ Route /notifications NOT FOUND                              │
│     Retourne: 404 Not Found                                     │
└─────────────────────────────────────────────────────────────────┘
                           ↑  (HTTP 404)
                           ↓
┌─────────────────────────────────────────────────────────────────┐
│                    NAVIGATEUR (Frontend React)                  │
│                                                                 │
│  ❌ Stats = Vides (pas de données)                              │
│  ❌ Notifications = Vides (pas de données)                      │
│  ✅ Rapports = Remplis (endpoint existe)                        │
│                                                                 │
│  Admin voit: Dashboard vide 🤷                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 📊 Schéma corrigé (APRÈS: Ça marche)

```
┌─────────────────────────────────────────────────────────────────┐
│                    NAVIGATEUR (Frontend React)                  │
│  http://localhost:5173                                          │
│                                                                 │
│  ┌──────────────────────────────────────────────────────┐      │
│  │ DashboardPage.jsx                                    │      │
│  │  ├─ useStats() → Appelle GET /api/stats/global      │      │
│  │  └─ useNotifications() → Appelle GET /api/notif...  │      │
│  └──────────────────────────────────────────────────────┘      │
│                           ↓                                      │
│  ┌──────────────────────────────────────────────────────┐      │
│  │ axiosInstance                                        │      │
│  │  ├─ Ajoute Authorization: Bearer TOKEN              │      │
│  │  ├─ Envoie requête HTTPS                            │      │
│  │  └─ Reçoit réponse                                  │      │
│  └──────────────────────────────────────────────────────┘      │
└─────────────────────────────────────────────────────────────────┘
                           ↓  (HTTP)
                    http://localhost:8000
                           ↓
┌─────────────────────────────────────────────────────────────────┐
│                   SERVER (Backend Laravel)                      │
│  http://localhost:8000                                          │
│                                                                 │
│  ┌──────────────────────────────────────────────────────┐      │
│  │ routes/api.php                                       │      │
│  │  ├─ GET /reports → ReportController ✅ EXISTS       │      │
│  │  ├─ GET /stats/global → StatsController ✅ EXISTS   │      │
│  │  ├─ GET /stats/timeline → StatsController ✅ EXISTS │      │
│  │  ├─ GET /notifications → NotifController ✅ EXISTS  │      │
│  │  └─ PATCH /reports/{id} → ✅ EXISTS                │      │
│  └──────────────────────────────────────────────────────┘      │
│                           ↓                                      │
│  ┌──────────────────────────────────────────────────────┐      │
│  │ StatsController@globalStats()                        │      │
│  │  ├─ Vérifier permission: hasRole('Admin') ✅        │      │
│  │  ├─ SELECT COUNT(*) FROM reports → 5                │      │
│  │  ├─ SELECT status, COUNT(*) GROUP BY status         │      │
│  │  ├─ SELECT * FROM categories                        │      │
│  │  └─ Retourner: { data: {...} } (200 OK)             │      │
│  └──────────────────────────────────────────────────────┘      │
│                           ↓                                      │
│  ┌──────────────────────────────────────────────────────┐      │
│  │ NotificationController@index()                       │      │
│  │  ├─ Vérifier permission: hasRole('Admin') ✅        │      │
│  │  ├─ SELECT * FROM notifications WHERE user = me     │      │
│  │  ├─ Paginer (page=1, per_page=20)                   │      │
│  │  └─ Retourner: { data: { data: [...] } } (200 OK)   │      │
│  └──────────────────────────────────────────────────────┘      │
└─────────────────────────────────────────────────────────────────┘
                           ↑  (HTTP 200 + données)
                           ↓
┌─────────────────────────────────────────────────────────────────┐
│                    NAVIGATEUR (Frontend React)                  │
│                                                                 │
│  ✅ Stats = Remplies (données reçues)                           │
│     ├─ Total: 5                                                 │
│     ├─ En attente: 2 (40%)                                      │
│     ├─ En cours: 2 (40%)                                        │
│     └─ Résolu: 1 (20%)                                          │
│                                                                 │
│  ✅ Notifications = Remplies (données reçues)                   │
│     ├─ Notification 1: Nouveau signalement                      │
│     ├─ Notification 2: Changement de statut                     │
│     └─ ...                                                      │
│                                                                 │
│  ✅ Rapports = Remplis (endpoint existe déjà)                   │
│                                                                 │
│  Admin voit: Dashboard complet et rempli 🎉                     │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🔄 Flux de données détaillé

### Scenario: Admin ouvre le dashboard

```
Time 0:00
  User: Clique sur "Dashboard"
  ↓
  Frontend charge DashboardPage.jsx
  ↓
  useStats() hook s'exécute
  useNotifications() hook s'exécute
  ↓

Time 0:05
  Frontend: Vérifie que user.roles.includes('Admin')
    ✅ TRUE (c'est un admin)
  ↓
  Frontend: Construit 2 requêtes HTTP
    Requête 1: GET /api/stats/global
    Requête 2: GET /api/notifications?page=1&per_page=20
  ↓

Time 0:10
  Frontend: Envoie les requêtes au backend
    Authorization: Bearer TOKEN_ADMIN
  ↓

Time 0:15 ❌ AVANT (Ça ne marche pas)
  Backend reçoit: GET /api/stats/global
  Backend cherche la route...
  ❌ Route n'existe pas!
  Backend retourne: 404 Not Found
  ↓
  Frontend reçoit l'erreur
  useStats() log l'erreur mais continue
  DashboardStats affiche VIDE
  ↓

Time 0:15 ✅ APRÈS (Ça marche)
  Backend reçoit: GET /api/stats/global
  Backend cherche la route...
  ✅ Route existe!
  Backend exécute: StatsController@globalStats()
    ├─ Vérifier permission: hasRole('Admin')
    ├─ Récupérer data depuis la BDD
    └─ Retourner 200 + données
  ↓
  Frontend reçoit: 200 OK + stats
  useStats() met en cache (React Query)
  DashboardStats affiche les stats ✅
  ↓

Time 0:20
  Backend reçoit: GET /api/notifications
  Backend exécute: NotificationController@index()
    ├─ Vérifier permission: hasRole('Admin')
    ├─ Récupérer notifications depuis la BDD
    └─ Retourner 200 + données paginées
  ↓
  Frontend reçoit: 200 OK + notifications
  useNotifications() met en cache
  NotificationPanel affiche les notifications ✅
  ↓

Time 0:25
  Admin voit: Dashboard rempli avec stats + graphiques + notifications 🎉
```

---

## 🗂️ Structure fichiers backend

### AVANT (Incomplet)

```
app/
  └─ Http/
      └─ Controllers/
          ├─ ReportController.php ✅ OK
          ├─ AuthController.php ✅ OK
          ├─ UserController.php ✅ OK
          ├─ StatsController.php ❌ MANQUANT
          └─ NotificationController.php ❌ MANQUANT
```

### APRÈS (Complet)

```
app/
  └─ Http/
      └─ Controllers/
          ├─ ReportController.php ✅ OK
          ├─ AuthController.php ✅ OK
          ├─ UserController.php ✅ OK
          ├─ StatsController.php ✅ CRÉÉ (voir jinx.md)
          └─ NotificationController.php ✅ CRÉÉ (voir jinx.md)
```

---

## 📊 Tableau de comparaison

### Avant vs Après

```
┌──────────────────┬─────────────────────┬────────────────────┐
│ Composant        │ AVANT (Ne marche)   │ APRÈS (Marche)     │
├──────────────────┼─────────────────────┼────────────────────┤
│ Frontend         │ ✅ Code correct     │ ✅ Code correct    │
│ Routes Backend   │ ❌ Manquantes       │ ✅ Ajoutées        │
│ Contrôleurs      │ ❌ Manquants        │ ✅ Créés           │
│ Données DB       │ ✅ Existantes       │ ✅ Existantes      │
│ Permissions      │ ⚠️ À vérifier       │ ✅ Vérifiées       │
│ Stats affichées  │ ❌ NON              │ ✅ OUI             │
│ Notif affichées  │ ❌ NON              │ ✅ OUI             │
│ Dashboard        │ ❌ Vide             │ ✅ Rempli          │
└──────────────────┴─────────────────────┴────────────────────┘
```

---

## 🔐 Flux de permissions

### Admin (Peut voir les stats)

```
Frontend:
  user = { roles: ['Admin'] }
  ├─ Check: user.roles.includes('Admin') → TRUE
  └─ Envoyer requête

Backend:
  auth()->user()->hasRole('Admin') → TRUE
  ├─ Récupérer données
  └─ Retourner 200 + données
```

### User (Ne peut pas voir les stats)

```
Frontend:
  user = { roles: ['Utilisateur'] }
  ├─ Check: user.roles.includes('Admin') → FALSE
  └─ NE PAS envoyer requête

Backend:
  (ne reçoit pas de requête de user non-admin)
  
OU si la requête arrive:
  auth()->user()->hasRole('Admin') → FALSE
  ├─ Retourner 403 Forbidden
  └─ Ne pas retourner les données
```

---

## 🔄 Cycle de requête complet

### Frontend initiates (useStats.js)

```javascript
1. Component mounts
   ↓
2. useAuth() → get user
   ↓
3. Check: isAdmin = user?.roles?.includes('Admin')
   ↓
4. If isAdmin = true:
      axiosInstance.get('/stats/global')
   ↓
5. Axios interceptor adds:
      Authorization: Bearer TOKEN
      Accept: application/json
   ↓
6. Send HTTP GET to backend
   ↓
7. Receive response:
      Status: 200 ✅
      Body: { data: {...} }
   ↓
8. Parse response
   ↓
9. Normalize data
   ↓
10. Update state with setData()
    ↓
11. React Query cache updates
    ↓
12. Component re-renders
    ↓
13. DashboardStats shows data ✅
```

### Backend processes (StatsController.php)

```php
1. Route middleware checks:
      auth:sanctum (is logged in?)
      ↓ YES
2. Controller action:
      globalStats(Request $request)
3. Check permission:
      auth()->user()->hasRole('Admin')
      ↓ YES
4. Database queries:
      SELECT COUNT(*) FROM reports
      SELECT status, COUNT(*) GROUP BY status
      SELECT * FROM categories
      etc...
5. Process data:
      Calculate percentages
      Map statuses to French
      Format response
6. Return response:
      Status: 200 OK
      Body: { success: true, data: {...} }
7. Send back to frontend ✅
```

---

## 🚨 Erreur flow

### Quand l'endpoint n'existe pas (AVANT)

```
Frontend:
  GET /api/stats/global → Send

Backend Route Handler:
  Router looks for: GET /api/stats/global
  ❌ NOT FOUND in routes/api.php
  
Error Handler:
  Return 404 Not Found
  Body: { message: "Route not defined" }

Frontend Error Handler:
  Catch error (status 404)
  Log error
  Continue (no crash)
  
UI Result:
  Stats card: "N/A"
  Graphs: Empty
  Admin confused: "Why is it empty?"
```

---

## ✅ Success flow

### Quand l'endpoint existe (APRÈS)

```
Frontend:
  GET /api/stats/global → Send

Backend Route Handler:
  Router finds: GET /api/stats/global
  ✅ FOUND in routes/api.php
  
Controller Execution:
  StatsController@globalStats()
  ├─ Permission check: ✅ OK
  ├─ Database queries: ✅ OK
  ├─ Data processing: ✅ OK
  └─ Return 200 + data: ✅ OK

Frontend Response Handler:
  Catch response (status 200)
  Parse JSON
  Validate data
  Update state
  
UI Result:
  Stats card: "5 signalements"
  Graphs: Filled with data
  Admin happy: "It works!" 🎉
```

---

## 🎯 Decision Tree

```
                    Admin opens Dashboard
                            ↓
                  Frontend tries to fetch stats
                            ↓
                ┌───────────────────────────────┐
                │                               │
          Is user Admin?                        │
           /        \                           │
          YES        NO                         │
          ↓          ↓                           │
      Fetch      Don't fetch                    │
        ↓          ↓                             │
        │      Stats = null                     │
        │      (Skip request)                   │
        │          ↓                             │
        │      Dashboard OK                     │
        │      (but without stats)              │
        │                                       │
    GET /stats/global ────────────────────────→ │
        ↓                                       │
  ┌─────────────────────────────────────────────┤
  │   Backend receives request                  │
  │          ↓                                  │
  │   Route exists? (AFTER fix)                 │
  │   /         \                               │
  │  YES         NO                             │
  │   ↓           ↓                             │
  │   │     404 Error                           │
  │   │     Return error                        │
  │   │     Frontend: Stats = empty             │
  │   │                                         │
  │   └─→ StatsController.php                   │
  │       ├─ Is user Admin?                     │
  │       │  /         \                        │
  │       │ YES         NO                      │
  │       │  ↓           ↓                      │
  │       │  │      403 Forbidden               │
  │       │  │                                  │
  │       │  ├─ Query database                  │
  │       │  ├─ Calculate stats                 │
  │       │  ├─ Format response                 │
  │       │  └─ Return 200 OK                   │
  │       │      + data                         │
  │       ↓                                     │
  │   Frontend receives data                    │
  │       ↓                                     │
  │   Cache with React Query                    │
  │       ↓                                     │
  │   Dashboard renders with stats ✅           │
  │                                             │
  └─────────────────────────────────────────────┘
```

---

**Créé par**: AI Architecture Team  
**Date**: 9 Décembre 2025  
**Clarté**: 100% visuelle
