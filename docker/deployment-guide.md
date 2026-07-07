# Guide de déploiement CheckTime sur Ubuntu

## Architecture Docker

```
┌─────────────────────────────────────────────────────┐
│                    Ubuntu Server                     │
│              IP fixe : 192.168.1.100                 │
│                                                      │
│  ┌──────────────────┐   ┌──────────────────┐         │
│  │   checktime-app   │   │  checktime-mysql  │        │
│  │   (Nginx + PHP)   │   │     (MySQL 8)     │        │
│  │   Ports : 80/443  │   │   Port : 3306     │        │
│  └────────┬─────────┘   └────────┬─────────┘         │
│           │                      │                    │
│           └──────────┬───────────┘                    │
│                 Réseau Docker                         │
│                                                      │
│  ┌──────────────────┐                                │
│  │  checktime-phpma │  (optionnel, profil admin)     │
│  │  Port : 8080     │                                │
│  └──────────────────┘                                │
└─────────────────────────────────────────────────────┘
```

## Prérequis

- Ubuntu 22.04 LTS ou 24.04 LTS
- 2 Go RAM minimum (4 Go recommandé)
- 10 Go d'espace disque
- Accès root (sudo)

## Installation rapide

```bash
# 1. Cloner le projet
sudo mkdir -p /opt/checktime
sudo git clone https://github.com/wahidfkiri/Checktime-Local /opt/checktime
cd /opt/checktime

# 2. Lancer l'installation
sudo bash docker/install-ubuntu.sh
```

Le script vous demandera l'adresse IP fixe du serveur (ou utilise l'IP actuelle par défaut).

L'installation configurera automatiquement :
- Docker & Docker Compose
- L'IP fixe choisie
- Les conteneurs (bind sur l'IP choisie)
- Les migrations
- Un utilisateur admin

## Installation manuelle étape par étape

### 1. Installer Docker

```bash
sudo apt-get update
sudo apt-get install -y ca-certificates curl gnupg lsb-release

# Ajouter le dépôt Docker officiel
sudo mkdir -p /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | \
  sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg

echo "deb [arch=$(dpkg --print-architecture) \
  signed-by=/etc/apt/keyrings/docker.gpg] \
  https://download.docker.com/linux/ubuntu \
  $(lsb_release -cs) stable" | \
  sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

sudo apt-get update
sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
sudo systemctl enable docker
sudo systemctl start docker
```

### 2. Configurer l'IP fixe

```bash
# Identifier l'interface réseau
ip route show default

# Éditer netplan (exemple pour interface ens33)
sudo nano /etc/netplan/00-installer-config.yaml
```

Contenu du fichier netplan :
```yaml
network:
  version: 2
  renderer: networkd
  ethernets:
    ens33:   # ← adapter votre interface
      addresses:
        - 192.168.1.100/24
      routes:
        - to: default
          via: 192.168.1.1   # ← votre passerelle
      nameservers:
        addresses:
          - 8.8.8.8
          - 1.1.1.1
```

Appliquer :
```bash
sudo netplan apply
```

### 3. Déployer l'application

```bash
# Créer le répertoire
sudo mkdir -p /opt/checktime
cd /opt/checktime

# Copier les fichiers du projet (via git, scp, clé USB...)
git clone https://github.com/wahidfkiri/Checktime-Local .

# Configuration
cp .env.example .env
nano .env   # Modifier les paramètres si nécessaire
```

**.env minimum :**
```env
APP_NAME=CheckTime
APP_ENV=production
APP_DEBUG=false
APP_URL=http://192.168.1.100

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=checktime
DB_USERNAME=checktime_user
DB_PASSWORD=P@ssw0rd

QUEUE_CONNECTION=database
SESSION_DRIVER=file
```

### 4. Lancer les conteneurs

```bash
export DB_PASSWORD="P@ssw0rd"
export MYSQL_ROOT_PASSWORD="RootChange123!"

# Avec docker compose (plugin)
docker compose up -d --build

# Ou avec docker-compose (standalone)
docker-compose up -d --build
```

### 5. Commandes Laravel

```bash
# Attendre que MySQL soit prêt
sleep 15

# Générer la clé d'application
docker exec checktime-app php artisan key:generate --force

# Exécuter les migrations
docker exec checktime-app php artisan migrate --force

# Créer le lien de stockage
docker exec checktime-app php artisan storage:link --force

# Optimiser Laravel
docker exec checktime-app php artisan config:cache
docker exec checktime-app php artisan route:cache
docker exec checktime-app php artisan view:cache
```

### 6. Créer l'admin

```bash
docker exec -it checktime-app php artisan tinker
```

Dans Tinker :
```php
$user = \App\Models\User::create([
    'name' => 'Administrateur',
    'email' => 'admin@checktime.local',
    'password' => bcrypt('admin123'),
]);

$user->assignRole('admin');
```

## Mise à jour de l'application

```bash
cd /opt/checktime

# Récupérer les dernières modifications
git pull

# Rebuild et redémarrer
docker compose up -d --build

# Migrations si nécessaire
docker exec checktime-app php artisan migrate --force

# Vider le cache
docker exec checktime-app php artisan optimize:clear
docker exec checktime-app php artisan config:cache
docker exec checktime-app php artisan route:cache
docker exec checktime-app php artisan view:cache
```

## Logs et maintenance

```bash
# Logs de l'application
docker compose logs -f app

# Logs de la base de données
docker compose logs -f mysql

# Shell dans le conteneur
docker exec -it checktime-app bash

# Redémarrer un service
docker compose restart app

# Arrêter tous les conteneurs
docker compose down

# Supprimer les volumes (⚠️ supprime les données)
docker compose down -v
```

## Sauvegarde

```bash
# Backup de la base de données
docker exec checktime-mysql mysqldump -u root -p checktime > backup_$(date +%Y%m%d).sql

# Restore
cat backup.sql | docker exec -i checktime-mysql mysql -u root -p checktime
```

## Sécurité

1. **Modifier les mots de passe** dans le .env avant déploiement
2. **Activer HTTPS** avec Certbot ou un reverse proxy (Nginx Proxy Manager)
3. **Limiter l'accès** à phpMyAdmin (port 8080) avec un pare-feu :
   ```bash
   sudo ufw allow from 192.168.1.0/24 to any port 80
   sudo ufw allow from 192.168.1.0/24 to any port 443
   sudo ufw allow from 192.168.1.0/24 to any port 8080  # phpMyAdmin (optionnel)
   sudo ufw enable
   ```
4. **Désactiver phpMyAdmin** en production en omettant `--profile admin`
5. **Configurer le pare-feu UFW** pour n'autoriser que le réseau local

## Dépannage

| Problème | Solution |
|----------|----------|
| Port déjà utilisé | `sudo netstat -tlnp \| grep :80` puis tuer le processus |
| Permission denied | `sudo chown -R www-data:www-data storage/ bootstrap/cache/` |
| SQLSTATE[HY000] [2002] | MySQL pas encore prêt — attendre 15s après démarrage |
| APP_KEY manquant | `docker exec checktime-app php artisan key:generate --force` |
| Page blanche 500 | `docker compose logs app` pour voir l'erreur |
