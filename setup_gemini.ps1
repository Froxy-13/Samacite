#!/usr/bin/env pwsh
# Script de configuration rapide de GEMINI_API_KEY

Write-Host ""
Write-Host "Configuration GEMINI_API_KEY pour SamaCite" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

# Vérifier si déjà configuré
$envContent = Get-Content .env -Raw
if ($envContent -match "GEMINI_API_KEY=([A-Za-z0-9_-]+)") {
    $currentKey = $matches[1]
    $preview = $currentKey.Substring(0, [Math]::Min(15, $currentKey.Length))
    Write-Host "Cle actuelle: $preview..." -ForegroundColor Yellow
    Write-Host ""
    $change = Read-Host "Voulez-vous changer la cle? (o/N)"
    if ($change -ne "o") {
        Write-Host "Configuration annulee." -ForegroundColor Gray
        exit 0
    }
}

Write-Host ""
Write-Host "Pour obtenir une cle API Gemini GRATUITE:" -ForegroundColor Green
Write-Host ""
Write-Host "  1. Ouvrez: https://aistudio.google.com/app/apikey" -ForegroundColor Cyan
Write-Host "  2. Connectez-vous avec votre compte Google" -ForegroundColor Cyan
Write-Host "  3. Cliquez sur 'Create API Key'" -ForegroundColor Cyan
Write-Host "  4. Selectionnez ou creez un projet" -ForegroundColor Cyan
Write-Host "  5. Copiez la cle (format: AIzaSy...)" -ForegroundColor Cyan
Write-Host ""
Write-Host "Quotas gratuits: 60 req/min, 1500 req/jour" -ForegroundColor Gray
Write-Host ""

$apiKey = Read-Host "Collez votre cle API Gemini"

if ($apiKey -eq "") {
    Write-Host ""
    Write-Host "Aucune cle fournie. Configuration annulee." -ForegroundColor Red
    exit 1
}

# Vérifier le format
if ($apiKey -notmatch "^AIza[A-Za-z0-9_-]+$") {
    Write-Host ""
    Write-Host "Format de cle invalide. La cle doit commencer par 'AIza'" -ForegroundColor Red
    exit 1
}

# Mettre à jour ou ajouter dans .env
$envPath = ".env"
$envContent = Get-Content $envPath -Raw

if ($envContent -match "GEMINI_API_KEY=") {
    # Remplacer
    $envContent = $envContent -replace "GEMINI_API_KEY=.*", "GEMINI_API_KEY=$apiKey"
} else {
    # Ajouter à la fin
    if (-not $envContent.EndsWith("`n")) {
        $envContent += "`n"
    }
    $envContent += "GEMINI_API_KEY=$apiKey`n"
}

$envContent | Out-File -Encoding UTF8 -NoNewline $envPath

Write-Host ""
Write-Host "v Cle configuree avec succes!" -ForegroundColor Green
Write-Host ""

# Nettoyer le cache
Write-Host "Nettoyage du cache de configuration..." -ForegroundColor Yellow
php artisan config:clear | Out-Null
Write-Host "v Cache nettoye" -ForegroundColor Green
Write-Host ""

# Vérification
Write-Host "Verification de la configuration..." -ForegroundColor Yellow
$verifyScript = @"
<?php
require __DIR__.'/vendor/autoload.php';
`$app = require_once __DIR__.'/bootstrap/app.php';
`$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
`$key = config('services.gemini.api_key');
echo substr(`$key, 0, 15) . '...';
"@
$verifyScript | Out-File -Encoding UTF8 temp_verify.php
$configuredKey = php temp_verify.php
Remove-Item temp_verify.php

Write-Host "v Cle chargee: $configuredKey" -ForegroundColor Green
Write-Host ""

Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "Configuration terminee!" -ForegroundColor Green
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Prochaines etapes:" -ForegroundColor Yellow
Write-Host "  1. Lancez le serveur: .\serve.ps1" -ForegroundColor Cyan
Write-Host "  2. Testez le backend: php test_ai_backend.php" -ForegroundColor Cyan
Write-Host "  3. Integrez avec Flutter" -ForegroundColor Cyan
Write-Host ""
