# 🔐 Phase 1 - Authentification & Autorisation

## ✅ Statut: COMPLÉTÉE

### 📊 Résumé des Tests

- **Total de tests**: 30
- **Assertion**: 77
- **Statut**: ✅ TOUS LES TESTS PASSENT
- **Couverture**: Register, Login (email/phone), Permissions (Admin & User)

---

## 📁 Fichiers Créés

### Tests (`tests/Feature/Auth/`)

1. **RegisterTest.php** - 5 tests
   - ✅ Enregistrement avec email
   - ✅ Validation des données
   - ✅ Unicité de l'email
   - ✅ Unicité du téléphone
   - ✅ Confirmation du mot de passe

2. **LoginTest.php** - 5 tests
   - ✅ Connexion par email
   - ✅ Connexion par téléphone
   - ✅ Identifiants invalides
   - ✅ Utilisateur inexistant
   - ✅ Génération du token

3. **PermissionTest.php** - 9 tests
   - ✅ Permissions Admin (dashboard, users, reports, stats, etc.)
   - Toutes les permissions Admin vérifiées

4. **UserPermissionTest.php** - 11 tests
   - ✅ Permissions Utilisateur (report.create, report.view.own, report.comment, file.upload)
   - ✅ Restrictions appropriées (pas de dashboard, pas de stats, etc.)

### Contrôleurs (`app/Http/Controllers/`)

1. **BaseController.php**
   - Classe de base pour tous les contrôleurs
   - Méthodes helper pour réponses standardisées

2. **Auth/AuthController.php**
   - `register()` - Inscription avec email/téléphone
   - `login()` - Connexion et génération token Sanctum
   - `me()` - Récupérer l'utilisateur courant avec rôles/permissions
   - `logout()` - Déconnexion

### Réponses (`app/Http/Responses/`)

1. **BaseResponse.php**
   - Classe statique pour formater les réponses JSON
   - Méthodes: success, error, validationError, unauthorized, forbidden, notFound

### Seeders (`database/Seeders/`)

1. **RolesAndPermissionsSeeder.php**
   - Création de tous les rôles (Admin, Utilisateur)
   - Création de toutes les permissions (20 permissions)
   - Attribution des permissions aux rôles

2. **DatabaseSeeder.php** (mise à jour)
   - Appelle RolesAndPermissionsSeeder
   - Crée 2 utilisateurs de test (Admin + User)

### Traits (`tests/Traits/`)

1. **WithRolesAndPermissions.php**
   - Trait pour automatiser le seeding dans les tests
   - Évite les erreurs "Role does not exist"

### Routes (`routes/api.php`)

```php
POST   /api/auth/register  - Inscription
POST   /api/auth/login     - Connexion
GET    /api/auth/me        - Utilisateur courant (protégé)
POST   /api/auth/logout    - Déconnexion (protégé)
```

### Collection Postman

**Fichier**: `SamaCite-Phase1-Auth.postman_collection.json`

Contient 6 requêtes:
- ✅ Register - Email
- ✅ Register - Validation Error
- ✅ Login - Email
- ✅ Login - Phone
- ✅ Login - Invalid Credentials
- ✅ Get Current User
- ✅ Logout

Variables d'environnement:
- `{{BASE_URL}}` - http://localhost:8000 (par défaut)
- `{{AUTH_TOKEN}}` - Token généré automatiquement après login

---

## 🚀 Comment Utiliser

### 1. Exécuter les Tests

```bash
# Tous les tests d'authentification
php artisan test tests/Feature/Auth/

# Test spécifique
php artisan test tests/Feature/Auth/RegisterTest.php

# Avec coverage
php artisan test tests/Feature/Auth/ --coverage
```

### 2. Démarrer le Serveur

```bash
php artisan serve
```

Le serveur sera disponible à: `http://localhost:8000`

### 3. Utiliser Postman

1. Importer `SamaCite-Phase1-Auth.postman_collection.json` dans Postman
2. Créer un nouvel environment avec:
   - `BASE_URL`: `http://localhost:8000`
3. Exécuter les requêtes dans l'ordre:
   - Register d'abord (créer un utilisateur)
   - Login ensuite (obtenir le token)
   - Autres requêtes avec le token

### 4. Seeder les Données

```bash
php artisan db:seed
```

Cela crée:
- Admin: email = `admin@example.com`, password = `password123`
- User: email = `user@example.com`, password = `password123`

---

## 📋 API Endpoints

### POST `/api/auth/register`

**Request:**
```json
{
  "nom": "Dupont",
  "prenom": "Jean",
  "email": "jean@example.com",
  "telephone": "+221701234567",
  "password": "Password123",
  "password_confirmation": "Password123"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "id": 1,
    "nom": "Dupont",
    "prenom": "Jean",
    "email": "jean@example.com",
    "telephone": "+221701234567"
  }
}
```

### POST `/api/auth/login`

**Request (Email):**
```json
{
  "email": "jean@example.com",
  "password": "Password123"
}
```

**Request (Phone):**
```json
{
  "phone": "+221701234567",
  "password": "Password123"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Logged in successfully",
  "data": {
    "user": {
      "id": 1,
      "nom": "Dupont",
      "prenom": "Jean",
      "email": "jean@example.com",
      "telephone": "+221701234567"
    },
    "token": "1|A1B2C3D4E5F6G7H8I9J0K1L2M3N4O5P6Q7R8S9T0"
  }
}
```

### GET `/api/auth/me` (Authentifié)

**Header:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "id": 1,
    "nom": "Dupont",
    "prenom": "Jean",
    "email": "jean@example.com",
    "telephone": "+221701234567",
    "roles": ["Utilisateur"],
    "permissions": ["report.create", "report.view.own", "report.comment", "file.upload"]
  }
}
```

### POST `/api/auth/logout` (Authentifié)

**Header:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Logged out successfully",
  "data": null
}
```

---

## 🔐 Rôles et Permissions

### Rôle: Admin
- ✅ Toutes les permissions (20 permissions)
- Dashboard access
- User management
- Report management (all)
- Settings
- etc.

### Rôle: Utilisateur
- ✅ `report.create` - Créer un signalement
- ✅ `report.view.own` - Voir ses propres signalements
- ✅ `report.comment` - Commenter les signalements
- ✅ `file.upload` - Uploader des fichiers

---

## ✨ Points d'Amélioration Possibles

1. **Rate Limiting**: Ajouter un rate limiting sur login/register
2. **Email Verification**: Vérification d'email avant activation du compte
3. **Password Reset**: Endpoint pour réinitialiser le mot de passe
4. **Refresh Token**: Ajouter un refresh token pour meilleure sécurité
5. **Two-Factor Authentication**: 2FA optionnel

---

## 🛠️ Modifications de Fichiers Existants

1. **app/Models/User.php**
   - Ajout des traits: `HasApiTokens` (Sanctum) et `HasRoles` (Spatie)
   - Mise à jour de `$fillable` pour: nom, prenom, email, telephone

2. **database/factories/UserFactory.php**
   - Changement de 'name' à 'nom' et 'prenom'
   - Ajout du champ 'telephone'

3. **database/seeders/DatabaseSeeder.php**
   - Appel du RolesAndPermissionsSeeder
   - Création d'utilisateurs de test

4. **bootstrap/app.php**
   - Enregistrement des routes API

5. **.env**
   - Configuration SQLite (déjà faite en Phase 0)
   - Sanctum configuré automatiquement

---

## 📚 Structure du Code

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── BaseController.php
│   │   └── Auth/
│   │       └── AuthController.php
│   └── Responses/
│       └── BaseResponse.php
└── Models/
    └── User.php (modifié)

database/
├── factories/
│   └── UserFactory.php (modifié)
└── seeders/
    ├── RolesAndPermissionsSeeder.php
    └── DatabaseSeeder.php (modifié)

routes/
└── api.php

tests/
├── Feature/
│   └── Auth/
│       ├── RegisterTest.php
│       ├── LoginTest.php
│       ├── PermissionTest.php
│       └── UserPermissionTest.php
└── Traits/
    └── WithRolesAndPermissions.php
```

---

## 🧪 Exécution des Tests

```bash
$ php artisan test tests/Feature/Auth/

   PASS  Tests\Feature\Auth\LoginTest
   ✓ user can login with email
   ✓ user can login with telephone
   ✓ login fails with invalid credentials
   ✓ login fails with non existent user
   ✓ token is valid bearer token

   PASS  Tests\Feature\Auth\PermissionTest
   ✓ admin has dashboard access permission
   ✓ admin can view users
   ✓ admin can manage users
   ✓ admin has all report permissions
   ✓ admin can send notifications
   ✓ admin can view stats
   ✓ admin can manage settings
   ✓ admin can upload files
   ✓ admin can comment

   PASS  Tests\Feature\Auth\RegisterTest
   ✓ user can register with email
   ✓ register fails with invalid data
   ✓ email must be unique
   ✓ telephone must be unique
   ✓ password must be confirmed

   PASS  Tests\Feature\Auth\UserPermissionTest
   ✓ user can create reports
   ✓ user can view own reports
   ✓ user can comment
   ✓ user can upload files
   ✓ user cannot view any reports
   ✓ user cannot update reports
   ✓ user cannot delete reports
   ✓ user cannot change status
   ✓ user cannot access dashboard
   ✓ user cannot view stats
   ✓ user cannot manage settings

   Tests:    30 passed (77 assertions)
   Duration: 28.41s
```

---

## 💬 Feedback du Développeur

> À remplir par le développeur après vérification:

- [ ] Tests vérifiés et validés
- [ ] Code fonctionne correctement
- [ ] Collection Postman testée
- [ ] Observations/améliorations:
  - ...

---

**Créé**: 20 Novembre 2025
**Statut**: ✅ Complétée et prête pour Phase 2
