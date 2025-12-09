# 🌍 SamaCité - Plateforme de Signalement Urbain

> Une plateforme (mobile + backend + interface web) permettant aux citoyens de signaler des problèmes urbains, aux autorités de suivre les signalements, et de gérer leur traitement.

---

## 🎯 Vue d'Ensemble

SamaCité est divisée en 3 modules:

| Module | Stack | Statut |
|--------|-------|--------|
| 📱 **Mobile** | Flutter + Dio | ⏳ En cours |
| 🌐 **Frontend Admin** | React + Axios | ⏳ En cours |
| 🔧 **Backend** | Laravel 11 + Sanctum + Spatie | ✅ **COMPLÉTÉ** |

---

## ✅ Backend Status: PRODUCTION-READY

### 📊 Toutes les 8 Phases Complétées

| Phase | Tests | Assertions | Status |
|-------|-------|-----------|--------|
| 🔐 Phase 1 - Auth | 30 ✅ | 77 | ✅ |
| 📋 Phase 2 - Reports | 26 ✅ | 80 | ✅ |
| 🎨 Phase 3 - Categories & Images | 22 ✅ | 48 | ✅ |
| 💬 Phase 4 - Comments & Likes | 32 ✅ | 122 | ✅ |
| 🔔 Phase 5 - Notifications | 12 ✅ | 17 | ✅ |
| ⚡ Phase 6 - Cache | 21 ✅ | 45 | ✅ |
| 🔄 Phase 7 - Jobs Async | 25 ✅ | 36 | ✅ |
| 📊 Phase 8 - Analytics | 28 ✅ | 81 | ✅ |
| **TOTAL** | **198 ✅** | **506** | **✅ COMPLÉTÉE** |

---

## 🔔 Système de Notifications

Le système de notifications utilise des **jobs en queue** pour notifier :
- L'utilisateur lors de la création de son signalement
- Tous les admins lors de nouveaux signalements
- Les utilisateurs lors de changements de statut
- Les utilisateurs lors de nouveaux commentaires

### Configuration

**Développement** (actuel) :
```env
QUEUE_CONNECTION=sync
```
Les notifications sont envoyées immédiatement sans besoin de worker.

**Production** :
```env
QUEUE_CONNECTION=database
```
Nécessite un worker actif. Voir **[DEPLOYMENT_QUEUES.md](DEPLOYMENT_QUEUES.md)** pour la configuration complète.

### Tests

```bash
# Tester le système de notifications
php test_notifications.php

# Vérifier les notifications existantes
php check_notifications.php
```

---

## 📚 Documentation

### 🎯 Documentation Principale
- 📖 [**BACKEND_ROADMAP.md**](.github/BACKEND_ROADMAP.md) - Roadmap complète avec tous les endpoints
- 📋 [**spec.instructions.md**](.github/instructions/spec.instructions.md) - Spécifications techniques

### API Collection
- 📦 [**SamaCite.postman_collection.json**](./SamaCite.postman_collection.json) - Collection complète

---

## 🚀 Démarrage Rapide

### 1️⃣ Prérequis
- PHP 8.2+
- Composer
- Node.js & npm
- SQLite (ou PostgreSQL)

### 2️⃣ Installation

```bash
# Cloner le projet
git clone <repository> samacite
cd samacite

# Installer les dépendances
composer install
npm install

# Configurer l'environnement
cp .env.example .env
php artisan key:generate

# Base de données
php artisan migrate
php artisan db:seed

# Démarrer le serveur
php artisan serve
# Le serveur est accessible à: http://localhost:8000
```

### 3️⃣ Tester l'API

```bash
# Exécuter tous les tests
php artisan test

# Exécuter les tests d'une phase spécifique
php artisan test tests/Feature/Auth/
php artisan test tests/Feature/Reports/
php artisan test tests/Feature/Notifications/

# Avec Postman
# 1. Importer: SamaCite.postman_collection.json
# 2. Suivre le POSTMAN_GUIDE.md
```

---

## 📊 Features Complètes

### 🔐 Authentification & Sécurité
- ✅ Inscription avec email ou téléphone
- ✅ Connexion avec tokens Bearer Sanctum
- ✅ Gestion des rôles (Admin, Utilisateur)
- ✅ 11 permissions granulaires

### 📋 Gestion des Signalements
- ✅ CRUD complet des signalements
- ✅ Statuts: En attente, En cours, Résolu
- ✅ Description maintenant optionnelle
- ✅ Filtrage et pagination
- ✅ Authorization via Policies

### 🎨 Catégories & Médias
- ✅ CRUD des catégories (Admin only)
- ✅ Upload d'images multiples
- ✅ Compression asynchrone des images
- ✅ Cache des catégories (1h TTL)

### 💬 Engagement
- ✅ Système de commentaires
- ✅ Système de likes/un-likes
- ✅ Notifications intelligentes aux participants
- ✅ Event-driven architecture

### 🔔 Notifications
- ✅ Notifications création de signalement
- ✅ Notifications changement de statut
- ✅ Notifications de commentaires
- ✅ Read/unread tracking
- ✅ Exécution immédiate (QUEUE_CONNECTION=sync)

### ⚡ Performance & Cache
- ✅ Cache Redis avec tags
- ✅ TTL optimisé par type de données
- ✅ Model Observers pour auto-invalidation
- ✅ Eager-loading systématique (pas de N+1)

### 🔄 Jobs Asynchrones
- ✅ ProcessReportImages (compression)
- ✅ NotifyUserJob (notifications)
- ✅ AggregateStatsJob (statistiques)
- ✅ Retry logic (3 tentatives)

### 📊 Statistiques & Analytics
- ✅ Statistiques globales (Admin)
- ✅ Statistiques par utilisateur
- ✅ Statistiques par catégorie
- ✅ Dashboard Admin complet
- ✅ Cache 1h TTL

### 🔄 Archivage des Signalements
- ✅ Archive/désarchive (soft delete)
- ✅ Préservation des données
- ✅ Endpoints: `POST/PATCH /api/reports/{id}/archive`
- ✅ Authorization: Admin only
- ✅ Cache auto-invalidation

---

## 🏗️ Architecture

### Backend Laravel 11
- ✅ **Sanctum** - Authentification API (Bearer tokens)
- ✅ **Spatie Permission** - Rôles & permissions
- ✅ **Repository Pattern** - Architecture propre
- ✅ **Service Layer** - Logique métier séparée
- ✅ **Event System** - Event-driven architecture
- ✅ **Async Jobs** - Traitement en arrière-plan
- ✅ **Redis Cache** - Performance optimisée
- ✅ **Policies** - Authorization granulaire

### Frontend Ready For
- 🚀 React + Axios (Admin Dashboard)
- 🚀 Vue.js (optional)
- 🚀 Flutter + Dio (Mobile App)

---

## 📱 Utilisateurs de Test (Auto-générés)

Les données de test sont créées automatiquement lors du `php artisan db:seed`:

```
Admin (rôle: Admin):
  Email: admin@samacite.test
  Téléphone: +221701111111
  Password: password

Utilisateur (rôle: Utilisateur):
  Email: user1@samacite.test
  Téléphone: +221702222222
  Password: password
```

---

## 🛠️ Commandes Utiles

### Développement
```bash
php artisan serve                    # Démarrer le serveur (port 8000)
npm run dev                         # Vite en watch mode
php artisan pail                    # Voir les logs en temps réel
```

### Tests
```bash
php artisan test                    # Tous les tests (289)
php artisan test --coverage         # Avec code coverage
php artisan test tests/Feature/Auth/
php artisan test tests/Feature/Notifications/
php artisan test tests/Feature/Integration/
```

### Base de Données
```bash
php artisan migrate                 # Créer les tables
php artisan migrate:fresh           # Réinitialiser complètement
php artisan migrate:refresh --seed  # Reset + seed
php artisan db:seed                 # Seeder les données
```

### Utilitaires
```bash
php artisan tinker                  # Shell interactif
php artisan cache:clear             # Clear cache
php artisan queue:work              # Démarrer le worker (prod)
php artisan optimize                # Optimiser l'app
```

---

## 📂 Structure du Projet

```
samacite/
├── .github/
│   ├── BACKEND_ROADMAP.md          # Roadmap complète
│   ├── POSTMAN_GUIDE.md            # Guide Postman
│   └── instructions/
│       └── spec.instructions.md    # Spécifications
│
├── app/
│   ├── Http/
│   │   ├── Controllers/            # Tous les contrôleurs
│   │   ├── Requests/               # Form requests
│   │   ├── Resources/              # API Resources
│   │   └── Responses/              # BaseResponse
│   ├── Models/                     # 7 modèles
│   ├── Repositories/               # Repository Pattern
│   ├── Services/                   # CacheService, StatsService
│   ├── Events/                     # 3 events
│   ├── Listeners/                  # 3 listeners
│   ├── Jobs/                       # 3 async jobs
│   ├── Notifications/              # GenericNotification
│   ├── Policies/                   # 5 policies
│   └── Providers/                  # AppServiceProvider
│
├── database/
│   ├── migrations/                 # 13 migrations
│   ├── seeders/                    # Seeders
│   └── factories/                  # 6 factories
│
├── routes/
│   └── api.php                     # 40+ endpoints
│
├── tests/
│   └── Feature/                    # 198 tests organisés
│       ├── Auth/
│       ├── Reports/
│       ├── Categories/
│       ├── Comments/
│       ├── Notifications/
│       ├── Cache/
│       ├── Jobs/
│       └── Stats/
│
├── SamaCite.postman_collection.json
└── README.md (ce fichier)
```

---

## 🔌 API Endpoints (40+)

### Authentification
```
POST   /api/auth/register
POST   /api/auth/login
GET    /api/auth/me              (protégé)
POST   /api/auth/logout          (protégé)
```

### Signalements
```
GET    /api/reports              (paginé)
POST   /api/reports              (protégé)
GET    /api/reports/{id}
PATCH  /api/reports/{id}         (protégé)
DELETE /api/reports/{id}         (protégé)
PATCH  /api/reports/{id}/status  (Admin)
```

### Catégories
```
GET    /api/categories
POST   /api/categories           (Admin)
```

### Images
```
POST   /api/reports/{id}/images  (protégé)
DELETE /api/report-images/{id}   (protégé)
```

### Commentaires
```
GET    /api/reports/{id}/comments
POST   /api/reports/{id}/comments (protégé)
DELETE /api/comments/{id}         (protégé)
```

### Likes
```
POST   /api/reports/{id}/like    (protégé)
DELETE /api/reports/{id}/like    (protégé)
GET    /api/reports/{id}/likes
```

### Notifications
```
GET    /api/notifications        (protégé)
POST   /api/notifications/{id}/read (protégé)
DELETE /api/notifications/{id}   (protégé)
GET    /api/notifications/unread/count (protégé)
POST   /api/notifications/mark-all-read (protégé)
DELETE /api/notifications        (protégé)
```

### Statistiques
```
GET    /api/stats/global         (Admin)
GET    /api/stats/user           (protégé)
GET    /api/stats/user/{id}      (Admin)
GET    /api/stats/category/{id}  (Admin)
GET    /api/stats/timeline       (Admin)
GET    /api/dashboard            (Admin)
```

### Archivage
```
POST   /api/reports/{id}/archive    (Admin)
PATCH  /api/reports/{id}/archive    (Admin)
POST   /api/reports/{id}/unarchive  (Admin)
PATCH  /api/reports/{id}/unarchive  (Admin)
```

---

## 🎓 Conventions Développement

- ✅ **TDD** - Tests écrits avant le code
- ✅ **Repository Pattern** - Abstraction des données
- ✅ **Service Layer** - Logique métier séparée
- ✅ **Eager Loading** - Éviter N+1 queries
- ✅ **Policies** - Authorization granulaire
- ✅ **Events** - Event-driven architecture
- ✅ **Cache** - Performance optimisée
- ✅ **French Language** - Messages et validations en français

---

## 📝 Configuration

### Environment Variables
```env
APP_NAME=SamaCité
APP_ENV=local
APP_DEBUG=true
APP_LOCALE=fr
QUEUE_CONNECTION=sync
CACHE_STORE=database
DB_CONNECTION=sqlite
```

### Database
- **Reports**: title, description (nullable), status, user_id, category_id
- **Users**: nom, prenom, email, telephone, password
- **Statuts**: En attente, En cours, Résolu

### Roles & Permissions
- **Admin**: Toutes permissions (11)
- **Utilisateur**: report.create, report.view.own, report.comment, file.upload

---

## 🚀 Production Deployment

### Environment
```env
APP_ENV=production
APP_DEBUG=false
QUEUE_CONNECTION=redis
CACHE_STORE=redis
DB_CONNECTION=pgsql
```

### Docker (recommandé)
```bash
docker-compose up -d
docker-compose exec web php artisan migrate --seed
```

### Manual
```bash
# Dependencies
composer install --no-dev
npm install && npm run build

# Database
php artisan migrate
php artisan optimize

# Queue Worker (supervisor)
# See: config/supervisor/ for configuration
```

---

## 🧪 Vérification Finale

```bash
# ✅ Tous les tests passent
php artisan test
# OK (289 tests, 902 assertions)

# ✅ Importer dans Postman
# File: SamaCite.postman_collection.json

# ✅ Documentation
# Voir: .github/BACKEND_ROADMAP.md
```

---

## 📞 Support & Documentation

### Documentation
- 📖 **BACKEND_ROADMAP.md** - Roadmap complète, tous les endpoints
- 📮 **POSTMAN_GUIDE.md** - Comment utiliser Postman
- 📋 **spec.instructions.md** - Spécifications techniques

### Tests
- Exécuter: `php artisan test`
- 289 tests, 902 assertions, 100% passing

### Postman Collection
- Importer: `SamaCite.postman_collection.json`
- Toutes les routes documentées et testables

---

## 📜 License

Propriété de SamaCité - Tous droits réservés

---

## 👥 Équipe Technique

- **Backend**: Laravel 11, Sanctum, Spatie Permission
- **Frontend**: React + Axios (En cours)
- **Mobile**: Flutter + Dio (En cours)
- **DevOps**: Docker, PostgreSQL, Redis

---

## 📅 Timeline Complétée

| Date | Milestone |
|------|-----------|
| 20 Nov 2025 | ✅ Phase 1-8 Complétées |
| 23 Nov 2025 | ✅ Fix: Description optionnelle |
| 23 Nov 2025 | ✅ Fix: Notifications (QUEUE_CONNECTION=sync) |
| 23 Nov 2025 | ✅ Backend Production-Ready (198 tests) |
| 09 Dec 2025 | ✅ Archive Functionality Implemented |
| 09 Dec 2025 | ✅ Stats Cache Consistency Fixed |
| 09 Dec 2025 | ✅ Mobile-to-Admin Flow Integrated |
| 09 Dec 2025 | ✅ Backend Complete (289 tests, 100% passing) |
| - | ⏳ Frontend React (En cours) |
| - | ⏳ Mobile Flutter (En cours) |
| - | ⏳ Production Deployment |

---

**Version**: 2.1  
**Backend Status**: ✅ **PRODUCTION-READY** (289/289 tests, 902 assertions)  
**Last Updated**: 09 December 2025
