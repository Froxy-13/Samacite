# Test de l'API - Affichage des Détails des Signalements

## Configuration
- Base URL : `http://192.168.1.17:8000/api`
- Authentification : Bearer Token (Laravel Sanctum)

## 1. Connexion et Récupération du Token

### Créer un utilisateur test
```bash
php artisan tinker
```

```php
$user = \App\Models\User::factory()->create([
    'email' => 'test@samacite.com',
    'password' => bcrypt('password123')
]);
$user->assignRole('Utilisateur');
echo "User ID: " . $user->id;
```

### Se connecter
```bash
POST http://192.168.1.17:8000/api/auth/login
Content-Type: application/json

{
    "email": "test@samacite.com",
    "password": "password123"
}
```

**Réponse attendue :**
```json
{
    "success": true,
    "message": "Connexion réussie",
    "data": {
        "user": { ... },
        "token": "1|xxxxxxxxxxxxxxxxxxxxx"
    }
}
```

**Copier le token** pour les requêtes suivantes.

---

## 2. Test : Lister Tous les Signalements

```bash
GET http://192.168.1.17:8000/api/reports
Authorization: Bearer {votre_token}
```

**Résultat attendu :** ✅ `200 OK` avec tous les signalements

```json
{
    "success": true,
    "message": "Signalements récupérés avec succès",
    "data": [
        {
            "id": 1,
            "title": "Nids de poule",
            "user_id": 1,
            ...
        },
        {
            "id": 2,
            "title": "Lampadaire cassé",
            "user_id": 5,
            ...
        }
    ]
}
```

---

## 3. Test : Afficher les Détails d'un Signalement (Votre Propre Signalement)

Supposons que vous avez créé le signalement avec `id=1` :

```bash
GET http://192.168.1.17:8000/api/reports/1
Authorization: Bearer {votre_token}
```

**Résultat attendu :** ✅ `200 OK`

```json
{
    "success": true,
    "message": "Signalement récupéré avec succès",
    "data": {
        "id": 1,
        "title": "Nids de poule",
        "description": "Route endommagée",
        "status": "En attente",
        "user_id": 10,
        "user": {
            "id": 10,
            "nom": "Test",
            "email": "test@samacite.com"
        },
        ...
    }
}
```

---

## 4. Test : Afficher les Détails d'un Signalement d'un Autre Utilisateur

Supposons qu'un autre utilisateur (id=5) a créé le signalement avec `id=2` :

```bash
GET http://192.168.1.17:8000/api/reports/2
Authorization: Bearer {votre_token}
```

**Résultat attendu :** ✅ `200 OK` (AVANT : 403 Forbidden)

```json
{
    "success": true,
    "message": "Signalement récupéré avec succès",
    "data": {
        "id": 2,
        "title": "Lampadaire cassé",
        "user_id": 5,
        "user": {
            "id": 5,
            "nom": "Autre Utilisateur"
        },
        ...
    }
}
```

---

## 5. Test : Tenter de Supprimer le Signalement d'un Autre Utilisateur

```bash
DELETE http://192.168.1.17:8000/api/reports/2
Authorization: Bearer {votre_token}
```

**Résultat attendu :** ❌ `403 Forbidden`

```json
{
    "success": false,
    "message": "This action is unauthorized."
}
```

---

## 6. Test : Supprimer Votre Propre Signalement

```bash
DELETE http://192.168.1.17:8000/api/reports/1
Authorization: Bearer {votre_token}
```

**Résultat attendu :** ✅ `200 OK`

```json
{
    "success": true,
    "message": "Signalement supprimé avec succès"
}
```

---

## 7. Test : Signalement Inexistant

```bash
GET http://192.168.1.17:8000/api/reports/99999
Authorization: Bearer {votre_token}
```

**Résultat attendu :** ❌ `404 Not Found`

```json
{
    "success": false,
    "message": "Signalement non trouvé"
}
```

---

## 8. Test : Sans Authentification

```bash
GET http://192.168.1.17:8000/api/reports/1
# (Sans header Authorization)
```

**Résultat attendu :** ❌ `401 Unauthorized`

```json
{
    "message": "Unauthenticated."
}
```

---

## Résumé des Résultats Attendus

| Endpoint | Méthode | Scénario | Avant Fix | Après Fix |
|----------|---------|----------|-----------|-----------|
| `/api/reports` | GET | Lister tous | ✅ 200 | ✅ 200 |
| `/api/reports/{id}` | GET | Voir son signalement | ✅ 200 | ✅ 200 |
| `/api/reports/{id}` | GET | Voir signalement d'autrui | ❌ 403 | ✅ **200** |
| `/api/reports/{id}` | DELETE | Supprimer son signalement | ✅ 200 | ✅ 200 |
| `/api/reports/{id}` | DELETE | Supprimer signalement d'autrui | ❌ 403 | ❌ 403 |
| `/api/reports/99999` | GET | Signalement inexistant | ❌ 404 | ❌ 404 |
| `/api/reports/{id}` | GET | Sans token | ❌ 401 | ❌ 401 |

---

## Utilisation avec cURL (Windows PowerShell)

### 1. Connexion
```powershell
$loginResponse = Invoke-RestMethod -Uri "http://192.168.1.17:8000/api/auth/login" `
    -Method Post `
    -ContentType "application/json" `
    -Body '{"email":"test@samacite.com","password":"password123"}'

$token = $loginResponse.data.token
Write-Host "Token: $token"
```

### 2. Lister les signalements
```powershell
$headers = @{ "Authorization" = "Bearer $token" }
$reports = Invoke-RestMethod -Uri "http://192.168.1.17:8000/api/reports" `
    -Headers $headers

$reports.data | Format-Table id, title, user_id
```

### 3. Voir détails d'un signalement
```powershell
$report = Invoke-RestMethod -Uri "http://192.168.1.17:8000/api/reports/1" `
    -Headers $headers

$report.data | ConvertTo-Json
```

### 4. Tenter de supprimer le signalement d'un autre (doit échouer)
```powershell
try {
    Invoke-RestMethod -Uri "http://192.168.1.17:8000/api/reports/2" `
        -Method Delete `
        -Headers $headers
} catch {
    Write-Host "Erreur attendue: $($_.Exception.Message)"
}
```

---

## Utilisation avec Postman

### Collection Postman
Importer la collection `SamaCite-Phase1-Auth.postman_collection.json` et ajouter :

**Nouvelle Request : Get Report Details**
- **Method** : `GET`
- **URL** : `{{base_url}}/reports/1`
- **Headers** : `Authorization: Bearer {{token}}`

**Tests à ajouter :**
```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Response contains report data", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.success).to.eql(true);
    pm.expect(jsonData.data).to.have.property('id');
    pm.expect(jsonData.data).to.have.property('title');
    pm.expect(jsonData.data).to.have.property('user_id');
});
```

---

## ✅ Checklist de Validation

- [ ] Connexion réussie et token récupéré
- [ ] Liste complète des signalements visible
- [ ] Détails de son propre signalement accessibles
- [ ] ✅ **Détails du signalement d'un autre utilisateur accessibles (FIX)**
- [ ] Suppression de son propre signalement réussie
- [ ] Suppression du signalement d'un autre utilisateur bloquée (403)
- [ ] Signalement inexistant retourne 404
- [ ] Requête sans token retourne 401

---

**Date** : 25 novembre 2025  
**Version API** : 1.0  
**Backend** : Laravel 11 + Sanctum
