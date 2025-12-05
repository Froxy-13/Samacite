# 🚀 SamaCité - Backend AI Analysis

## Démarrage Ultra-Rapide

### Option 1 : Sans clé Gemini (test basique)
```powershell
.\serve.ps1
```
Le serveur démarre sur `http://172.17.144.1:8000`

### Option 2 : Avec AI complète (recommandé)
```powershell
# 1. Configurer la clé Gemini (1 minute)
.\setup_gemini.ps1

# 2. Démarrer le serveur
.\serve.ps1

# 3. Tester le backend AI
php test_ai_backend.php
```

---

## 📁 Fichiers Utiles

| Fichier | Description |
|---------|-------------|
| `serve.ps1` | Démarre le serveur Laravel immédiatement |
| `setup_gemini.ps1` | Configure GEMINI_API_KEY en 1 minute |
| `start_server.ps1` | Démarrage complet avec vérifications |
| `test_ai_backend.php` | Test automatique de l'endpoint AI |
| `QUICK_START.md` | Guide complet de démarrage |
| `AI_INSTALLATION_GUIDE.md` | Documentation technique AI |

---

## 🎯 Votre Configuration Actuelle

- **IP Détectée**: `172.17.144.1`
- **Port**: `8000`
- **Base de données**: ✅ 6 catégories configurées
- **Tests**: ✅ 278 tests passent (3 skipped sans API key)

---

## 🔑 Obtenir une Clé Gemini (GRATUIT)

1. Allez sur https://aistudio.google.com/app/apikey
2. Cliquez **"Create API Key"**
3. Copiez la clé (format: `AIzaSy...`)
4. Lancez `.\setup_gemini.ps1` et collez la clé

**Quotas gratuits:**
- 60 requêtes/minute
- 1500 requêtes/jour
- Pas de carte bancaire requise

---

## 📱 Configuration Flutter

Dans `lib/core/constants/api_constants.dart`:
```dart
class ApiConstants {
  static const String baseUrl = 'http://172.17.144.1:8000/api';
  static const String aiAnalyzeImage = '/ai/analyze-image';
}
```

⚠️ Le téléphone doit être sur le **même WiFi** que votre PC.

---

## 🧪 Tester l'Implémentation

### Test 1 : Backend seul
```powershell
php test_ai_backend.php
```

### Test 2 : Via cURL
```powershell
# Login
$resp = Invoke-RestMethod -Uri "http://172.17.144.1:8000/api/auth/login" -Method POST -ContentType "application/json" -Body '{"email":"admin@samacite.com","password":"password"}'
$token = $resp.data.token

# Analyse image
Invoke-RestMethod -Uri "http://172.17.144.1:8000/api/ai/analyze-image" -Method POST -Headers @{Authorization="Bearer $token"} -Form @{image=Get-Item "test.jpg"}
```

### Test 3 : Avec Flutter
1. Ouvrir l'app Flutter
2. Créer un nouveau signalement
3. Prendre/sélectionner une photo
4. Observer l'indicateur "🤖 Analyse IA en cours..."
5. Vérifier l'auto-complétion (5-15 secondes)

---

## 📊 Mapping des Catégories

| ID | Nom Base de Données | Gemini (category_name) |
|----|---------------------|------------------------|
| 1 | Routes & Chaussées | nid_de_poule |
| 2 | Eau & Assainissement | ordures |
| 3 | Électricité & Éclairage | eclairage |
| 4 | Déchets & Propreté | graffitis |
| 5 | Sécurité Publique | infrastructure |
| 6 | Autre | autre |

---

## 🐛 Dépannage Rapide

### "Connection refused" depuis Flutter
```powershell
# Vérifier le pare-feu
New-NetFirewallRule -DisplayName "Laravel Dev Server" -Direction Inbound -LocalPort 8000 -Protocol TCP -Action Allow
```

### "GEMINI_API_KEY non configurée"
```powershell
.\setup_gemini.ps1
```

### Tests échouent
```powershell
# Reset complet
php artisan migrate:fresh --seed
php artisan test
```

---

## ✅ Checklist

- [ ] Serveur démarre avec `.\serve.ps1`
- [ ] GEMINI_API_KEY configurée
- [ ] `php test_ai_backend.php` réussit
- [ ] Flutter peut se connecter à `http://172.17.144.1:8000`
- [ ] Analyse IA fonctionne dans l'app

---

## 🆘 Support

- **Logs Laravel**: `storage\logs\laravel.log`
- **Tests**: `php artisan test --filter=AI`
- **Documentation complète**: Voir `QUICK_START.md`

---

**Tout est prêt ! Lancez `.\serve.ps1` pour commencer.** 🎉
