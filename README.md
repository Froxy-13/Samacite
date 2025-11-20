# 🌍 SamaCité - Plateforme de Signalement Urbain

> Une plateforme (mobile + backend + interface web) permettant aux citoyens de signaler des problèmes urbains, aux autorités de suivre les signalements, et de gérer leur traitement.

---

## 🎯 Vue d'Ensemble

SamaCité est divisée en 3 modules:

| Module | Stack | Statut |
|--------|-------|--------|
| 📱 **Mobile** | Flutter + Dio | ⏳ En attente |
| 🌐 **Frontend Admin** | React + Axios | ⏳ En attente |
| 🔧 **Backend** | Laravel 12 + Sanctum + Spatie | ✅ Phase 1 |

---

## 📚 Documentation Backend

### 🎯 Commencez Ici
- 📖 [**DOCUMENTATION_INDEX.md**](.github/DOCUMENTATION_INDEX.md) - Index complet de la documentation

### Phase 1 - Authentification ✅
- 📄 [**PHASE1_README.md**](.github/PHASE1_README.md) - Guide rapide
- 📊 [**PHASE1_REPORT.md**](.github/PHASE1_REPORT.md) - Rapport détaillé
- 📈 [**PHASE1_SUMMARY.md**](.github/PHASE1_SUMMARY.md) - Résumé d'exécution

### Outils & Guides
- 📮 [**POSTMAN_GUIDE.md**](.github/POSTMAN_GUIDE.md) - Guide API Postman
- 🛣️ [**BACKEND_ROADMAP.md**](.github/BACKEND_ROADMAP.md) - Roadmap 8 phases

### Spécifications
- 📋 [**spec.instructions.md**](.github/instructions/spec.instructions.md) - Spécifications complètes

---

## 🚀 Démarrage Rapide

### 1️⃣ Prérequis
- PHP 8.2+
- Composer
- Node.js (pour Vite)
- SQLite

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
```

### 3️⃣ Tester l'API

```bash
# Exécuter les tests
php artisan test tests/Feature/Auth/

# Ou avec Postman
# Importer: SamaCite-Phase1-Auth.postman_collection.json
```

---

## 📊 Statut des Phases

| Phase | Statut | Tests | Coverage |
|-------|--------|-------|----------|
| 1️⃣ **Auth** | ✅ Complétée | 30 ✅ | 100% |
| 2️⃣ **Reports** | ⏳ À faire | - | - |
| 3️⃣ **Categories** | ⏳ À faire | - | - |
| 4️⃣ **Comments** | ⏳ À faire | - | - |
| 5️⃣ **Notifications** | ⏳ À faire | - | - |
| 6️⃣ **Cache** | ⏳ À faire | - | - |
| 7️⃣ **Jobs** | ⏳ À faire | - | - |
| 8️⃣ **Analytics** | ⏳ À faire | - | - |

---

## 🏗️ Architecture

### Backend Laravel
- ✅ **Sanctum** - Authentification API (tokens Bearer)
- ✅ **Spatie Permission** - Gestion des rôles et permissions
- ✅ **Repository Pattern** - Architecture propre
- ✅ **Service Layer** - Logique métier séparée
- 🔲 **Cache Redis** - Performance optimisée
- 🔲 **Jobs Asynchrones** - Traitement en arrière-plan

### Frontend React
- 🔲 **Axios** - Requêtes HTTP
- 🔲 **React Query** - Gestion du cache
- 🔲 **Atomic Design** - Structure des composants
- 🔲 **Context API** - État global

### Mobile Flutter
- 🔲 **Dio** - Requêtes HTTP
- 🔲 **Riverpod** - Gestion d'état
- 🔲 **Firebase** - Notifications push

---

## 🔐 Phase 1 - Authentification ✅

### Endpoints
```
POST   /api/auth/register  → Inscription
POST   /api/auth/login     → Connexion
GET    /api/auth/me        → Utilisateur courant (protégé)
POST   /api/auth/logout    → Déconnexion (protégé)
```

### Rôles & Permissions

**Admin**
- 20 permissions complètes
- Accès dashboard
- Gestion utilisateurs
- Gestion signalements
- Accès statistiques

**Utilisateur**
- Créer signalements
- Voir ses signalements
- Commenter
- Uploader des fichiers

### Tests
```
✅ 30 tests passés
✅ 77 assertions
✅ 100% réussite
✅ 10.5 secondes
```

---

## 📱 Utilisateurs de Test

```
Admin:
  Email: admin@example.com
  Téléphone: +221701111111
  Password: password123

Utilisateur:
  Email: user@example.com
  Téléphone: +221702222222
  Password: password123
```

---

## 🛠️ Commandes Utiles

```bash
# Développement
php artisan serve                    # Démarrer le serveur
npm run dev                         # Vite (frontend)
php artisan pail                    # Voir les logs

# Tests
php artisan test                    # Tous les tests
php artisan test tests/Feature/Auth/ # Tests d'auth
php artisan test --coverage         # Avec coverage

# Base de Données
php artisan migrate                 # Créer les tables
php artisan migrate:fresh           # Réinitialiser
php artisan db:seed                 # Seeder les données

# Laravel
php artisan tinker                  # Interactif
php artisan make:model Name         # Créer un modèle
php artisan make:migration name     # Créer une migration
php artisan make:controller Name    # Créer un contrôleur
```

---

## 📂 Structure du Projet

```
samacite/
├── .github/
│   ├── DOCUMENTATION_INDEX.md
│   ├── BACKEND_ROADMAP.md
│   ├── PHASE1_README.md
│   ├── PHASE1_REPORT.md
│   ├── POSTMAN_GUIDE.md
│   └── instructions/
│       └── spec.instructions.md
│
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── BaseController.php
│   │   │   └── Auth/
│   │   └── Responses/
│   │       └── BaseResponse.php
│   ├── Models/
│   ├── Services/
│   └── Repositories/
│
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
│
├── routes/
│   └── api.php
│
├── tests/
│   ├── Feature/
│   │   └── Auth/
│   └── Unit/
│
├── SamaCite-Phase1-Auth.postman_collection.json
├── .env
└── README.md
```

---

## 🎓 Conventions Développement

- ✅ **TDD** - Écrire les tests avant le code
- ✅ **Repository Pattern** - Abstraction des données
- ✅ **Service Layer** - Logique métier séparée
- ✅ **BaseController & BaseResponse** - Standardisation
- ✅ **Eager Loading** - Éviter N+1 queries
- ✅ **Permissions** - Spatie pour contrôle d'accès

---

## 🚀 Prochaines Étapes

### Phase 2 - Gestion des Signalements
1. Créer migrations pour `reports` et `report_images`
2. Écrire tests CRUD
3. Implémenter ReportController
4. Ajouter routes API

Voir: [BACKEND_ROADMAP.md](.github/BACKEND_ROADMAP.md)

---

## 📞 Support

### Pour Commencer
- 📖 Lire [DOCUMENTATION_INDEX.md](.github/DOCUMENTATION_INDEX.md)
- 🧪 Exécuter les tests: `php artisan test`
- 📮 Importer Postman: `SamaCite-Phase1-Auth.postman_collection.json`

### Problèmes
- ❓ Voir [PHASE1_REPORT.md](.github/PHASE1_REPORT.md)
- ❓ Voir [POSTMAN_GUIDE.md](.github/POSTMAN_GUIDE.md)
- ❓ Lire [spec.instructions.md](.github/instructions/spec.instructions.md)

---

## 📜 License

Propriété de SamaCité - Tous droits réservés

---

## 👥 Équipe

- **Backend**: Laravel Developer
- **Frontend**: React Developer
- **Mobile**: Flutter Developer
- **DevOps**: DevOps Engineer

---

## 📅 Timeline

| Date | Milestone |
|------|-----------|
| 20 Nov 2025 | ✅ Phase 1 Complétée |
| - | ⏳ Phase 2 (Reports) |
| - | ⏳ Phase 3-8 |
| - | ⏳ Frontend React |
| - | ⏳ Mobile Flutter |
| - | ⏳ Production |

---

**Version**: 1.0  
**Backend Status**: ✅ Phase 1 Complete  
**Last Updated**: 20 Novembre 2025
