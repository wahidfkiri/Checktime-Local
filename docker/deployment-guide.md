# Guide de déploiement CheckTime sur Ubuntu

## Architecture Docker

```
┌─────────────────────────────────────────────────────┐
│                    Ubuntu Server                     │
│            IP fixe : 192.168.100.169                 │
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
- Accès Internet (pour télécharger Docker et les images)

## Installation rapide

> **Important** : Ubuntu Server minimal n'a **ni git ni curl** préinstallés.
> C'est la cause de l'erreur `git : commande introuvable` / `git: command not found`.
> L'étape 0 ci-dessous est donc obligatoire.

```bash
# ---- 0. Paquets de base (règle l'erreur "git: command not found") ----
sudo apt update
sudo apt install -y git curl wget nano unzip ca-certificates gnupg lsb-release openssl net-tools

# Vérifier que git répond maintenant
git --version

# ---- 1. Cloner le projet ----
sudo mkdir -p /opt/checktime
sudo git clone https://github.com/wahidfkiri/Checktime-Local /opt/checktime
cd /opt/checktime

# ---- 2. Lancer l'installation ----
sudo bash docker/install-ubuntu.sh
```

Le script demande l'adresse IP fixe du serveur (par défaut `192.168.100.169`).
Pour l'imposer sans question :

```bash
sudo IP_FIXE=192.168.100.169 bash docker/install-ubuntu.sh
```

Depuis cette version, le script installe lui-même `git` et les paquets de base
à son étape [1/9] — mais il faut quand même `git` pour cloner le dépôt **avant**
de pouvoir le lancer.

### Si le serveur n'a pas d'accès Internet (sans git)

Transférer le projet depuis votre PC au lieu de le cloner :

```bash
# Sur votre PC (Windows PowerShell ou Linux), depuis le dossier du projet
scp -r . utilisateur@192.168.100.169:/tmp/checktime

# Puis sur le serveur
sudo mkdir -p /opt/checktime
sudo cp -r /tmp/checktime/. /opt/checktime/
cd /opt/checktime
sudo bash docker/install-ubuntu.sh
```

Ou via une archive ZIP / clé USB :

```bash
sudo apt install -y unzip
unzip checktime.zip -d /opt/
sudo mv /opt/Checktime-Local /opt/checktime
cd /opt/checktime
sudo bash docker/install-ubuntu.sh
```

L'installation configurera automatiquement :
- Docker & Docker Compose
- L'IP fixe choisie
- Les conteneurs (bind sur l'IP choisie)
- Les migrations
- Un utilisateur admin

## Installation manuelle étape par étape

### 0. Paquets de base

À faire en premier sur une Ubuntu fraîchement installée :

```bash
sudo apt update
sudo apt upgrade -y
sudo apt install -y git curl wget nano unzip ca-certificates gnupg lsb-release openssl net-tools

# Vérifications
git --version
curl --version
```

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

# Vérifications
docker --version
docker compose version
sudo docker run --rm hello-world
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
        - 192.168.100.169/24
      routes:
        - to: default
          via: 192.168.100.1   # ← votre passerelle
      nameservers:
        addresses:
          - 8.8.8.8
          - 1.1.1.1
```

Appliquer :
```bash
sudo netplan apply

# Vérifications
ip -4 addr show          # l'IP doit apparaître sur l'interface
ip route show default    # la passerelle doit être présente
ping -c 3 8.8.8.8        # accès Internet
```

### 3. Déployer l'application

```bash
# Créer le répertoire
sudo mkdir -p /opt/checktime
cd /opt/checktime

# Copier les fichiers du projet (via git, scp, clé USB...)
# git doit être installé — voir l'étape 0 si "git: command not found"
sudo git clone https://github.com/wahidfkiri/Checktime-Local .

# Dépôt privé ? utiliser un token d'accès personnel GitHub :
# sudo git clone https://<TOKEN>@github.com/wahidfkiri/Checktime-Local .

# Configuration
sudo cp .env.example .env
sudo nano .env   # Modifier les paramètres si nécessaire
```

**.env minimum :**
```env
APP_NAME=CheckTime
APP_ENV=production
APP_DEBUG=false
APP_URL=http://192.168.100.169
APP_HOST_IP=192.168.100.169

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=checktime
DB_USERNAME=checktime_user
DB_PASSWORD=P@ssw0rd

QUEUE_CONNECTION=database
SESSION_DRIVER=file
```

### Changer l'IP du serveur plus tard

`APP_HOST_IP` est relue par `docker-compose.yml` à chaque démarrage. Pour
changer d'IP, utilisez le script dédié :

```bash
cd /opt/checktime
sudo bash docker/change-ip.sh 192.168.1.50
```

Il met à jour `.env`, la configuration netplan, redémarre les conteneurs et
régénère le cache de configuration Laravel. À la main, cela revient à modifier
`APP_URL` et `APP_HOST_IP` dans le `.env`, puis `sudo docker compose up -d`.

### 4. Créer les répertoires persistants

```bash
cd /opt/checktime
sudo mkdir -p storage/app/public
sudo mkdir -p storage/framework/cache/data
sudo mkdir -p storage/framework/sessions
sudo mkdir -p storage/framework/views
sudo mkdir -p storage/logs
sudo mkdir -p bootstrap/cache
sudo mkdir -p public/storage
sudo chmod -R 775 storage bootstrap/cache public/storage
```

### 5. Lancer les conteneurs

```bash
export DB_PASSWORD="P@ssw0rd"
export MYSQL_ROOT_PASSWORD="RootChange123!"

# Avec docker compose (plugin)
sudo docker compose up -d --build

# Ou avec docker-compose (standalone)
sudo docker-compose up -d --build

# Vérifier que les 3 conteneurs tournent
sudo docker compose ps
sudo docker ps
```

### 6. Commandes Laravel

```bash
# Attendre que MySQL soit prêt
sleep 15

# Vérifier la connexion à la base
docker exec checktime-app php artisan db:show

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

### 7. Créer l'admin

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

### 8. Vérification finale

```bash
# Les conteneurs sont-ils "Up" / "healthy" ?
sudo docker compose ps

# L'application répond-elle en local ?
curl -I http://localhost

# Et depuis l'IP fixe ?
curl -I http://192.168.100.169

# Les ports sont-ils bien ouverts ?
sudo netstat -tlnp | grep -E ':80|:3306|:8080'

# Aucune erreur dans les logs ?
sudo docker compose logs --tail=50 app
```

Puis, depuis un poste du réseau : `http://192.168.100.169`
(identifiants `admin@checktime.local` / `admin123` — **à changer**).

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
   sudo ufw allow from 192.168.100.0/24 to any port 80
   sudo ufw allow from 192.168.100.0/24 to any port 443
   sudo ufw allow from 192.168.100.0/24 to any port 8080  # phpMyAdmin (optionnel)
   sudo ufw enable
   ```
4. **Désactiver phpMyAdmin** en production en omettant `--profile admin`
5. **Configurer le pare-feu UFW** pour n'autoriser que le réseau local

## Dépannage

| Problème | Solution |
|----------|----------|
| `git: command not found` / `git : commande introuvable` | `sudo apt update && sudo apt install -y git` (voir étape 0) |
| `curl: command not found` | `sudo apt install -y curl` |
| `docker: command not found` | Docker pas installé — refaire l'étape 1 |
| `docker compose` inconnu mais `docker` OK | `sudo apt install -y docker-compose-plugin` |
| `permission denied` sur le socket Docker | Préfixer par `sudo`, ou `sudo usermod -aG docker $USER` puis se reconnecter |
| `Unable to locate package` | `sudo apt update` d'abord ; vérifier l'accès Internet (`ping 8.8.8.8`) |
| `failed to resolve reference "docker.io/..."` <br> `server misbehaving` | DNS cassé. `ping 8.8.8.8`, `resolvectl status`. Souvent causé par une config netplan inadaptée : `sudo rm /etc/netplan/01-checktime-static.yaml && sudo netplan apply` |
| `Permissions for ... are too open` | `sudo chmod 600 /etc/netplan/01-checktime-static.yaml` |
| `systemd-networkd is not running` | Le système utilise NetworkManager, pas networkd — configurer l'IP avec `nmcli` (voir ci-dessous) |
| Interface en `wl...` (Wi-Fi) | netplan `ethernets` ne s'applique pas au Wi-Fi — utiliser `nmcli` (voir ci-dessous) |
| Port déjà utilisé | `sudo netstat -tlnp \| grep :80` puis tuer le processus |
| Permission denied | `sudo chown -R www-data:www-data storage/ bootstrap/cache/` |
| SQLSTATE[HY000] [2002] | MySQL pas encore prêt — attendre 15s après démarrage |
| APP_KEY manquant | `docker exec checktime-app php artisan key:generate --force` |
| Page blanche 500 | `docker compose logs app` pour voir l'erreur |

### IP fixe en Wi-Fi ou sous NetworkManager (Ubuntu Desktop)

netplan avec `renderer: networkd` et un bloc `ethernets:` ne fonctionne **ni sur
une carte Wi-Fi** (`wlan0`, `wlx…`) **ni sur un système géré par NetworkManager**.
Appliquer la config dans ces conditions casse la résolution DNS.

```bash
# 1. Retirer une éventuelle config netplan inadaptée
sudo rm -f /etc/netplan/01-checktime-static.yaml
sudo netplan apply

# 2. Identifier la connexion
nmcli connection show

# 3. Fixer l'IP (remplacer <NomDeLaConnexion>)
sudo nmcli connection modify "<NomDeLaConnexion>" \
  ipv4.method manual \
  ipv4.addresses 192.168.100.169/24 \
  ipv4.gateway 192.168.100.1 \
  ipv4.dns "8.8.8.8,1.1.1.1"

sudo nmcli connection down "<NomDeLaConnexion>"
sudo nmcli connection up   "<NomDeLaConnexion>"

# 4. Vérifier
ip -4 addr show
ping -c 3 8.8.8.8
getent hosts registry-1.docker.io   # le DNS doit répondre
```

## Annexe : installation complète en un seul bloc

À copier-coller sur un Ubuntu Server vierge, en une fois :

```bash
# 0. Paquets de base
sudo apt update && sudo apt upgrade -y
sudo apt install -y git curl wget nano unzip ca-certificates gnupg lsb-release openssl net-tools

# 1. Cloner le projet
sudo mkdir -p /opt/checktime
sudo git clone https://github.com/wahidfkiri/Checktime-Local /opt/checktime
cd /opt/checktime

# 2. Installation automatique (Docker + IP fixe + conteneurs + migrations + admin)
sudo IP_FIXE=192.168.100.169 bash docker/install-ubuntu.sh

# 3. Vérifier
sudo docker compose ps
curl -I http://192.168.100.169
```
