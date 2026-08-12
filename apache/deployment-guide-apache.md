# Guide de déploiement CheckTime — Installation native (sans Docker), Apache

Ce guide installe CheckTime **directement sur le système** Ubuntu — sans
Docker — avec **Apache** comme serveur web (au lieu de Nginx). Même principe
que le guide Docker : mêmes étapes numérotées, mêmes points de vérification,
mêmes pièges corrigés (`git` absent, DNS cassé par une mauvaise config
réseau, Wi-Fi vs Ethernet).

## Architecture

```
┌───────────────────────────────────────────────────────────┐
│                      Ubuntu Server                         │
│              IP fixe : 192.168.100.169                     │
│                                                             │
│   Apache 2  ──(mod_php)──  PHP (natif Ubuntu)  ──  MySQL 8 │
│   Port 80                                        Port 3306 │
│                                                             │
│   phpMyAdmin : http://<IP>/phpmyadmin (même vhost)         │
│                                                             │
│   Supervisor : worker de file d'attente (queue:work)       │
│   Cron       : planificateur Laravel (schedule:run)        │
└───────────────────────────────────────────────────────────┘
```

Rien ne tourne en conteneur : Apache, PHP, MySQL, le worker de file d'attente
et le planificateur sont des services système classiques (`systemctl`,
`supervisorctl`, `crontab`).

## Prérequis

- Ubuntu 22.04 LTS ou 24.04 LTS
- 2 Go RAM minimum (4 Go recommandé)
- 10 Go d'espace disque
- Accès root (sudo)
- Accès Internet (paquets Ubuntu, dépôt PHP, Node.js, Composer)

> **Point d'attention** : une installation Ubuntu Server minimale ne contient
> ni `git` ni `curl`. Sans eux, le clonage du dépôt échoue avec
> `git : commande introuvable`. La première étape ci-dessous les installe.

## Installation rapide

```bash
# 0. Paquets de base (obligatoire, règle "git: command not found")
sudo apt update
sudo apt install -y git curl wget nano unzip ca-certificates gnupg lsb-release \
    software-properties-common openssl net-tools

# 1. Cloner le projet
sudo mkdir -p /var/www/checktime
sudo git clone https://github.com/wahidfkiri/Checktime-Local /var/www/checktime
cd /var/www/checktime

# 2. Lancer l'installation automatique
sudo bash apache/install-ubuntu-apache.sh
```

Le script demande l'adresse IP fixe (par défaut `192.168.100.169`). Pour
l'imposer sans question :

```bash
sudo IP_FIXE=192.168.100.169 bash apache/install-ubuntu-apache.sh
```

Il installe et configure, dans l'ordre : Apache, PHP + extensions,
Composer, Node.js, MySQL, le clonage du projet, le `.env`, les dépendances
PHP/JS, les migrations, le compte admin, le VirtualHost Apache, le worker de
file d'attente (Supervisor) et le planificateur (cron).

### Si le serveur n'a pas d'accès Internet (sans git)

```bash
# Sur votre PC, depuis le dossier du projet
scp -r . utilisateur@192.168.100.169:/tmp/checktime

# Sur le serveur
sudo mkdir -p /var/www/checktime
sudo cp -r /tmp/checktime/. /var/www/checktime/
cd /var/www/checktime
sudo bash apache/install-ubuntu-apache.sh
```

## Installation manuelle étape par étape

### 0. Paquets de base

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y git curl wget nano unzip ca-certificates gnupg lsb-release \
    software-properties-common openssl net-tools

git --version
curl --version
```

### 1. Configurer l'IP fixe

```bash
ip route show default
sudo nano /etc/netplan/01-checktime-static.yaml
```

Contenu (adapter l'interface et la passerelle) :
```yaml
network:
  version: 2
  renderer: networkd
  ethernets:
    ens33:                    # <- votre interface
      addresses:
        - 192.168.100.169/24
      routes:
        - to: default
          via: 192.168.100.1  # <- votre passerelle
      nameservers:
        addresses:
          - 8.8.8.8
          - 1.1.1.1
```

```bash
sudo chmod 600 /etc/netplan/01-checktime-static.yaml
sudo netplan apply

ip -4 addr show
ping -c 3 8.8.8.8
```

> **Wi-Fi ou Ubuntu Desktop** : cette configuration netplan ne fonctionne ni
> sur une carte Wi-Fi (`wlan0`, `wlx…`) ni sur un système géré par
> NetworkManager — l'appliquer casse la résolution DNS. Voir la section
> [IP fixe en Wi-Fi](#ip-fixe-en-wi-fi-ou-sous-networkmanager) ci-dessous.

### 2. Installer Apache

```bash
sudo apt install -y apache2
sudo a2enmod rewrite headers expires
sudo systemctl enable apache2
sudo systemctl start apache2

# Vérification
systemctl status apache2 --no-pager
curl -I http://localhost
```

### 3. Installer PHP et ses extensions

CheckTime exige seulement PHP `^8.0.2` : on installe la version fournie
nativement par Ubuntu (8.1 sur 22.04, 8.3 sur 24.04), **sans dépôt tiers**.
Les paquets sans numéro de version (`php-mysql`, `php-gd`...) résolvent
automatiquement vers la bonne version.

> Pas de PPA `ondrej/php` ici volontairement : un dépôt tiers ajoute un point
> de défaillance (réseau, GPG, décalage de version avec une nouvelle release
> Ubuntu) pour un gain nul, puisque la version native suffit.

> Pas de `php-opcache` non plus : OPcache est déjà fourni et activé par
> défaut avec `php-common` (`mods-available/opcache.ini`), et ce paquet
> séparé n'existe pas sous ce nom sur toutes les releases Ubuntu.

```bash
sudo apt install -y \
    php libapache2-mod-php php-cli php-common \
    php-mysql php-mbstring php-xml php-bcmath \
    php-gd php-zip php-intl php-curl

PHP_VERSION=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
sudo a2dismod mpm_event 2>/dev/null || true
sudo a2enmod mpm_prefork
sudo a2enmod "php${PHP_VERSION}"
sudo systemctl restart apache2

# Vérification
php -v
php -m | grep -E "pdo_mysql|mbstring|gd|zip|intl|bcmath|Zend OPcache"
```

### 4. Installer Composer

```bash
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
composer --version
```

### 5. Installer Node.js (build des assets front)

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo bash -
sudo apt install -y nodejs
node -v
npm -v
```

### 6. Installer et configurer MySQL

```bash
sudo apt install -y mysql-server
sudo systemctl enable mysql
sudo systemctl start mysql

sudo mysql --user=root <<'SQL'
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'RootChange123!';
CREATE DATABASE checktime CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'checktime_user'@'localhost' IDENTIFIED BY 'P@ssw0rd';
GRANT ALL PRIVILEGES ON checktime.* TO 'checktime_user'@'localhost';
FLUSH PRIVILEGES;
SQL

# Vérification
mysql -u checktime_user -p'P@ssw0rd' -e "SHOW DATABASES;"
```

### 7. Installer phpMyAdmin

```bash
sudo apt install -y phpmyadmin
```

L'assistant demande, dans l'ordre :
1. Serveur web à configurer automatiquement → cocher **apache2** (barre espace, puis Entrée)
2. Configurer une base de données avec `dbconfig-common` → **Oui**
3. Mot de passe de l'application phpMyAdmin → laisser vide pour générer automatiquement, ou en saisir un
4. Mot de passe root MySQL → celui défini à l'étape 6

```bash
sudo a2enconf phpmyadmin
sudo systemctl reload apache2

# Vérification
curl -I http://localhost/phpmyadmin
```

Accessible ensuite sur `http://192.168.100.169/phpmyadmin`, avec l'utilisateur
`root` et le mot de passe MySQL root défini à l'étape 6.

> **Installation non interactive** (pour un script automatisé) : préremplir
> les réponses avec `debconf-set-selections` avant `apt install` — c'est ce
> que fait `install-ubuntu-apache.sh`. Voir l'annexe du script pour le détail.

### 8. Déployer l'application

```bash
sudo mkdir -p /var/www/checktime
cd /var/www/checktime
sudo git clone https://github.com/wahidfkiri/Checktime-Local .

sudo cp .env.example .env
sudo nano .env
```

**.env minimum :**
```env
APP_NAME=CheckTime
APP_ENV=production
APP_DEBUG=false
APP_URL=http://192.168.100.169

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=checktime
DB_USERNAME=checktime_user
DB_PASSWORD=P@ssw0rd

QUEUE_CONNECTION=database
SESSION_DRIVER=file
```

### 9. Créer les répertoires, installer les dépendances

```bash
cd /var/www/checktime
sudo mkdir -p storage/app/public storage/framework/{cache/data,sessions,views} \
    storage/logs bootstrap/cache public/storage public/uploads

sudo composer install --no-dev --optimize-autoloader --no-interaction

sudo npm install --no-audit --no-fund
sudo npm run build
sudo rm -rf node_modules

# Apache tourne sous l'utilisateur www-data
sudo chown -R www-data:www-data /var/www/checktime
sudo chmod -R 775 storage bootstrap/cache
```

### 10. Commandes Laravel

```bash
sudo -u www-data php artisan key:generate --force
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan storage:link --force
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
```

### 11. Créer le compte administrateur

```bash
sudo -u www-data php artisan tinker
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

### 12. Configurer le VirtualHost Apache

```bash
sudo nano /etc/apache2/sites-available/checktime.conf
```

```apache
<VirtualHost *:80>
    ServerName 192.168.100.169
    DocumentRoot /var/www/checktime/public

    <Directory /var/www/checktime/public>
        Options -MultiViews +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    <FilesMatch "^\.env|\.git|composer\.(json|lock)|artisan$">
        Require all denied
    </FilesMatch>

    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"

    ErrorLog ${APACHE_LOG_DIR}/checktime-error.log
    CustomLog ${APACHE_LOG_DIR}/checktime-access.log combined
</VirtualHost>
```

> `+FollowSymLinks` est indispensable : `storage:link` crée un lien
> symbolique dans `public/`, qu'Apache ignore par défaut.

```bash
sudo a2ensite checktime.conf
sudo a2dissite 000-default.conf
sudo apache2ctl configtest
sudo systemctl reload apache2
```

### 13. Worker de file d'attente (Supervisor)

```bash
sudo apt install -y supervisor
sudo nano /etc/supervisor/conf.d/checktime-queue.conf
```

```ini
[program:checktime-queue]
command=php /var/www/checktime/artisan queue:work --queue=zones,zones-high,default --sleep=3 --tries=3 --timeout=300
directory=/var/www/checktime
autostart=true
autorestart=true
user=www-data
numprocs=2
process_name=%(program_name)s_%(process_num)02d
stdout_logfile=/var/log/supervisor/checktime-queue.log
stderr_logfile=/var/log/supervisor/checktime-queue.err.log
```

```bash
sudo systemctl enable supervisor
sudo systemctl restart supervisor
sudo supervisorctl reread && sudo supervisorctl update
sudo supervisorctl status
```

### 14. Planificateur (cron)

```bash
sudo crontab -u www-data -e
```

Ajouter la ligne standard recommandée par Laravel :
```
* * * * * cd /var/www/checktime && php artisan schedule:run >> /dev/null 2>&1
```

### 15. Vérification finale

```bash
sudo systemctl status apache2 mysql supervisor --no-pager
curl -I http://localhost
curl -I http://192.168.100.169
curl -I http://192.168.100.169/phpmyadmin
sudo tail -n 50 /var/log/apache2/checktime-error.log
sudo tail -n 50 storage/logs/laravel.log
```

Depuis un poste du réseau : `http://192.168.100.169`
(identifiants `admin@checktime.local` / `admin123` — **à changer**).

## Changer l'adresse IP du serveur

### Méthode 1 — Script dédié (recommandé)

```bash
cd /var/www/checktime
sudo bash apache/change-ip-apache.sh 192.168.1.50
```

Met à jour `.env`, netplan, le `ServerName` du VirtualHost Apache, et
régénère le cache Laravel.

### Méthode 2 — Manuellement

```bash
cd /var/www/checktime
sudo nano .env                                   # APP_URL=http://<nouvelle IP>
sudo nano /etc/apache2/sites-available/checktime.conf   # ServerName <nouvelle IP>
sudo systemctl reload apache2
sudo -u www-data php artisan config:cache
```

### L'IP a changé toute seule (DHCP / changement de réseau)

Si le serveur n'a pas d'IP fixe configurée et qu'il a simplement reçu une
nouvelle adresse par DHCP (branché sur un autre routeur, changement de
box…), **ne touchez pas au réseau** — il faut seulement faire suivre
l'application à la nouvelle adresse.

**1. Trouver la nouvelle IP**
```bash
hostname -I
# ou
ip -4 addr show
```

**2. Mettre à jour l'application**

Le script `change-ip-apache.sh` gère déjà ce cas : s'il ne trouve pas de
fichier netplan statique, il ne touche pas au réseau et se contente de
mettre à jour l'application.
```bash
cd /var/www/checktime
sudo bash apache/change-ip-apache.sh 192.168.1.50
```

Ou à la main :
```bash
cd /var/www/checktime
sudo nano .env                                          # APP_URL=http://<nouvelle IP>
sudo nano /etc/apache2/sites-available/checktime.conf   # ServerName <nouvelle IP>
sudo apache2ctl configtest
sudo systemctl reload apache2
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan config:cache
```

**3. Vérifier**
```bash
curl -I http://<nouvelle IP>
```

**Pour éviter que ça se reproduise à chaque changement de réseau**, deux
solutions durables :
- **Réservation DHCP** sur le routeur/box (par adresse MAC du serveur) — le
  serveur garde toujours la même IP sur ce réseau, sans rien configurer côté
  Ubuntu.
- **IP statique** sur le serveur (voir l'étape 1 de l'installation manuelle
  ci-dessus) — fixe l'IP définitivement, mais elle ne suivra plus
  automatiquement un changement de réseau : il faudra la refixer à la main.

### IP fixe en Wi-Fi ou sous NetworkManager

netplan avec `renderer: networkd` et un bloc `ethernets:` ne fonctionne ni
sur une carte Wi-Fi (`wlan0`, `wlx…`) ni sur un système géré par
NetworkManager. L'appliquer casse la résolution DNS, et l'installation
échoue avec des erreurs de type « impossible de résoudre `deb.nodesource.com`
/ `repo.packagist.org` ».

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
```

## Maintenance

### Mise à jour de l'application

```bash
cd /var/www/checktime
sudo git pull

sudo -u www-data composer install --no-dev --optimize-autoloader --no-interaction
sudo npm install --no-audit --no-fund && sudo npm run build && sudo rm -rf node_modules

sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

sudo systemctl reload apache2
sudo supervisorctl restart checktime-queue:*
```

### Commandes courantes

```bash
sudo tail -f /var/log/apache2/checktime-error.log   # Logs Apache
sudo tail -f /var/www/checktime/storage/logs/laravel.log # Logs Laravel
sudo systemctl restart apache2                       # Redémarrer Apache
sudo systemctl restart mysql                         # Redémarrer MySQL
sudo supervisorctl status                            # État du worker de file d'attente
sudo supervisorctl restart checktime-queue:*          # Redémarrer le worker
sudo crontab -u www-data -l                           # Voir le planificateur
```

### Sauvegarde et restauration

```bash
# Sauvegarde
mysqldump -u root -p checktime > backup_$(date +%Y%m%d).sql

# Restauration
mysql -u root -p checktime < backup.sql
```

## Sécurité

1. Changer le mot de passe du compte `admin@checktime.local` dès la première connexion
2. Modifier `DB_PASSWORD` et le mot de passe root MySQL avant la mise en production
3. Activer HTTPS avec Certbot (`sudo apt install certbot python3-certbot-apache`)
4. Limiter l'accès réseau avec UFW :
   ```bash
   sudo ufw allow from 192.168.100.0/24 to any port 80
   sudo ufw allow from 192.168.100.0/24 to any port 443
   sudo ufw enable   # sans cette commande, les règles ci-dessus n'ont aucun effet
   sudo ufw status
   ```
5. Vérifier que `APP_DEBUG=false` en production (une page d'erreur Laravel en
   debug expose la configuration, y compris les identifiants de base de données)
6. **Restreindre l'accès à phpMyAdmin**, accessible par défaut à quiconque
   atteint le serveur sur le réseau. Deux options :
   - Le désinstaller si non utilisé au quotidien : `sudo apt remove phpmyadmin`
   - Ou restreindre l'accès par IP dans le VirtualHost :
     ```apache
     <Directory /usr/share/phpmyadmin>
         Require ip 192.168.100.0/24
     </Directory>
     ```
     à ajouter dans `/etc/apache2/sites-available/checktime.conf`, puis
     `sudo systemctl reload apache2`.

## Dépannage

| Problème | Solution |
|---|---|
| `git : commande introuvable` | `sudo apt update && sudo apt install -y git` |
| `Impossible de trouver le paquet php8.2` | Ce guide n'installe plus une version figée de PHP (voir étape 3) — utiliser les paquets sans numéro de version (`php`, `php-mysql`...) qui suivent la version native d'Ubuntu |
| `php-opcache : pas de version susceptible d'être installée` | Normal, ne pas l'installer séparément — déjà fourni et activé avec `php-common` (voir étape 3) |
| `composer install` échoue : `were not loaded, because they are affected by security advisories` | Composer récent (≥ 2.9) bloque par défaut l'installation de paquets ayant des failles connues. `laravel/framework` 9.x est en fin de support et n'a aucune version indemne. Déjà réglé dans `composer.json` (`config.policy.advisories.block: false`) — `git pull` si l'erreur persiste sur un checkout ancien |
| `curl: Failed to connect ... port 80` | Voir [ci-dessous](#curl-narrive-pas-a-se-connecter) |
| Apache démarre mais page blanche | `sudo tail -f /var/log/apache2/checktime-error.log` et `storage/logs/laravel.log` |
| Erreur 403 Forbidden | Vérifier `DocumentRoot` et `Require all granted` dans le VirtualHost ; `sudo chown -R www-data:www-data /var/www/checktime` |
| `/storage/...` renvoie 404 | `+FollowSymLinks` manquant dans le `<Directory>`, ou lien `php artisan storage:link` non créé |
| `.htaccess` ignoré (URLs `?page=` visibles) | `sudo a2enmod rewrite` puis vérifier `AllowOverride All` |
| `http://<IP>/phpmyadmin` renvoie 404 | `sudo a2enconf phpmyadmin && sudo systemctl reload apache2` |
| phpMyAdmin : `mysqli::real_connect(): (HY000/1698)` | Le mot de passe root MySQL a changé depuis l'installation de phpMyAdmin — reconfigurer avec `sudo dpkg-reconfigure phpmyadmin` |
| Erreur MySQL `Access denied` | Vérifier `DB_USERNAME`/`DB_PASSWORD` dans `.env`, tester avec `mysql -u checktime_user -p` |
| `SQLSTATE[HY000] [2002] Connection refused` | `sudo systemctl status mysql` — le service est peut-être arrêté |
| `npm run build` échoue | `node -v` doit être ≥ 18 ; réinstaller via NodeSource (étape 5) |
| La file d'attente ne traite rien | `sudo supervisorctl status` ; vérifier `QUEUE_CONNECTION=database` dans `.env` |
| Le planificateur (rapports, sync) ne s'exécute pas | `sudo crontab -u www-data -l` doit contenir la ligne `schedule:run` |
| `Permissions for ... are too open` (netplan) | `sudo chmod 600 /etc/netplan/01-checktime-static.yaml` |
| `failed to resolve ... server misbehaving` | DNS cassé par une config netplan inadaptée — voir [IP fixe en Wi-Fi](#ip-fixe-en-wi-fi-ou-sous-networkmanager) |
| `sudo ufw allow 80/tcp` → « État : inactif » | Normal : un pare-feu inactif ne bloque rien, ce n'est pas la cause d'un problème de connexion. `sudo ufw enable` pour l'activer si besoin |

### curl n'arrive pas à se connecter

`curl: (7) Failed to connect ... Could not connect to server` a trois causes possibles, à tester dans l'ordre :

```bash
# 1. Le serveur a-t-il vraiment cette IP ?
ip -4 addr show | grep inet
# Si l'IP affichée n'est pas celle testée, curl échoue forcément.
# En Wi-Fi/DHCP, l'IP peut avoir changé — voir la section IP fixe ci-dessus.

# 2. Apache tourne-t-il ?
sudo systemctl status apache2 --no-pager
curl -I http://localhost

# 3. Le pare-feu bloque-t-il ?
sudo ufw status
# S'il est "inactif", il ne bloque rien : ce n'est pas la cause.
# S'il est actif sans règle sur le port 80 :
sudo ufw allow 80/tcp
```

## Annexe : installation complète en un seul bloc

```bash
# 0. Paquets de base
sudo apt update && sudo apt upgrade -y
sudo apt install -y git curl wget nano unzip ca-certificates gnupg lsb-release \
    software-properties-common openssl net-tools

# 1. Cloner le projet
sudo mkdir -p /var/www/checktime
sudo git clone https://github.com/wahidfkiri/Checktime-Local /var/www/checktime
cd /var/www/checktime

# 2. Installation automatique
# (Apache + PHP + Composer + Node + MySQL + phpMyAdmin + app + VirtualHost
#  + Supervisor + cron)
sudo IP_FIXE=192.168.100.169 bash apache/install-ubuntu-apache.sh

# 3. Vérifier
sudo systemctl status apache2 mysql supervisor --no-pager
curl -I http://192.168.100.169
curl -I http://192.168.100.169/phpmyadmin
```

### Récapitulatif des accès

| Service | Adresse et identifiants |
|---|---|
| Application CheckTime | http://192.168.100.169 |
| Compte administrateur | admin@checktime.local / admin123 (à changer) |
| phpMyAdmin | http://192.168.100.169/phpmyadmin — utilisateur root |
| Répertoire d'installation | /var/www/checktime |
| Fichier de configuration | /var/www/checktime/.env |
| VirtualHost Apache | /etc/apache2/sites-available/checktime.conf |
| Configuration réseau | /etc/netplan/01-checktime-static.yaml |
| Logs Apache | /var/log/apache2/checktime-error.log |
| Logs Laravel | /var/www/checktime/storage/logs/laravel.log |
