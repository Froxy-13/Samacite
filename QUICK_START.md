# 🚀 Guide de Démarrage Rapide - AI Analysis Backend

## ✅ État actuel

**Backend Laravel : 100% Implémenté ✅**
- Controller: `AIAnalysisController.php` ✅
- Route: `POST /api/ai/analyze-image` ✅
- Tests: 9 tests complets ✅
- Documentation complète ✅

**Votre configuration :**
- IP détectée: `172.17.144.1`
- Port: `8000`
- Base de données: Configurée avec 6 catégories

---

## 🎯 Démarrage en 3 étapes

### Option 1️⃣ : Script automatique (RECOMMANDÉ)

```powershell
# Exécution du script PowerShell
.\start_server.ps1
```

Ce script va :
- ✅ Détecter votre IP automatiquement
- ✅ Vérifier la configuration GEMINI_API_KEY
- ✅ Nettoyer le cache
- ✅ Vérifier les catégories
- ✅ Lancer les tests
- ✅ Démarrer le serveur sur 0.0.0.0:8000

### Option 2️⃣ : Configuration manuelle

#### Étape 1 : Obtenir une clé Gemini (2 minutes)

1. Aller sur: https://aistudio.google.com/app/apikey
2. Cliquer **"Create API Key"**
3. Sélectionner/créer un projet
4. Copier la clé (format: `AIzaSy...`)

#### Étape 2 : Configurer le backend

```powershell
# 1. Ouvrir le fichier .env
notepad .env

# 2. Ajouter la ligne (remplacer avec votre vraie clé)
GEMINI_API_KEY=AIzaSy_votre_cle_ici

# 3. Nettoyer le cache
php artisan config:clear

# 4. Vérifier la configuration
php artisan tinker
>>> config('services.gemini.api_key')
# Devrait afficher votre clé
>>> exit
```

#### Étape 3 : Démarrer le serveur

```powershell
php artisan serve --host=0.0.0.0 --port=8000
```

Le serveur sera accessible sur :
- ✅ `http://172.17.144.1:8000` (depuis d'autres appareils sur le réseau)
- ✅ `http://localhost:8000` (depuis votre PC)

---

## 🧪 Tester l'implémentation

### Test automatique avec le script PHP

```powershell
php test_ai_backend.php
```

Ce script va :
1. Se connecter avec les credentials par défaut
2. Créer une image de test
3. Appeler l'endpoint AI
4. Vérifier le format de réponse
5. Afficher un rapport détaillé

**Sortie attendue :**
```
╔════════════════════════════════════════════════════╗
║  Test Backend AI Analysis - SamaCité              ║
╚════════════════════════════════════════════════════╝

📝 ÉTAPE 1 : Authentification
--------------------------------------------------
✓ Connecté en tant que: Admin User
✓ Token obtenu: eyJ0eXAiOiJKV1QiLCJh...

🖼️  ÉTAPE 2 : Préparation image de test
--------------------------------------------------
✓ Image de test créée: test_pothole.jpg
→ Taille de l'image: 45.23 KB

🤖 ÉTAPE 3 : Analyse IA avec Gemini
--------------------------------------------------
→ Envoi de l'image à l'endpoint AI...
→ Temps de réponse: 3245ms
✓ Analyse réussie!

✨ ÉTAPE 4 : Résultat de l'analyse
--------------------------------------------------
   📌 Titre       : Nid de poule dangereux
   📝 Description : Trou important dans la chaussée...
   🏷️  Catégorie   : 1
   🎯 Confiance   : 92%
→ Nom de catégorie: Routes & Chaussées

==================================================
🎉 TOUS LES TESTS RÉUSSIS!
==================================================
```

### Test manuel avec cURL

```powershell
# 1. Se connecter
$response = Invoke-RestMethod -Uri "http://172.17.144.1:8000/api/auth/login" `
  -Method POST `
  -ContentType "application/json" `
  -Body '{"email":"admin@samacite.com","password":"password"}'

$token = $response.data.token

# 2. Tester l'analyse IA
$imagePath = "C:\chemin\vers\image.jpg"
$headers = @{ Authorization = "Bearer $token" }
$form = @{ image = Get-Item -Path $imagePath }

Invoke-RestMethod -Uri "http://172.17.144.1:8000/api/ai/analyze-image" `
  -Method POST `
  -Headers $headers `
  -Form $form
```

---

## 📱 Configuration Flutter

Dans votre projet Flutter, mettez à jour `lib/core/constants/api_constants.dart` :

```dart
class ApiConstants {
  // Utiliser l'IP détectée de votre PC
  static const String baseUrl = 'http://172.17.144.1:8000/api';
  
  // Endpoint AI (déjà configuré normalement)
  static const String aiAnalyzeImage = '/ai/analyze-image';
}
```

**⚠️ Important :**
- Le téléphone Flutter doit être sur le **même réseau WiFi** que votre PC
- Désactivez le pare-feu Windows temporairement si connexion impossible
- Sur émulateur Android : utilisez `http://10.0.2.2:8000`

---

## 🔍 Vérifications importantes

### 1. Vérifier les catégories en base de données

```powershell
php artisan tinker
>>> DB::table('categories')->select('id','name')->orderBy('id')->get()
```

**Mapping attendu :**
```
1 → Routes & Chaussées      (nid_de_poule)
2 → Eau & Assainissement     (ordures)
3 → Électricité & Éclairage  (eclairage)
4 → Déchets & Propreté       (graffitis)
5 → Sécurité Publique        (infrastructure)
6 → Autre                    (autre)
```

### 2. Vérifier que tous les tests passent

```powershell
php artisan test --filter=AnalyzeImage
```

**Résultat attendu :**
- ✅ 6 tests réussis
- ⏭️ 3 tests skipped (normaux si pas de clé API)

### 3. Vérifier les logs en cas d'erreur

```powershell
Get-Content storage\logs\laravel.log -Tail 50
```

---

## 🐛 Dépannage

### Erreur: "GEMINI_API_KEY non configurée"

```powershell
# Vérifier le .env
Select-String -Path .env -Pattern "GEMINI_API_KEY"

# Si absent, ajouter
echo "GEMINI_API_KEY=AIza_votre_cle" >> .env

# Nettoyer cache
php artisan config:clear
```

### Erreur: "Connection refused" depuis Flutter

```powershell
# 1. Vérifier que le serveur tourne
curl http://172.17.144.1:8000/api/health

# 2. Vérifier le pare-feu Windows
# Panneau de configuration → Pare-feu → Autoriser une application
# Ajouter PHP

# 3. Vérifier l'IP est correcte
(Get-NetIPAddress -AddressFamily IPv4 | Where-Object {$_.InterfaceAlias -like '*Wi-Fi*'}).IPAddress
```

### Erreur: "Invalid API key"

```powershell
# Tester la clé directement
$apiKey = "AIza_votre_cle"
Invoke-RestMethod -Uri "https://generativelanguage.googleapis.com/v1beta/models?key=$apiKey"

# Si erreur, régénérer une nouvelle clé sur:
# https://aistudio.google.com/app/apikey
```

### Tests échouent avec "Database not found"

```powershell
# Créer la base de données
php artisan migrate --seed

# Si besoin de reset complet
php artisan migrate:fresh --seed
```

---

## 📊 Monitoring en temps réel

Pendant les tests Flutter, surveillez les logs :

```powershell
# Terminal 1 : Serveur
php artisan serve --host=0.0.0.0 --port=8000

# Terminal 2 : Logs en temps réel
Get-Content storage\logs\laravel.log -Wait -Tail 20
```

---

## 🎉 Checklist finale avant intégration Flutter

- [ ] Serveur Laravel démarre sans erreur
- [ ] `php test_ai_backend.php` réussit tous les tests
- [ ] GEMINI_API_KEY configurée dans .env
- [ ] Base de données contient 6 catégories
- [ ] IP `172.17.144.1` accessible depuis le réseau
- [ ] Pare-feu Windows autorise PHP
- [ ] `api_constants.dart` utilise la bonne IP
- [ ] Téléphone sur le même WiFi que le PC

---

## 💡 Commandes utiles

```powershell
# Démarrage rapide (tout-en-un)
.\start_server.ps1

# Test backend uniquement
php test_ai_backend.php

# Tests unitaires Laravel
php artisan test --filter=AI

# Voir les routes disponibles
php artisan route:list --path=ai

# Nettoyer tous les caches
php artisan optimize:clear

# Voir la configuration Gemini
php artisan tinker
>>> config('services.gemini')
```

---

## 📖 Documentation complète

- **AI_INSTALLATION_GUIDE.md** : Guide détaillé d'installation
- **AI_BACKEND_IMPLEMENTATION.md** : Spécifications techniques
- **TESTING_GUIDE.md** : Scénarios de test Flutter
- **spec.instructions.md** : Spécifications complètes du projet

---

## ✅ Prochaines étapes

1. **Maintenant** : Démarrer le serveur
   ```powershell
   .\start_server.ps1
   ```

2. **Ensuite** : Tester le backend
   ```powershell
   php test_ai_backend.php
   ```

3. **Puis** : Ouvrir l'app Flutter et tester la création de signalement avec photo

4. **Enfin** : Observer l'auto-complétion des champs (titre, description, catégorie)

---

**🚀 Tout est prêt ! Lancez `.\start_server.ps1` pour commencer.**
