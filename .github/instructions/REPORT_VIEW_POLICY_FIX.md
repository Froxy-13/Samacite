# Correction de la Policy d'Affichage des Signalements

## 📋 Problème Identifié

**Erreur** : Impossible d'afficher les détails des signalements qui n'appartiennent pas à l'utilisateur connecté.

- **Endpoint** : `GET /api/reports/{id}`
- **Code HTTP** : `403 Forbidden`
- **Message** : "This action is unauthorized."

### Cause
La méthode `view()` de `ReportPolicy` limitait l'affichage des détails aux propriétaires ou admins uniquement :

```php
// ❌ AVANT (code restrictif)
public function view(User $user, Report $report): bool
{
    return $user->id === $report->user_id || $user->hasRole('Admin');
}
```

## ✅ Solution Implémentée

### 1. Modification de `app/Policies/ReportPolicy.php`

```php
// ✅ APRÈS (accès ouvert à tous les utilisateurs authentifiés)
public function view(User $user, Report $report): bool
{
    // Any authenticated user can view any report details
    return true;
}
```

### 2. Comportement de l'API

| Endpoint | Méthode | Autorisation | Comportement |
|----------|---------|--------------|--------------|
| `/api/reports` | GET | Tous les utilisateurs authentifiés | Retourne la liste de TOUS les signalements |
| `/api/reports?user_id=X` | GET | Tous les utilisateurs authentifiés | Filtre les signalements de l'utilisateur X |
| `/api/reports/{id}` | GET | ✅ **Tous les utilisateurs authentifiés** | Affiche les détails de n'importe quel signalement |
| `/api/reports/{id}` | DELETE | ❌ Propriétaire ou Admin uniquement | Supprime le signalement |
| `/api/reports/{id}` | PATCH | ❌ Propriétaire ou Admin uniquement | Met à jour le signalement |

### 3. Tests Ajoutés

Trois nouveaux tests dans `tests/Feature/Reports/GetReportsTest.php` :

#### Test 1 : Afficher les détails de son propre signalement
```php
public function test_user_can_view_details_of_own_report()
{
    $report = Report::where('user_id', $this->user->id)->first();
    
    $response = $this->actingAs($this->user)->getJson("/api/reports/{$report->id}");
    
    $response->assertStatus(200); // ✅ Succès
}
```

#### Test 2 : Afficher les détails du signalement d'un autre utilisateur
```php
public function test_user_can_view_details_of_other_user_report()
{
    // Signalement créé par l'admin
    $adminReport = Report::where('user_id', $this->admin->id)->first();
    
    // L'utilisateur régulier peut voir les détails du signalement de l'admin
    $response = $this->actingAs($this->user)->getJson("/api/reports/{$adminReport->id}");
    
    $response->assertStatus(200); // ✅ Succès
}
```

#### Test 3 : Signalement inexistant retourne 404
```php
public function test_get_nonexistent_report_returns_404()
{
    $response = $this->actingAs($this->user)->getJson('/api/reports/9999');
    
    $response->assertStatus(404);
}
```

## 🎯 Résultats

### Avant la Correction
- ❌ Utilisateur ne peut voir que ses propres signalements
- ❌ `GET /api/reports/{id}` retourne 403 pour les signalements des autres
- ❌ Application mobile ne peut pas afficher les détails des signalements publics

### Après la Correction
- ✅ Tous les utilisateurs authentifiés peuvent voir tous les signalements
- ✅ `GET /api/reports/{id}` retourne 200 OK pour n'importe quel signalement
- ✅ `DELETE /api/reports/{id}` reste protégé (403 si non propriétaire/admin)
- ✅ Le bouton de suppression dans l'app mobile n'apparaît que pour les signalements de l'utilisateur

## 📊 Tests

### Résultats Complets
```bash
Tests:    244 passed (738 assertions)
Duration: 11.16s
```

### Tests Spécifiques aux Signalements
- ✅ `test_user_can_view_all_reports` : Liste complète visible
- ✅ `test_user_can_filter_by_user_id` : Filtrage optionnel fonctionne
- ✅ `test_user_can_view_details_of_own_report` : Détails de son signalement
- ✅ `test_user_can_view_details_of_other_user_report` : Détails des autres signalements
- ✅ `test_get_nonexistent_report_returns_404` : Gestion des erreurs
- ✅ `test_user_cannot_delete_other_user_report` : Suppression protégée

## 🔐 Sécurité

La correction **n'introduit aucune faille de sécurité** :

1. **Authentification requise** : Tous les endpoints nécessitent un token Sanctum valide
2. **Autorisation de suppression maintenue** : Seuls les propriétaires et admins peuvent supprimer
3. **Autorisation de modification maintenue** : Seuls les propriétaires et admins peuvent modifier
4. **Visibilité publique** : Les signalements sont publics par nature (problèmes de la cité)

## 📱 Impact Mobile

### Flutter App
L'application mobile peut maintenant :
- ✅ Afficher la liste complète des signalements
- ✅ Ouvrir les détails de n'importe quel signalement
- ✅ Afficher le bouton "Supprimer" uniquement pour ses propres signalements
- ✅ Recevoir 403 Forbidden si tentative de suppression d'un signalement d'autrui

### Code Flutter Suggéré
```dart
// Dans le widget de détails du signalement
bool canDelete = report.userId == currentUserId || isAdmin;

if (canDelete) {
  IconButton(
    icon: Icon(Icons.delete),
    onPressed: () => deleteReport(report.id),
  )
}
```

## 🚀 Déploiement

### Fichiers Modifiés
1. `app/Policies/ReportPolicy.php` - Méthode `view()` rendue publique
2. `tests/Feature/Reports/GetReportsTest.php` - 3 nouveaux tests ajoutés

### Commandes
```bash
# Tester
php artisan test --filter=GetReportsTest

# Tester tous les tests
php artisan test

# Déployer (si nécessaire)
php artisan config:cache
php artisan route:cache
```

## ✅ Validation

- ✅ Code modifié
- ✅ Tests ajoutés et passants (244/244)
- ✅ Aucune régression détectée
- ✅ Sécurité maintenue
- ✅ Comportement attendu vérifié

---

**Date** : 25 novembre 2025  
**Statut** : ✅ Corrigé et Testé  
**Tests** : 244 passés / 244 total
