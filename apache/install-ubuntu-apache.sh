#!/bin/bash
set -e

# ===========================================================
# Installation NATIVE de CheckTime sur Ubuntu 22.04 / 24.04
# (sans Docker — Apache + PHP-FPM ou mod_php + MySQL)
# Usage: sudo bash apache/install-ubuntu-apache.sh
# ===========================================================

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  Installation de CheckTime - Apache${NC}"
echo -e "${GREEN}========================================${NC}"

# ---- Vérifier root ----
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}Veuillez exécuter en tant que root (sudo).${NC}"
    exit 1
fi

# ---- Demander l'IP fixe ----
DEFAULT_IP="192.168.100.169"
if [ -n "$IP_FIXE" ]; then
    echo -e "${GREEN}IP fournie via la variable IP_FIXE: $IP_FIXE${NC}"
else
    read -p "Adresse IP fixe du serveur [${DEFAULT_IP}] : " IP_FIXE
    IP_FIXE=${IP_FIXE:-$DEFAULT_IP}
fi
echo -e "${GREEN}IP configurée: $IP_FIXE${NC}"

# ---- Demander les mots de passe MySQL ----
DB_PASSWORD=${DB_PASSWORD:-"P@ssw0rd"}
MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD:-$(openssl rand -base64 24 | tr -dc 'a-zA-Z0-9!@#%^&*()_+' | head -c 25)}

INSTALL_DIR="/var/www/checktime"
REPO_URL=${REPO_URL:-"https://github.com/wahidfkiri/Checktime-Local"}

# ---- 1. Paquets de base ----
# Ubuntu Server minimal n'inclut ni git ni curl : sans ça, le clonage du dépôt
# échoue ("git: command not found").
echo -e "${YELLOW}[1/12] Installation des paquets de base...${NC}"
apt-get update -qq
apt-get install -y -qq git curl wget nano unzip ca-certificates gnupg lsb-release \
    software-properties-common openssl net-tools apt-transport-https
echo -e "${GREEN}Paquets de base installés (git $(git --version | awk '{print $3}')).${NC}"

# ---- 2. Configurer l'IP fixe ----
echo -e "${YELLOW}[2/12] Configuration de l'IP fixe ${IP_FIXE}...${NC}"
INTERFACE=$(ip -o -4 route show to default | awk '{print $5}' | head -1)
GATEWAY=$(ip -o -4 route show to default | awk '{print $3}' | head -1)
NETPLAN_FILE="/etc/netplan/01-checktime-static.yaml"

# Vérifie que le DNS et la route par défaut fonctionnent encore.
network_ok() {
    ping -c 1 -W 3 "${GATEWAY:-8.8.8.8}" >/dev/null 2>&1 &&
    getent hosts deb.nodesource.com >/dev/null 2>&1
}

if [ -z "$INTERFACE" ]; then
    echo -e "${RED}Impossible de détecter l'interface réseau. Configuration manuelle requise.${NC}"
    echo "  sudo nmcli connection show   (ou)   sudo nano /etc/netplan/00-installer-config.yaml"

# Carte Wi-Fi (wl*) : netplan « ethernets » ne s'applique pas, et un bloc
# « wifis » exigerait le SSID et la clé. On laisse la main à NetworkManager
# plutôt que de casser la connexion (et donc le DNS) du serveur.
elif [ "${INTERFACE#wl}" != "$INTERFACE" ]; then
    echo -e "${YELLOW}Interface Wi-Fi détectée : $INTERFACE${NC}"
    echo -e "${YELLOW}L'IP fixe n'est PAS configurée automatiquement en Wi-Fi.${NC}"
    echo -e "${YELLOW}Configurez-la avec NetworkManager :${NC}"
    echo "  nmcli connection show"
    echo "  sudo nmcli connection modify \"<NomDuWifi>\" \\"
    echo "    ipv4.method manual ipv4.addresses ${IP_FIXE}/24 \\"
    echo "    ipv4.gateway ${GATEWAY:-192.168.100.1} ipv4.dns \"8.8.8.8,1.1.1.1\""
    echo "  sudo nmcli connection down \"<NomDuWifi>\" && sudo nmcli connection up \"<NomDuWifi>\""
    echo -e "${YELLOW}L'installation continue avec l'adresse IP actuelle.${NC}"

else
    echo "Interface détectée: $INTERFACE"

    # Le renderer doit correspondre au gestionnaire réseau réellement actif :
    # networkd sur Ubuntu Server, NetworkManager sur Ubuntu Desktop.
    if systemctl is-active --quiet NetworkManager; then
        RENDERER="NetworkManager"
    else
        RENDERER="networkd"
    fi
    echo "Gestionnaire réseau: $RENDERER"

    BACKUP=""
    if [ -f "$NETPLAN_FILE" ]; then
        BACKUP="${NETPLAN_FILE}.bak.$(date +%s)"
        cp "$NETPLAN_FILE" "$BACKUP"
    fi

    cat > "$NETPLAN_FILE" << NETPLAN
network:
  version: 2
  renderer: $RENDERER
  ethernets:
    $INTERFACE:
      addresses:
        - $IP_FIXE/24
      routes:
        - to: default
          via: ${GATEWAY:-192.168.100.1}
      nameservers:
        addresses:
          - 8.8.8.8
          - 1.1.1.1
NETPLAN
    chmod 600 "$NETPLAN_FILE"

    if netplan apply; then
        sleep 3
        if network_ok; then
            echo -e "${GREEN}IP fixe $IP_FIXE configurée.${NC}"
        else
            echo -e "${RED}Le réseau ne répond plus après netplan apply : retour en arrière.${NC}"
            if [ -n "$BACKUP" ]; then mv "$BACKUP" "$NETPLAN_FILE"; else rm -f "$NETPLAN_FILE"; fi
            netplan apply || true
            echo -e "${YELLOW}IP fixe à configurer manuellement. L'installation continue.${NC}"
        fi
    else
        echo -e "${RED}Erreur netplan : configuration retirée.${NC}"
        if [ -n "$BACKUP" ]; then mv "$BACKUP" "$NETPLAN_FILE"; else rm -f "$NETPLAN_FILE"; fi
        netplan apply || true
    fi
fi

if ! getent hosts deb.nodesource.com >/dev/null 2>&1; then
    echo -e "${RED}ATTENTION : la résolution DNS ne fonctionne pas.${NC}"
    echo -e "${YELLOW}Les étapes suivantes (PHP, Node, MySQL) vont échouer. Vérifiez :${NC}"
    echo "  ping -c 3 8.8.8.8   /   resolvectl status   /   cat /etc/resolv.conf"
fi

# ---- 3. Installer Apache ----
echo -e "${YELLOW}[3/12] Installation d'Apache...${NC}"
apt-get install -y -qq apache2
a2enmod rewrite headers expires >/dev/null
systemctl enable apache2 >/dev/null
systemctl start apache2

# ---- 4. Installer PHP et ses extensions ----
# CheckTime exige seulement PHP ^8.0.2 : on installe la version fournie par
# les dépôts Ubuntu (8.1 sur 22.04, 8.3 sur 24.04), sans dépôt tiers. Les
# noms de paquets sans numéro de version (php-mysql, php-gd...) résolvent
# automatiquement vers la bonne version installée.
# Pas de php-opcache : OPcache est déjà fourni et activé par défaut avec
# php-common (mods-available/opcache.ini), et ce paquet séparé n'existe pas
# sur toutes les releases Ubuntu.
echo -e "${YELLOW}[4/12] Installation de PHP...${NC}"
apt-get install -y -qq \
    php libapache2-mod-php php-cli php-common \
    php-mysql php-mbstring php-xml php-bcmath \
    php-gd php-zip php-intl php-curl
PHP_VERSION=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
a2dismod mpm_event >/dev/null 2>&1 || true
a2enmod mpm_prefork >/dev/null 2>&1 || true
a2enmod "php${PHP_VERSION}" >/dev/null
echo -e "${GREEN}PHP $(php -v | head -1 | awk '{print $2}') installé.${NC}"

# ---- 5. Installer Composer ----
echo -e "${YELLOW}[5/12] Installation de Composer...${NC}"
if ! command -v composer &>/dev/null; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi
echo -e "${GREEN}Composer $(composer --version --no-ansi | awk '{print $3}') installé.${NC}"

# ---- 6. Installer Node.js (build des assets front) ----
echo -e "${YELLOW}[6/12] Installation de Node.js...${NC}"
if ! command -v node &>/dev/null || [ "$(node -v | cut -d. -f1 | tr -d v)" -lt 18 ]; then
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash - >/dev/null
    apt-get install -y -qq nodejs
fi
echo -e "${GREEN}Node $(node -v) installé.${NC}"

# ---- 7. Installer et configurer MySQL ----
echo -e "${YELLOW}[7/12] Installation de MySQL...${NC}"
apt-get install -y -qq mysql-server
systemctl enable mysql >/dev/null
systemctl start mysql

mysql --user=root <<-SQL
    ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '${MYSQL_ROOT_PASSWORD}';
    CREATE DATABASE IF NOT EXISTS checktime CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    CREATE USER IF NOT EXISTS 'checktime_user'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
    GRANT ALL PRIVILEGES ON checktime.* TO 'checktime_user'@'localhost';
    FLUSH PRIVILEGES;
SQL
echo -e "${GREEN}Base de données checktime créée.${NC}"

# ---- 8. Installer phpMyAdmin ----
echo -e "${YELLOW}[8/12] Installation de phpMyAdmin...${NC}"
# Préconfiguration debconf pour une installation non interactive (le paquet
# demande normalement le serveur web et les mots de passe via des invites).
debconf-set-selections <<EOF
phpmyadmin phpmyadmin/dbconfig-install boolean true
phpmyadmin phpmyadmin/app-password-confirm password ${MYSQL_ROOT_PASSWORD}
phpmyadmin phpmyadmin/mysql/admin-pass password ${MYSQL_ROOT_PASSWORD}
phpmyadmin phpmyadmin/mysql/app-pass password ${MYSQL_ROOT_PASSWORD}
phpmyadmin phpmyadmin/reconfigure-webserver multiselect apache2
EOF
DEBIAN_FRONTEND=noninteractive apt-get install -y -qq phpmyadmin
a2enconf phpmyadmin >/dev/null
systemctl reload apache2
echo -e "${GREEN}phpMyAdmin installé : http://${IP_FIXE}/phpmyadmin${NC}"

# ---- Ouvrir les ports LAN (pare-feu ufw) ----
if command -v ufw >/dev/null 2>&1; then
    ufw allow 80/tcp  || true
    ufw allow 443/tcp || true
    # « ufw allow » n'a aucun effet tant que le pare-feu n'est pas actif.
    if ufw status | grep -q "Status: inactive"; then
        echo -e "${YELLOW}UFW est inactif : les ports 80/443 sont déjà accessibles sans lui.${NC}"
        echo -e "${YELLOW}Pour l'activer : sudo ufw enable${NC}"
    else
        echo -e "${GREEN}Pare-feu : ports 80 et 443 autorisés.${NC}"
    fi
fi

# ---- 9. Cloner le projet ----
echo -e "${YELLOW}[9/12] Clonage de l'application...${NC}"
if [ ! -d "$INSTALL_DIR/.git" ]; then
    mkdir -p "$INSTALL_DIR"
    git clone "$REPO_URL" "$INSTALL_DIR"
fi
cd "$INSTALL_DIR"

# ---- 10. Configurer l'environnement ----
echo -e "${YELLOW}[10/12] Configuration de l'environnement...${NC}"
if [ ! -f .env ]; then
    cp .env.example .env
fi
sed -i "s|^APP_NAME=.*|APP_NAME=CheckTime|" .env
sed -i "s|^APP_ENV=.*|APP_ENV=production|" .env
sed -i "s|^APP_DEBUG=.*|APP_DEBUG=false|" .env
sed -i "s|^APP_URL=.*|APP_URL=http://${IP_FIXE}|" .env
sed -i "s|^DB_HOST=.*|DB_HOST=127.0.0.1|" .env
sed -i "s|^DB_DATABASE=.*|DB_DATABASE=checktime|" .env
sed -i "s|^DB_USERNAME=.*|DB_USERNAME=checktime_user|" .env
sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|" .env
sed -i "s|^QUEUE_CONNECTION=.*|QUEUE_CONNECTION=database|" .env
sed -i "s|^SESSION_DRIVER=.*|SESSION_DRIVER=file|" .env

# Répertoires persistants
mkdir -p storage/app/public storage/framework/{cache/data,sessions,views} \
    storage/logs bootstrap/cache public/storage public/uploads

# Dépendances PHP et front (sans les paquets de développement)
composer install --no-dev --optimize-autoloader --no-interaction
npm install --no-audit --no-fund
npm run build
rm -rf node_modules

# Permissions : Apache tourne sous www-data sur Ubuntu
chown -R www-data:www-data "$INSTALL_DIR"
chmod -R 775 storage bootstrap/cache

# ---- 11. Commandes Laravel ----
echo -e "${YELLOW}[11/12] Exécution des commandes Laravel...${NC}"
sudo -u www-data php artisan key:generate --force
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan storage:link --force || true
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

echo -e "${YELLOW}Création de l'utilisateur admin...${NC}"
sudo -u www-data php artisan tinker --execute="
    \$user = \App\Models\User::where('email', 'admin@checktime.local')->first();
    if (!\$user) {
        \$user = \App\Models\User::create([
            'name' => 'Administrateur',
            'email' => 'admin@checktime.local',
            'password' => bcrypt('admin123'),
        ]);
        echo 'Admin créé: admin@checktime.local / admin123\n';
    } else {
        echo 'Admin existe déjà.\n';
    }
    use Spatie\Permission\Models\Role;
    \$role = Role::firstOrCreate(['name' => 'admin']);
    if (!\$user->hasRole('admin')) {
        \$user->assignRole('admin');
        echo 'Rôle admin assigné.\n';
    }
"

# ---- 12. Configurer Apache, le worker de file d'attente et le planificateur ----
echo -e "${YELLOW}[12/12] Finalisation (Apache, file d'attente, cron)...${NC}"

cat > /etc/apache2/sites-available/checktime.conf << APACHECONF
<VirtualHost *:80>
    ServerName ${IP_FIXE}
    DocumentRoot ${INSTALL_DIR}/public

    <Directory ${INSTALL_DIR}/public>
        Options -MultiViews +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Fichiers sensibles jamais servis
    <FilesMatch "^\.env|\.git|composer\.(json|lock)|artisan\$">
        Require all denied
    </FilesMatch>

    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"

    ErrorLog \${APACHE_LOG_DIR}/checktime-error.log
    CustomLog \${APACHE_LOG_DIR}/checktime-access.log combined
</VirtualHost>
APACHECONF

a2ensite checktime.conf >/dev/null
a2dissite 000-default.conf >/dev/null 2>&1 || true
systemctl reload apache2

# Worker de file d'attente : Supervisor (identique au conteneur Docker)
apt-get install -y -qq supervisor
cat > /etc/supervisor/conf.d/checktime-queue.conf << SUPERVISORCONF
[program:checktime-queue]
command=php ${INSTALL_DIR}/artisan queue:work --queue=zones,zones-high,default --sleep=3 --tries=3 --timeout=300
directory=${INSTALL_DIR}
autostart=true
autorestart=true
user=www-data
numprocs=2
process_name=%(program_name)s_%(process_num)02d
stdout_logfile=/var/log/supervisor/checktime-queue.log
stderr_logfile=/var/log/supervisor/checktime-queue.err.log
SUPERVISORCONF
systemctl enable supervisor >/dev/null
systemctl restart supervisor
supervisorctl reread >/dev/null && supervisorctl update >/dev/null

# Planificateur : cron standard Laravel (remplace le "while true" du conteneur)
CRON_LINE="* * * * * cd ${INSTALL_DIR} && php artisan schedule:run >> /dev/null 2>&1"
( crontab -u www-data -l 2>/dev/null | grep -vF "$INSTALL_DIR"; echo "$CRON_LINE" ) | crontab -u www-data -

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  Installation terminée avec succès !${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "  Application : ${YELLOW}http://${IP_FIXE}${NC}"
echo -e "  Admin        : ${YELLOW}admin@checktime.local${NC}"
echo -e "  Mot de passe : ${YELLOW}admin123${NC}"
echo ""
echo -e "  phpMyAdmin   : ${YELLOW}http://${IP_FIXE}/phpmyadmin${NC}"
echo -e "  (utilisateur : ${YELLOW}root${NC} / mot de passe root ci-dessous)"
echo ""
echo -e "  ${YELLOW}IMPORTANT: Changez les mots de passe en production !${NC}"
echo -e "  MYSQL_ROOT_PASSWORD : ${MYSQL_ROOT_PASSWORD}"
echo -e "  DB_PASSWORD         : ${DB_PASSWORD}"
echo ""
echo -e "${GREEN}Commandes utiles :${NC}"
echo "  sudo tail -f /var/log/apache2/checktime-error.log   # Logs Apache"
echo "  sudo supervisorctl status                            # État de la file d'attente"
echo "  sudo systemctl restart apache2                       # Redémarrer Apache"
echo "  sudo -u www-data php artisan migrate --force          # Rejouer les migrations"
echo ""
