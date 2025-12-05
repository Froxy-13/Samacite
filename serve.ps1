#!/usr/bin/env pwsh
# Script simple de démarrage du serveur Laravel

Write-Host ""
Write-Host "Demarrage du serveur Laravel SamaCite..." -ForegroundColor Cyan
Write-Host ""

# Détection IP
$ip = (Get-NetIPAddress -AddressFamily IPv4 | Where-Object {$_.InterfaceAlias -like '*Wi-Fi*' -or $_.InterfaceAlias -like '*Ethernet*'} | Select-Object -First 1).IPAddress

Write-Host "Serveur accessible sur:" -ForegroundColor Green
Write-Host "  - http://$ip:8000" -ForegroundColor Cyan
Write-Host "  - http://localhost:8000" -ForegroundColor Cyan
Write-Host ""
Write-Host "Pour Flutter, utilisez: http://${ip}:8000" -ForegroundColor Yellow
Write-Host ""
Write-Host "Appuyez sur Ctrl+C pour arreter" -ForegroundColor Gray
Write-Host "------------------------------------------------------" -ForegroundColor DarkGray
Write-Host ""

# Lancer le serveur
php artisan serve --host=0.0.0.0 --port=8000
