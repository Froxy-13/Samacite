# 📮 Postman Collection Guide - Phase 1 Authentication

## 🎯 Objectif

Ce guide vous montre comment utiliser la collection Postman `SamaCite-Phase1-Auth.postman_collection.json` pour tester les endpoints d'authentification.

---

## 🚀 Installation

### Prérequis
- Postman installé ([télécharger ici](https://www.postman.com/downloads/))
- Le serveur Laravel en cours d'exécution (`php artisan serve`)
- La base de données seedée (`php artisan db:seed`)

### Étapes d'Installation

1. **Télécharger la collection**
   - Fichier: `SamaCite-Phase1-Auth.postman_collection.json`
   - Localisation: Racine du projet

2. **Importer dans Postman**
   - Ouvrir Postman
   - Cliquer sur "Import" (haut-gauche)
   - Sélectionner le fichier JSON
   - Cliquer sur "Import"

3. **Créer un Environment (Optionnel)**
   - Postman crée automatiquement les variables globales
   - Vous pouvez modifier `BASE_URL` si le serveur n'est pas sur `http://localhost:8000`

---

## 📋 Variables d'Environnement

La collection utilise 2 variables:

| Variable | Valeur Par Défaut | Description |
|----------|------------------|-------------|
| `BASE_URL` | `http://localhost:8000` | URL du serveur |
| `AUTH_TOKEN` | *(vide)* | Token généré après login (sauvegardé automatiquement) |

### Modifier les Variables

1. Cliquer sur l'icône "Œil" (haut-droite)
2. Cliquer sur "Edit" à côté des variables
3. Modifier les valeurs
4. Cliquer sur "Save"

---

## 📝 Exécution des Requests

### 1️⃣ Register - Email (Créer un nouvel utilisateur)

**Objectif**: Inscrire un nouvel utilisateur

**Requête**:
```
POST http://localhost:8000/api/auth/register
```

**Body (JSON)**:
```json
{
  "nom": "Dupont",
  "prenom": "Jean",
  "email": "jean.dupont@example.com",
  "telephone": "+221701234567",
  "password": "Password123",
  "password_confirmation": "Password123"
}
```

**Réponse (201)**:
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "id": 3,
    "nom": "Dupont",
    "prenom": "Jean",
    "email": "jean.dupont@example.com",
    "telephone": "+221701234567"
  }
}
```

**Notes**:
- L'email et le téléphone doivent être uniques
- Le mot de passe doit être confirmé
- Le mot de passe minimum 8 caractères

---

### 2️⃣ Register - Validation Error (Tester la validation)

**Objectif**: Tester les erreurs de validation

**Requête**:
```
POST http://localhost:8000/api/auth/register
```

**Body (JSON)**:
```json
{
  "nom": "",
  "prenom": "",
  "email": "invalid-email",
  "telephone": "",
  "password": "123",
  "password_confirmation": "456"
}
```

**Réponse (422)**:
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "nom": ["The nom field is required."],
    "prenom": ["The prenom field is required."],
    "email": ["The email field must be a valid email address."],
    "telephone": ["The telephone field is required."],
    "password": ["The password field must be at least 8 characters.", "The password confirmation does not match."]
  }
}
```

---

### 3️⃣ Login - Email (Connexion par email)

**Objectif**: Se connecter avec email et mot de passe

**Requête**:
```
POST http://localhost:8000/api/auth/login
```

**Body (JSON)**:
```json
{
  "email": "admin@example.com",
  "password": "password123"
}
```

**Réponse (200)**:
```json
{
  "success": true,
  "message": "Logged in successfully",
  "data": {
    "user": {
      "id": 1,
      "nom": "Admin",
      "prenom": "Utilisateur",
      "email": "admin@example.com",
      "telephone": "+221701111111"
    },
    "token": "1|eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
  }
}
```

**Notes**:
- ✅ Le token est automatiquement sauvegardé dans `{{AUTH_TOKEN}}`
- ✅ Utilisez ce token pour les requêtes protégées
- Utilisateurs de test:
  - Email: `admin@example.com`, Password: `password123` (Admin)
  - Email: `user@example.com`, Password: `password123` (User)

---

### 4️⃣ Login - Phone (Connexion par téléphone)

**Objectif**: Se connecter avec téléphone et mot de passe

**Requête**:
```
POST http://localhost:8000/api/auth/login
```

**Body (JSON)**:
```json
{
  "phone": "+221701111111",
  "password": "password123"
}
```

**Réponse (200)**:
```json
{
  "success": true,
  "message": "Logged in successfully",
  "data": {
    "user": {
      "id": 1,
      "nom": "Admin",
      "prenom": "Utilisateur",
      "email": "admin@example.com",
      "telephone": "+221701111111"
    },
    "token": "1|eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
  }
}
```

**Notes**:
- Alternative à l'email pour la connexion
- Le token est automatiquement sauvegardé

---

### 5️⃣ Login - Invalid Credentials (Tester les erreurs)

**Objectif**: Tester une connexion échouée

**Requête**:
```
POST http://localhost:8000/api/auth/login
```

**Body (JSON)**:
```json
{
  "email": "admin@example.com",
  "password": "wrongpassword"
}
```

**Réponse (401)**:
```json
{
  "success": false,
  "message": "Invalid credentials"
}
```

**Notes**:
- Aucun token n'est généré
- Le token précédent reste valide

---

### 6️⃣ Get Current User (Récupérer l'utilisateur courant)

**Objectif**: Récupérer les données de l'utilisateur connecté

**Requête**:
```
GET http://localhost:8000/api/auth/me
```

**Headers**:
```
Authorization: Bearer {{AUTH_TOKEN}}
Accept: application/json
```

**Réponse (200)**:
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "id": 1,
    "nom": "Admin",
    "prenom": "Utilisateur",
    "email": "admin@example.com",
    "telephone": "+221701111111",
    "roles": ["Admin"],
    "permissions": [
      "dashboard.access",
      "user.view",
      "user.manage",
      "report.create",
      "report.view.own",
      "report.view.any",
      "report.update",
      "report.assign",
      "report.change_state",
      "report.delete",
      "report.comment",
      "notification.send",
      "stats.view",
      "settings.manage",
      "file.upload"
    ]
  }
}
```

**Notes**:
- ✅ Requête protégée (nécessite le token)
- Affiche tous les rôles et permissions de l'utilisateur
- Utile pour l'interface: vérifier les droits de l'utilisateur

---

### 7️⃣ Logout (Déconnexion)

**Objectif**: Se déconnecter

**Requête**:
```
POST http://localhost:8000/api/auth/logout
```

**Headers**:
```
Authorization: Bearer {{AUTH_TOKEN}}
Accept: application/json
```

**Réponse (200)**:
```json
{
  "success": true,
  "message": "Logged out successfully",
  "data": null
}
```

**Notes**:
- ✅ Requête protégée (nécessite le token)
- Le token est révoqué et ne peut plus être utilisé
- `{{AUTH_TOKEN}}` devient inutilisable après logout

---

## 🔄 Workflow Complet

### Scénario 1: Inscription → Login → Accès Utilisateur → Logout

1. **Register - Email**
   - Remplir les données d'un nouvel utilisateur
   - Envoyer la requête
   - Vérifier la réponse (201)

2. **Login - Email**
   - Utiliser l'email et mot de passe du nouvel utilisateur
   - Le token est automatiquement sauvegardé
   - Vérifier la réponse (200)

3. **Get Current User**
   - Le token est automatiquement inclus
   - Vérifier les rôles et permissions

4. **Logout**
   - Le token est révoqué
   - Les requêtes suivantes avec ce token échoueront

### Scénario 2: Connexion Administrateur → Gestion

1. **Login - Email** (avec admin@example.com)
   - Obtenir le token admin
   - Vérifier les permissions

2. **Get Current User**
   - Voir toutes les permissions admin
   - Préparer les requêtes de gestion

---

## 🛡️ Authentification Bearer Token

### Comment ça marche

1. **Login** → reçoit un token
2. **Header Authorization** → `Bearer {token}`
3. **Requête protégée** → serveur valide le token
4. **Réponse** → données utilisateur

### Dans Postman

- **Automatique**: Postman sauvegarde le token dans `{{AUTH_TOKEN}}`
- **Manuel**: Copier-coller le token dans les headers
- **Exemple Header**:
  ```
  Authorization: Bearer 1|eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
  ```

---

## 🧪 Tests Automatisés

Chaque requête a des **tests automatisés**:

### Examples

**Register - Email** teste:
- ✅ Status code est 201
- ✅ `success` est true
- ✅ Les données utilisateur sont correctes

**Login** teste:
- ✅ Status code est 200
- ✅ `success` est true
- ✅ Un token est présent
- ✅ Le token est sauvegardé dans `AUTH_TOKEN`

### Voir les Résultats des Tests

1. Envoyer une requête
2. Cliquer sur l'onglet "Tests"
3. Voir les résultats (vert = réussi, rouge = échoué)

---

## ❌ Dépannage

### Erreur: "404 Not Found"

**Cause**: URL incorrecte ou serveur non démarré

**Solution**:
1. Vérifier que le serveur fonctionne: `php artisan serve`
2. Vérifier `BASE_URL` dans Postman
3. Vérifier l'URL de la requête

### Erreur: "401 Unauthorized"

**Cause**: Token invalide ou expiré

**Solution**:
1. Re-login pour obtenir un nouveau token
2. Vérifier que le header Authorization est correct
3. Vérifier le format: `Bearer {token}` (pas d'oubli d'espace)

### Erreur: "422 Unprocessable Entity"

**Cause**: Validation échouée

**Solution**:
1. Vérifier les erreurs dans la réponse
2. Corriger les données d'entrée
3. Vérifier les règles de validation (email unique, téléphone unique, etc.)

### Erreur: "500 Internal Server Error"

**Cause**: Erreur serveur

**Solution**:
1. Vérifier les logs: `php artisan tail`
2. Vérifier que les migrations sont exécutées: `php artisan migrate`
3. Vérifier que le seeder est exécuté: `php artisan db:seed`

---

## 📚 Références

- [Documentation Postman](https://learning.postman.com/)
- [Laravel Sanctum Docs](https://laravel.com/docs/11.x/sanctum)
- [Spatie Permission Docs](https://spatie.be/docs/laravel-permission/v6)

---

**Créé**: 20 Novembre 2025
**Collection**: SamaCite-Phase1-Auth
**Version**: 1.0
