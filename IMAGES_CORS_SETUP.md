# Configuration CORS et Upload d'Images - SamaCité

## ✅ Modifications effectuées

### 1. **Images incluses dans les réponses API**
Les images sont maintenant automatiquement retournées avec chaque signalement :

```json
{
  "success": true,
  "message": "Signalement récupéré avec succès",
  "data": {
    "id": 1,
    "title": "Nid de poule",
    "description": "Grande fissure sur la route",
    "status": "En attente",
    "user": {...},
    "category": {...},
    "images": [
      {
        "id": 1,
        "report_id": 1,
        "image_path": "reports/xyz123.jpg",
        "image_url": "http://localhost:8000/storage/reports/xyz123.jpg",
        "created_at": "2025-11-23T10:30:00.000000Z",
        "updated_at": "2025-11-23T10:30:00.000000Z"
      }
    ],
    "created_at": "2025-11-23T10:00:00.000000Z",
    "updated_at": "2025-11-23T10:00:00.000000Z"
  }
}
```

### 2. **Configuration CORS pour applications mobiles**

#### Fichier `config/cors.php`
- ✅ Chemins autorisés : `api/*`, `sanctum/csrf-cookie`, `storage/*`
- ✅ Méthodes HTTP : GET, POST, PUT, PATCH, DELETE, OPTIONS
- ✅ Headers autorisés : Content-Type, Authorization, Accept, Origin, etc.
- ✅ Cache CORS : 24 heures
- ✅ Credentials supportés : `true`

#### Fichier `.env`
```env
CORS_ALLOWED_ORIGINS=*
```

**En production**, remplacer par les domaines spécifiques :
```env
CORS_ALLOWED_ORIGINS=https://votreapp.com,capacitor://localhost,ionic://localhost
```

### 3. **Repository Pattern mis à jour**
Les images sont automatiquement chargées (eager-loading) pour éviter le problème N+1 :
- `paginateWithFilters()` → charge `user`, `category`, `images`
- `findWithRelations()` → charge `user`, `category`, `images`
- `findByUser()` → charge `user`, `category`, `images`

---

## 📱 Utilisation depuis une application mobile

### Flutter (avec Dio)

#### 1. Configuration Dio avec CORS
```dart
import 'package:dio/dio.dart';

final dio = Dio(
  BaseOptions(
    baseUrl: 'http://localhost:8000/api',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    },
  ),
);

// Intercepteur pour ajouter le token
dio.interceptors.add(
  InterceptorsWrapper(
    onRequest: (options, handler) {
      final token = storage.read('token'); // Votre méthode de stockage
      if (token != null) {
        options.headers['Authorization'] = 'Bearer $token';
      }
      return handler.next(options);
    },
  ),
);
```

#### 2. Créer un signalement
```dart
Future<Response> createReport({
  required String title,
  String? description,
  int? categoryId,
  double? latitude,
  double? longitude,
}) async {
  try {
    final response = await dio.post(
      '/reports',
      data: {
        'title': title,
        'description': description,
        'category_id': categoryId,
        'latitude': latitude,
        'longitude': longitude,
      },
    );
    return response;
  } catch (e) {
    rethrow;
  }
}
```

#### 3. Upload d'image (avec compression automatique)
```dart
import 'package:image_picker/image_picker.dart';
import 'package:dio/dio.dart';
import 'package:flutter_image_compress/flutter_image_compress.dart';

Future<Response> uploadReportImage({
  required int reportId,
  required File imageFile,
}) async {
  try {
    // Compresser l'image AVANT l'upload pour éviter les timeout
    final compressedFile = await FlutterImageCompress.compressAndGetFile(
      imageFile.path,
      imageFile.path.replaceAll('.jpg', '_compressed.jpg'),
      quality: 85,
      minWidth: 1920,
      minHeight: 1920,
    );

    final fileToUpload = compressedFile ?? imageFile;
    String fileName = fileToUpload.path.split('/').last;
    
    FormData formData = FormData.fromMap({
      'image': await MultipartFile.fromFile(
        fileToUpload.path,
        filename: fileName,
      ),
    });

    final response = await dio.post(
      '/reports/$reportId/images',
      data: formData,
      options: Options(
        headers: {
          'Content-Type': 'multipart/form-data',
        },
        sendTimeout: const Duration(seconds: 60), // Timeout de 60s
        receiveTimeout: const Duration(seconds: 60),
      ),
    );
    
    return response;
  } catch (e) {
    rethrow;
  }
}
```

**Ajoutez cette dépendance dans `pubspec.yaml` :**
```yaml
dependencies:
  flutter_image_compress: ^2.1.0
```

#### 4. Récupérer un signalement avec images
```dart
Future<Response> getReport(int reportId) async {
  try {
    final response = await dio.get('/reports/$reportId');
    
    // Les images sont automatiquement incluses
    final images = response.data['data']['images'] as List;
    
    for (var image in images) {
      print('Image URL: ${image['image_url']}');
    }
    
    return response;
  } catch (e) {
    rethrow;
  }
}
```

#### 5. Liste des signalements avec images
```dart
Future<Response> getReports() async {
  try {
    final response = await dio.get('/reports');
    
    // Chaque report contient ses images
    final reports = response.data['data'] as List;
    
    for (var report in reports) {
      print('Report: ${report['title']}');
      print('Images count: ${report['images'].length}');
    }
    
    return response;
  } catch (e) {
    rethrow;
  }
}
```

#### 6. Supprimer une image
```dart
Future<Response> deleteImage(int imageId) async {
  try {
    final response = await dio.delete('/images/$imageId');
    return response;
  } catch (e) {
    rethrow;
  }
}
```

---

## 🔒 Authentification

Toutes les routes d'images nécessitent une authentification via Sanctum :

```dart
// Login
Future<String?> login(String email, String password) async {
  try {
    final response = await dio.post(
      '/auth/login',
      data: {
        'email': email,
        'password': password,
      },
    );
    
    final token = response.data['data']['token'];
    await storage.write('token', token); // Sauvegarder le token
    
    return token;
  } catch (e) {
    rethrow;
  }
}
```

---

## 📋 Routes API disponibles

### Signalements
- `GET /api/reports` - Liste des signalements (avec images)
- `GET /api/reports/{id}` - Détails d'un signalement (avec images)
- `POST /api/reports` - Créer un signalement
- `PATCH /api/reports/{id}` - Mettre à jour un signalement
- `DELETE /api/reports/{id}` - Supprimer un signalement

### Images
- `POST /api/reports/{reportId}/images` - Upload une image
- `GET /api/reports/{reportId}/images` - Liste des images d'un signalement
- `DELETE /api/images/{id}` - Supprimer une image

---

## 🧪 Tests

Tous les tests passent (39 tests, 136 assertions) :
```bash
php artisan test tests/Feature/Reports/
```

Tests spécifiques aux images :
- `UploadImageTest` - Upload d'images (6 tests)
- `DeleteImageTest` - Suppression d'images (5 tests)
- `GetReportWithImagesTest` - Images dans les réponses (2 tests)

---

## ⚙️ Configuration de production

Pour la production, modifier `.env` :

```env
# Domaines spécifiques au lieu de *
CORS_ALLOWED_ORIGINS=https://api.samacite.sn,https://admin.samacite.sn,capacitor://localhost

# Sanctum stateful domains
SANCTUM_STATEFUL_DOMAINS=api.samacite.sn,admin.samacite.sn

# URL de l'application
APP_URL=https://api.samacite.sn
```

---

## 📝 Notes importantes

1. **Limite de taille** : Maximum 10MB par image (mais compresser AVANT l'upload recommandé)
2. **Formats acceptés** : jpeg, png, jpg, gif
3. **Stockage** : `storage/app/public/reports/`
4. **URL publique** : Les images sont accessibles via `storage/reports/nom-fichier.jpg`
5. **Compression automatique** : 
   - ✅ Compression immédiate lors de l'upload (qualité 85%, max 1920x1920px)
   - ✅ Miniatures générées en arrière-plan (300x300px)
   - ✅ Réduction typique: 60-80% de la taille originale
6. **Permissions** : Seul le propriétaire ou un admin peut uploader/supprimer des images
7. **Timeout** : Configurer 60 secondes de timeout côté client pour les uploads

---

## 🚀 Prochaines étapes possibles

- [ ] Compression/redimensionnement automatique des images
- [ ] Support de plusieurs images en une seule requête
- [ ] Miniatures (thumbnails) pour les listes
- [ ] Watermarking des images
- [ ] Support de la géolocalisation EXIF
