# 📋 Guide de Déploiement - Gestion des Queues

## 🎯 Vue d'ensemble

SamaCité utilise des **jobs en queue** pour gérer les notifications de manière asynchrone. Cette configuration optimise les performances et évite de bloquer les requêtes API.

---

## 🔧 Configuration par environnement

### **Développement Local** (actuel)

Dans `.env` :
```env
QUEUE_CONNECTION=sync
```

✅ **Avantages** :
- Jobs exécutés immédiatement
- Pas besoin de worker séparé
- Idéal pour les tests et le développement
- Débogage facile

❌ **Inconvénients** :
- Requêtes API légèrement plus lentes
- Pas de retry automatique en cas d'échec

---

### **Production** (recommandé)

Dans `.env` de production :
```env
QUEUE_CONNECTION=database
```

✅ **Avantages** :
- Meilleure performance API (réponse immédiate)
- Retry automatique des jobs échoués
- Gestion de charge optimisée
- Monitoring des jobs

⚠️ **Nécessite** :
- Un worker queue actif en permanence
- Supervision du processus (Supervisor/PM2/systemd)

---

## 🚀 Configuration Production - Worker Queue

### Option 1 : Supervisor (Linux - Recommandé)

1. **Installer Supervisor** :
```bash
sudo apt-get install supervisor
```

2. **Créer le fichier de configuration** `/etc/supervisor/conf.d/samacite-worker.conf` :
```ini
[program:samacite-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/samacite/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/samacite/storage/logs/worker.log
stopwaitsecs=3600
```

3. **Démarrer le worker** :
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start samacite-worker:*
```

4. **Vérifier le statut** :
```bash
sudo supervisorctl status samacite-worker:*
```

---

### Option 2 : systemd (Linux - Alternative)

1. **Créer le service** `/etc/systemd/system/samacite-queue.service` :
```ini
[Unit]
Description=SamaCité Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/samacite
ExecStart=/usr/bin/php /path/to/samacite/artisan queue:work --sleep=3 --tries=3
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

2. **Activer et démarrer** :
```bash
sudo systemctl enable samacite-queue
sudo systemctl start samacite-queue
sudo systemctl status samacite-queue
```

---

### Option 3 : PM2 (Node.js - Alternative moderne)

```bash
# Installer PM2 globalement
npm install pm2 -g

# Créer ecosystem.config.js
cat > ecosystem.config.js << 'EOF'
module.exports = {
  apps: [{
    name: 'samacite-queue',
    script: 'php',
    args: 'artisan queue:work --sleep=3 --tries=3 --max-time=3600',
    instances: 2,
    autorestart: true,
    watch: false,
    max_memory_restart: '200M',
  }]
}
EOF

# Démarrer
pm2 start ecosystem.config.js
pm2 save
pm2 startup
```

---

### Option 4 : Docker (Containers)

**docker-compose.yml** :
```yaml
services:
  app:
    # ... votre config Laravel
  
  queue-worker:
    image: your-laravel-image
    command: php artisan queue:work --sleep=3 --tries=3 --max-time=3600
    restart: unless-stopped
    depends_on:
      - app
    environment:
      - QUEUE_CONNECTION=database
    volumes:
      - ./storage:/app/storage
```

---

## 📊 Monitoring & Maintenance

### Commandes utiles

```bash
# Voir les jobs en attente
php artisan queue:monitor database

# Voir les jobs échoués
php artisan queue:failed

# Réessayer tous les jobs échoués
php artisan queue:retry all

# Réessayer un job spécifique
php artisan queue:retry {job-id}

# Vider la queue
php artisan queue:clear

# Vider les jobs échoués
php artisan queue:flush

# Redémarrer tous les workers (sans downtime)
php artisan queue:restart
```

---

## 🔍 Diagnostic des problèmes

### Problème : Les notifications ne sont pas envoyées

**Vérifications** :
1. Vérifier la configuration queue :
```bash
php artisan config:cache
php artisan queue:monitor database
```

2. Vérifier les logs :
```bash
tail -f storage/logs/laravel.log
tail -f storage/logs/worker.log  # Si Supervisor
```

3. Tester la création de job :
```bash
php check_notifications.php
```

4. Vérifier le worker :
```bash
# Supervisor
sudo supervisorctl status

# systemd
sudo systemctl status samacite-queue

# PM2
pm2 status
```

### Problème : Jobs échouent en permanence

```bash
# Voir les détails des échecs
php artisan queue:failed

# Augmenter le nombre de tentatives
php artisan queue:work --tries=5

# Vérifier les logs pour l'erreur exacte
tail -f storage/logs/laravel.log
```

---

## 🎯 Checklist de Déploiement

### Avant de déployer :

- [ ] `.env` configuré avec `QUEUE_CONNECTION=database`
- [ ] Table `jobs` créée (migration `0001_01_01_000002_create_jobs_table.php`)
- [ ] Worker configuré (Supervisor/systemd/PM2/Docker)
- [ ] Logs accessibles et surveillés
- [ ] Tests des notifications effectués

### Après déploiement :

- [ ] Vérifier que le worker tourne : `php artisan queue:monitor`
- [ ] Créer un signalement de test
- [ ] Vérifier les notifications admin : `php check_notifications.php`
- [ ] Surveiller les logs : `tail -f storage/logs/laravel.log`
- [ ] Configurer alertes en cas de jobs échoués

---

## 📈 Performance & Scalabilité

### Optimisations recommandées

1. **Utiliser Redis** (plus performant que database) :
```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

2. **Augmenter le nombre de workers** selon la charge :
```bash
# Supervisor : modifier numprocs dans la config
numprocs=4  # 4 workers parallèles
```

3. **Séparer les queues par priorité** :
```bash
# High priority (notifications urgentes)
php artisan queue:work --queue=high,default

# Low priority (stats, rapports)
php artisan queue:work --queue=low
```

---

## 🆘 Support

Pour toute question sur les queues :
- 📖 Documentation Laravel : https://laravel.com/docs/queues
- 🔍 Vérifier les logs : `storage/logs/laravel.log`
- 🛠️ Script diagnostic : `php check_notifications.php`

---

**Dernière mise à jour** : 5 décembre 2025
**Version** : SamaCité v1.0
