#!/usr/bin/env pwsh
# Script PowerShell de démarrage rapide pour SamaCité Backend AI

Write-Host ""
Write-Host "========================================================" -ForegroundColor Cyan
Write-Host "  SamaCite Backend - Demarrage AI Analysis" -ForegroundColor Cyan
Write-Host "========================================================" -ForegroundColor Cyan
Write-Host ""

$ErrorActionPreference = "Stop"

# Détection IP
Write-Host "-> Detection de l'IP locale..." -ForegroundColor Yellow
$ip = (Get-NetIPAddress -AddressFamily IPv4 | Where-Object {$_.InterfaceAlias -like '*Wi-Fi*' -or $_.InterfaceAlias -like '*Ethernet*'} | Select-Object -First 1).IPAddress
Write-Host "v IP detectee: $ip" -ForegroundColor Green
Write-Host ""

# Vérification .env
Write-Host "-> Verification du fichier .env..." -ForegroundColor Yellow
if (-not (Test-Path ".env")) {
    Write-Host "x Fichier .env introuvable!" -ForegroundColor Red
    Write-Host "  Copiez .env.example vers .env et configurez-le." -ForegroundColor Red
    Write-Host ""
    exit 1
}
Write-Host "v Fichier .env trouve" -ForegroundColor Green
Write-Host ""

# Vérification GEMINI_API_KEY
Write-Host "-> Verification de GEMINI_API_KEY..." -ForegroundColor Yellow
$envContent = Get-Content .env -Raw
if ($envContent -match "GEMINI_API_KEY=([A-Za-z0-9_-]+)") {
    $keyPreview = $matches[1].Substring(0, [Math]::Min(15, $matches[1].Length))
    Write-Host "v Cle Gemini configuree: $keyPreview..." -ForegroundColor Green
    Write-Host ""
} else {
    Write-Host "! GEMINI_API_KEY non configuree dans .env" -ForegroundColor Red
    Write-Host ""
    Write-Host "Pour obtenir une cle API gratuite:" -ForegroundColor Yellow
    Write-Host "   1. Visitez: https://aistudio.google.com/app/apikey" -ForegroundColor Cyan
    Write-Host "   2. Cliquez 'Create API Key'" -ForegroundColor Cyan
    Write-Host "   3. Copiez la cle (format: AIza...)" -ForegroundColor Cyan
    Write-Host "   4. Ajoutez dans .env: GEMINI_API_KEY=votre_cle" -ForegroundColor Cyan
    Write-Host "   5. Relancez ce script" -ForegroundColor Cyan
    Write-Host ""
    
    $continue = Read-Host "Continuer sans cle API? Les tests IA echoueront. (o/N)"
    if ($continue -ne "o") {
        exit 1
    }
}

# Nettoyage cache
Write-Host "-> Nettoyage du cache de configuration..." -ForegroundColor Yellow
php artisan config:clear | Out-Null
Write-Host "v Cache nettoye" -ForegroundColor Green
Write-Host ""

# Vérification des catégories
Write-Host "-> Verification des categories en base de donnees..." -ForegroundColor Yellow
$checkScript = @"
<?php
require __DIR__.'/vendor/autoload.php';
`$app = require_once __DIR__.'/bootstrap/app.php';
`$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
`$count = DB::table('categories')->count();
echo `$count;
"@
$checkScript | Out-File -Encoding UTF8 temp_check.php
$catCount = php temp_check.php
Remove-Item temp_check.php
if ([int]$catCount -ge 6) {
    Write-Host "v $catCount categories trouvees" -ForegroundColor Green
    Write-Host ""
} else {
    Write-Host "! Seulement $catCount categories trouvees" -ForegroundColor Yellow
    Write-Host "  Executez: php artisan db:seed" -ForegroundColor Yellow
    Write-Host ""
}

# Lancement des tests
Write-Host "-> Lancement de la suite de tests..." -ForegroundColor Yellow
Write-Host "  (Cela peut prendre 30-60 secondes)" -ForegroundColor Gray
Write-Host ""

$testOutput = php artisan test --filter=AIAnalysisController 2>&1
$testSuccess = $LASTEXITCODE -eq 0

if ($testSuccess) {
    Write-Host "v Tests reussis!" -ForegroundColor Green
    Write-Host ""
} else {
    Write-Host "! Certains tests ont echoue (normal sans API key)" -ForegroundColor Yellow
    Write-Host ""
}

# Démarrage du serveur
Write-Host ""
Write-Host "========================================================" -ForegroundColor Cyan
Write-Host "  Demarrage du serveur Laravel" -ForegroundColor Cyan
Write-Host "========================================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "Serveur accessible sur:" -ForegroundColor Green
Write-Host "   - http://$ip:8000" -ForegroundColor Cyan
Write-Host "   - http://localhost:8000" -ForegroundColor Cyan
Write-Host ""

Write-Host "Configuration Flutter:" -ForegroundColor Yellow
Write-Host "   Dans api_constants.dart, utilisez: http://${ip}:8000" -ForegroundColor Cyan
Write-Host ""

Write-Host "Pour tester le backend AI:" -ForegroundColor Yellow
Write-Host "   php test_ai_backend.php" -ForegroundColor Cyan
Write-Host ""

Write-Host "Appuyez sur Ctrl+C pour arreter le serveur" -ForegroundColor Gray
Write-Host ""
Write-Host "------------------------------------------------------" -ForegroundColor DarkGray
Write-Host ""

# Lancement du serveur
php artisan serve --host=0.0.0.0 --port=8000
