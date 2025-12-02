#!/bin/bash

# Script de vérification du Queue Worker
# Usage: ./check_queue_worker.sh

echo "=========================================="
echo "  SamaCité - Queue Worker Status"
echo "=========================================="
echo ""

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 1. Vérifier le service systemd
echo "1️⃣  Vérification du service systemd..."
if systemctl is-active --quiet samacite-queue; then
    echo -e "${GREEN}✅ Service actif${NC}"
else
    echo -e "${RED}❌ Service inactif${NC}"
    echo "   → Lancer: sudo systemctl start samacite-queue"
fi
echo ""

# 2. Vérifier les jobs en attente
echo "2️⃣  Jobs en attente dans la queue..."
JOBS_COUNT=$(php artisan tinker --execute="echo DB::table('jobs')->count();" 2>/dev/null | grep -o '[0-9]*' | tail -1)
if [ "$JOBS_COUNT" -eq 0 ]; then
    echo -e "${GREEN}✅ Aucun job en attente (queue vide)${NC}"
elif [ "$JOBS_COUNT" -lt 10 ]; then
    echo -e "${YELLOW}⚠️  $JOBS_COUNT jobs en attente${NC}"
else
    echo -e "${RED}⚠️  $JOBS_COUNT jobs en attente !${NC}"
    echo "   → Le worker est peut-être surchargé"
fi
echo ""

# 3. Jobs échoués
echo "3️⃣  Jobs échoués..."
FAILED_COUNT=$(php artisan queue:failed 2>/dev/null | grep -c "App\\\\Jobs" || echo "0")
if [ "$FAILED_COUNT" -eq 0 ]; then
    echo -e "${GREEN}✅ Aucun job échoué${NC}"
else
    echo -e "${RED}❌ $FAILED_COUNT jobs ont échoué${NC}"
    echo "   → Voir: php artisan queue:failed"
    echo "   → Retry: php artisan queue:retry all"
fi
echo ""

# 4. Logs récents
echo "4️⃣  Dernières entrées du log worker..."
if [ -f "/var/www/samacite/storage/logs/queue-worker.log" ]; then
    tail -n 5 /var/www/samacite/storage/logs/queue-worker.log
else
    echo -e "${YELLOW}⚠️  Fichier de log non trouvé${NC}"
fi
echo ""

# 5. Notifications récentes
echo "5️⃣  Notifications créées (dernières 24h)..."
NOTIF_COUNT=$(php artisan tinker --execute="echo App\\Models\\User::first()->notifications()->where('created_at', '>', now()->subDay())->count();" 2>/dev/null | grep -o '[0-9]*' | tail -1)
echo "   📬 $NOTIF_COUNT notifications créées aujourd'hui"
echo ""

# Résumé
echo "=========================================="
echo "  Résumé"
echo "=========================================="
if systemctl is-active --quiet samacite-queue && [ "$JOBS_COUNT" -lt 10 ] && [ "$FAILED_COUNT" -eq 0 ]; then
    echo -e "${GREEN}✅ Tout fonctionne correctement !${NC}"
else
    echo -e "${YELLOW}⚠️  Vérifier les points ci-dessus${NC}"
fi
echo ""
