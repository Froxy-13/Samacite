# 📬 Guide de Test - Système de Notifications

## ✅ État du Système de Notifications

Le système de notifications est maintenant **COMPLET** et **FONCTIONNEL**. Les notifications sont envoyées immédiatement lors de:

1. **Création d'un signalement** → L'utilisateur créateur + tous les admins reçoivent une notification
2. **Changement de statut** → L'utilisateur créateur reçoit une notification
3. **Nouveau commentaire** → Le créateur du rapport + les autres commentateurs reçoivent une notification

---

## 🧪 Tests Automatisés (PHPUnit)

Tous les tests passent avec succès:

```bash
✓ 12 tests de notifications (17 assertions)
✓ Notification sent when report created
✓ Admin receives new report notification
✓ User receives status change notification
✓ Notification sent when comment created
✓ Report owner receives comment notification
```

**Exécuter les tests:**
```bash
php artisan test tests/Feature/Notifications/ --testdox
```

---

## 🚀 Test via Postman

### Configuration préalable

1. **Variables d'environnement Postman:**
   ```
   BASE_URL = http://localhost:8000
   auth_token = [généré automatiquement après login]
   ```

2. **Créer deux comptes utilisateurs:**
   - **Utilisateur A** (Créateur de rapports)
   - **Utilisateur B** (Admin)

### Étapes du test

#### **Étape 1: Créer un signalement (Utilisateur A)**

**Endpoint:** `POST /api/reports`

**Headers:**
```
Authorization: Bearer {{auth_token}}
Content-Type: application/json
```

**Body:**
```json
{
  "title": "Route cassée à Dakar",
  "description": "Nid de poule dangereux sur la route de la corniche",
  "category_id": 1,
  "latitude": 14.6519,
  "longitude": -17.1119
}
```

**Réponse attendue:** 201 Created

```json
{
  "success": true,
  "message": "Signalement créé avec succès",
  "data": {
    "id": 1,
    "title": "Route cassée à Dakar",
    "status": "En attente",
    "user_id": 1,
    ...
  }
}
```

#### **Étape 2: Vérifier les notifications (Utilisateur A)**

**Endpoint:** `GET /api/notifications`

**Headers:**
```
Authorization: Bearer {{auth_token_user_a}}
```

**Réponse attendue:** 200 OK

```json
{
  "success": true,
  "message": "Notifications retrieved successfully",
  "data": [
    {
      "id": "uuid-1",
      "type": "App\\Notifications\\ReportCreatedNotification",
      "notifiable_id": 1,
      "data": {
        "type": "report_created",
        "report_id": 1,
        "title": "Route cassée à Dakar",
        "user_name": "John Doe"
      },
      "read_at": null,
      "created_at": "2025-11-22T12:30:45.000000Z"
    }
  ]
}
```

#### **Étape 3: Vérifier le nombre de notifications non lues (Utilisateur A)**

**Endpoint:** `GET /api/notifications/unread-count`

**Réponse attendue:** 200 OK

```json
{
  "success": true,
  "message": "Unread notifications count retrieved",
  "data": {
    "unread_count": 1
  }
}
```

#### **Étape 4: Changer le statut du signalement (Utilisateur A ou Admin)**

**Endpoint:** `PATCH /api/reports/{{report_id}}/status`

**Body:**
```json
{
  "status": "En cours"
}
```

**Réponse attendue:** 200 OK

#### **Étape 5: Vérifier les nouvelles notifications (Utilisateur A)**

**Endpoint:** `GET /api/notifications`

Vous devriez voir 2 notifications:
- 1 notification de création du rapport
- 1 notification de changement de statut

```json
{
  "success": true,
  "data": [
    {
      "type": "App\\Notifications\\StatusChangedNotification",
      "data": {
        "type": "status_changed",
        "report_id": 1,
        "title": "Route cassée à Dakar",
        "old_status": "En attente",
        "new_status": "En cours"
      },
      "read_at": null,
      "created_at": "2025-11-22T12:31:10.000000Z"
    },
    {
      "type": "App\\Notifications\\ReportCreatedNotification",
      "data": {
        "type": "report_created",
        "report_id": 1,
        "title": "Route cassée à Dakar",
        "user_name": "John Doe"
      },
      "read_at": null,
      "created_at": "2025-11-22T12:30:45.000000Z"
    }
  ]
}
```

#### **Étape 6: Marquer une notification comme lue**

**Endpoint:** `PATCH /api/notifications/{{notification_id}}/read`

**Réponse attendue:** 200 OK

```json
{
  "success": true,
  "message": "Notification marked as read",
  "data": {
    "id": "uuid-1",
    "read_at": "2025-11-22T12:32:00.000000Z"
  }
}
```

#### **Étape 7: Marquer toutes les notifications comme lues**

**Endpoint:** `PATCH /api/notifications/mark-all-read`

**Réponse attendue:** 200 OK

```json
{
  "success": true,
  "message": "All notifications marked as read",
  "data": null
}
```

#### **Étape 8: Supprimer une notification**

**Endpoint:** `DELETE /api/notifications/{{notification_id}}`

**Réponse attendue:** 200 OK

```json
{
  "success": true,
  "message": "Notification deleted successfully",
  "data": null
}
```

---

## 📝 Résumé des Endpoints

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `GET` | `/api/notifications` | Récupérer toutes les notifications (paginées) |
| `GET` | `/api/notifications/unread-count` | Compter les notifications non lues |
| `PATCH` | `/api/notifications/{id}/read` | Marquer une notification comme lue |
| `PATCH` | `/api/notifications/mark-all-read` | Marquer toutes comme lues |
| `DELETE` | `/api/notifications/{id}` | Supprimer une notification |
| `DELETE` | `/api/notifications` | Supprimer toutes les notifications |

---

## 🔄 Types de Notifications

### 1️⃣ **report_created**
- **Déclencheur:** Création d'un signalement
- **Destinataires:** Créateur du rapport + Tous les admins
- **Données:** `report_id`, `title`, `user_name`

### 2️⃣ **status_changed**
- **Déclencheur:** Changement de statut du rapport
- **Destinataires:** Créateur du rapport
- **Données:** `report_id`, `title`, `old_status`, `new_status`

### 3️⃣ **comment_created**
- **Déclencheur:** Nouveau commentaire sur un rapport
- **Destinataires:** Créateur du rapport + Autres commentateurs
- **Données:** `report_id`, `content`, `user_name`

---

## ✨ Améliorations Apportées

✅ **Notifications envoyées immédiatement** (pas d'attente de queue)
✅ **Créateur du rapport reçoit notification** lors de la création
✅ **Admins informés** de chaque nouveau signalement
✅ **Utilisateurs informés** des changements de statut
✅ **Participants informés** des nouveaux commentaires
✅ **Base de données** sqlite avec table `notifications`
✅ **API complète** pour gérer les notifications
✅ **Tests automatisés** (12 tests, 17 assertions)

---

## 🐛 Dépannage

### Les notifications ne s'affichent pas?

1. **Vérifiez la migration:**
   ```bash
   php artisan migrate:status
   ```
   Assurez-vous que `create_notifications_table` est "Ran"

2. **Vérifiez que l'utilisateur est authentifié:**
   ```bash
   GET /api/auth/me
   ```

3. **Vérifiez les logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

4. **Videz le cache:**
   ```bash
   php artisan cache:clear
   ```

---

## 📊 Test Suite

```bash
# Tous les tests
php artisan test

# Juste les notifications
php artisan test tests/Feature/Notifications/

# Avec le testdox (lisible)
php artisan test tests/Feature/Notifications/ --testdox

# Résultat actuel:
# ✅ 145/145 tests passent
# ✅ 391 assertions vérifiées
```

---

**Dernière mise à jour:** 22 Novembre 2025
**Status:** ✅ PRODUCTION READY
