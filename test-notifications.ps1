# Script de Test des Notifications - SamaCité
# Utilisation: .\test-notifications.ps1

$baseUrl = "http://192.168.1.17:8000/api"
$userId = 10
$token = "102|MexAeJK0AXEaNrLILfrAwtMO8gfY7I2ruZRvAU8ccc539db2"

Write-Host "🔔 Test des Notifications SamaCité" -ForegroundColor Cyan
Write-Host "=================================" -ForegroundColor Cyan
Write-Host ""

# Headers
$headers = @{
    "Authorization" = "Bearer $token"
    "Content-Type" = "application/json"
    "Accept" = "application/json"
}

# Test 1: Créer une notification de test
Write-Host "📝 Test 1: Création d'une notification de test..." -ForegroundColor Yellow
try {
    $response = Invoke-RestMethod -Uri "$baseUrl/test/notification/$userId" `
        -Method Post `
        -Headers $headers
    
    Write-Host "✅ Notification créée avec succès!" -ForegroundColor Green
    Write-Host "   Titre: $($response.data.title)" -ForegroundColor White
    Write-Host "   Corps: $($response.data.body)" -ForegroundColor White
    Write-Host ""
} catch {
    Write-Host "❌ Erreur: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host ""
}

Start-Sleep -Seconds 1

# Test 2: Récupérer toutes les notifications
Write-Host "📋 Test 2: Récupération des notifications..." -ForegroundColor Yellow
try {
    $response = Invoke-RestMethod -Uri "$baseUrl/notifications" `
        -Method Get `
        -Headers $headers
    
    $count = $response.data.Count
    Write-Host "✅ $count notification(s) récupérée(s)!" -ForegroundColor Green
    
    if ($count -gt 0) {
        Write-Host ""
        Write-Host "📌 Dernières notifications:" -ForegroundColor Cyan
        foreach ($notif in $response.data | Select-Object -First 5) {
            $readStatus = if ($notif.read_at) { "✓ Lu" } else { "○ Non lu" }
            Write-Host "   [$readStatus] $($notif.data.title)" -ForegroundColor White
            Write-Host "      → $($notif.data.body)" -ForegroundColor Gray
            Write-Host "      Créé: $($notif.created_at)" -ForegroundColor DarkGray
            Write-Host ""
        }
    }
} catch {
    Write-Host "❌ Erreur: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host ""
}

Start-Sleep -Seconds 1

# Test 3: Compter les notifications non lues
Write-Host "🔢 Test 3: Comptage des notifications non lues..." -ForegroundColor Yellow
try {
    $response = Invoke-RestMethod -Uri "$baseUrl/notifications/unread-count" `
        -Method Get `
        -Headers $headers
    
    Write-Host "✅ Notifications non lues: $($response.data.unread_count)" -ForegroundColor Green
    Write-Host ""
} catch {
    Write-Host "❌ Erreur: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host ""
}

Write-Host "=================================" -ForegroundColor Cyan
Write-Host "✅ Tests terminés!" -ForegroundColor Green
Write-Host ""
Write-Host "💡 Pour créer plus de notifications:" -ForegroundColor Yellow
Write-Host "   Invoke-RestMethod -Uri '$baseUrl/test/notification/$userId' -Method Post -Headers @{'Authorization'='Bearer $token'}" -ForegroundColor Gray
