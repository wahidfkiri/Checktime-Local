#!/bin/bash
set -e

# ===========================================================
# Script d'installation de CheckTime sur Ubuntu 22.04 / 24.04
# Usage: sudo bash install-ubuntu.sh
# ===========================================================

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  Installation de CheckTime - Docker${NC}"
echo -e "${GREEN}========================================${NC}"

# ---- Vérifier root ----
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}Veuillez exécuter en tant que root (sudo).${NC}"
    exit 1
fi

# ---- Demander l'IP fixe ----
# IP par défaut du serveur CheckTime. Pour installer sur une autre IP :
#   sudo IP_FIXE=192.168.1.50 bash docker/install-ubuntu.sh   (sans question)
# ou répondre à l'invite ci-dessous.
DEFAULT_IP="192.168.100.169"
if [ -n "$IP_FIXE" ]; then
    echo -e "${GREEN}IP fournie via la variable IP_FIXE: $IP_FIXE${NC}"
else
    read -p "Adresse IP fixe du serveur [${DEFAULT_IP}] : " IP_FIXE
    IP_FIXE=${IP_FIXE:-$DEFAULT_IP}
fi
echo -e "${GREEN}IP configurée: $IP_FIXE${NC}"

INSTALL_DIR="/opt/checktime"
DB_PASSWORD="P@ssw0rd"
MYSQL_ROOT_PASSWORD=$(openssl rand -base64 24 | tr -dc 'a-zA-Z0-9!@#%^&*()_+' | head -c 25)

# ---- 1. Paquets de base ----
# Ubuntu Server minimal n'inclut ni git ni curl : sans ça, le clonage du dépôt
# et l'ajout de la clé GPG Docker échouent ("git: command not found").
echo -e "${YELLOW}[1/9] Installation des paquets de base...${NC}"
apt-get update -qq
apt-get install -y -qq git curl wget nano unzip ca-certificates gnupg lsb-release openssl net-tools
echo -e "${GREEN}Paquets de base installés (git $(git --version | awk '{print $3}')).${NC}"

# ---- 2. Installer Docker ----
echo -e "${YELLOW}[2/9] Installation de Docker...${NC}"
if ! command -v docker &>/dev/null; then
    mkdir -p /etc/apt/keyrings
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | tee /etc/apt/sources.list.d/docker.list > /dev/null
    apt-get update -qq
    apt-get install -y -qq docker-ce docker-ce-cli containerd.io docker-compose-plugin
    systemctl enable docker
    systemctl start docker
    echo -e "${GREEN}Docker installé avec succès.${NC}"
else
    echo -e "${GREEN}Docker déjà installé.${NC}"
fi

# ---- 3. Configurer l'IP fixe ----
echo -e "${YELLOW}[3/9] Configuration de l'IP fixe ${IP_FIXE}...${NC}"
# Vérifier l'interface réseau active
INTERFACE=$(ip -o -4 route show to default | awk '{print $5}' | head -1)
GATEWAY=$(ip -o -4 route show to default | awk '{print $3}' | head -1)
NETPLAN_FILE="/etc/netplan/01-checktime-static.yaml"

# Vérifie que le DNS et la route par défaut fonctionnent encore.
network_ok() {
    ping -c 1 -W 3 "${GATEWAY:-8.8.8.8}" >/dev/null 2>&1 &&
    getent hosts registry-1.docker.io >/dev/null 2>&1
}

if [ -z "$INTERFACE" ]; then
    echo -e "${RED}Impossible de détecter l'interface réseau. Configuration manuelle requise.${NC}"
    echo -e "${YELLOW}Après l'installation, configurez l'IP fixe avec:${NC}"
    echo "  sudo nmcli connection show     # puis ipv4.method manual"
    echo "  ou  sudo nano /etc/netplan/00-installer-config.yaml && sudo netplan apply"

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
    # networkd sur Ubuntu Server, NetworkManager sur Ubuntu Desktop. Se tromper
    # rend la configuration inopérante et casse la résolution DNS.
    if systemctl is-active --quiet NetworkManager; then
        RENDERER="NetworkManager"
    else
        RENDERER="networkd"
    fi
    echo "Gestionnaire réseau: $RENDERER"

    # Sauvegarde pour pouvoir revenir en arrière si le réseau tombe.
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
    # Netplan refuse un fichier lisible par tous (avertissement "permissions too open").
    chmod 600 "$NETPLAN_FILE"

    if netplan apply; then
        sleep 3
        if network_ok; then
            echo -e "${GREEN}IP fixe $IP_FIXE configurée.${NC}"
        else
            echo -e "${RED}Le réseau ne répond plus après netplan apply : retour en arrière.${NC}"
            if [ -n "$BACKUP" ]; then
                mv "$BACKUP" "$NETPLAN_FILE"
            else
                rm -f "$NETPLAN_FILE"
            fi
            netplan apply || true
            echo -e "${YELLOW}IP fixe à configurer manuellement. L'installation continue.${NC}"
        fi
    else
        echo -e "${RED}Erreur netplan : configuration retirée.${NC}"
        if [ -n "$BACKUP" ]; then mv "$BACKUP" "$NETPLAN_FILE"; else rm -f "$NETPLAN_FILE"; fi
        netplan apply || true
    fi
fi

# Sans DNS, le téléchargement des images Docker échoue plus loin avec
# "failed to resolve reference docker.io/library/mysql:8.0".
if ! getent hosts registry-1.docker.io >/dev/null 2>&1; then
    echo -e "${RED}ATTENTION : la résolution DNS ne fonctionne pas.${NC}"
    echo -e "${YELLOW}Le téléchargement des images Docker va échouer. Vérifiez :${NC}"
    echo "  ping -c 3 8.8.8.8"
    echo "  resolvectl status"
    echo "  cat /etc/resolv.conf"
fi

# Ouvrir les ports LAN si le pare-feu ufw est présent/actif
if command -v ufw >/dev/null 2>&1; then
    ufw allow 80/tcp   || true   # Application web
    ufw allow 8080/tcp || true   # phpMyAdmin (retirer si non souhaité sur le LAN)
    echo -e "${GREEN}Pare-feu : ports 80 et 8080 autorisés.${NC}"
fi

# ---- 4. Cloner le projet ----
echo -e "${YELLOW}[4/9] Clonage de l'application...${NC}"
if [ ! -d "$INSTALL_DIR" ]; then
    mkdir -p "$INSTALL_DIR"
    # À adapter : remplacer par votre dépôt Git
    echo -e "${YELLOW}Veuillez cloner votre dépôt Git manuellement:${NC}"
    echo "  git clone https://github.com/wahidfkiri/Checktime-Local $INSTALL_DIR"
    echo "  cd $INSTALL_DIR"
    echo "  sudo bash docker/install-ubuntu.sh"
    exit 0
fi
cd "$INSTALL_DIR"

# ---- 5. Créer le fichier .env ----
echo -e "${YELLOW}[5/9] Configuration de l'environnement...${NC}"
if [ ! -f .env ]; then
    cp .env.example .env
fi
sed -i "s|^APP_NAME=.*|APP_NAME=CheckTime|" .env
sed -i "s|^APP_ENV=.*|APP_ENV=production|" .env
sed -i "s|^APP_DEBUG=.*|APP_DEBUG=false|" .env
sed -i "s|^APP_URL=.*|APP_URL=http://${IP_FIXE}|" .env
# APP_HOST_IP est relu par docker-compose.yml : l'IP survit aux prochains
# "docker compose up -d" sans devoir relancer ce script.
if grep -q "^APP_HOST_IP=" .env; then
    sed -i "s|^APP_HOST_IP=.*|APP_HOST_IP=${IP_FIXE}|" .env
else
    echo "APP_HOST_IP=${IP_FIXE}" >> .env
fi
sed -i "s|^DB_HOST=.*|DB_HOST=mysql|" .env
sed -i "s|^DB_DATABASE=.*|DB_DATABASE=checktime|" .env
sed -i "s|^DB_USERNAME=.*|DB_USERNAME=checktime_user|" .env
sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|" .env
sed -i "s|^QUEUE_CONNECTION=.*|QUEUE_CONNECTION=database|" .env
sed -i "s|^SESSION_DRIVER=.*|SESSION_DRIVER=file|" .env

# ---- 6. Créer les répertoires persistants ----
echo -e "${YELLOW}[6/9] Création des répertoires de données...${NC}"
mkdir -p storage/app/public
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache
mkdir -p public/storage
chmod -R 775 storage bootstrap/cache public/storage

# ---- 7. Lancer Docker Compose ----
echo -e "${YELLOW}[7/9] Lancement des conteneurs Docker...${NC}"
export DB_PASSWORD
export MYSQL_ROOT_PASSWORD
export APP_HOST_IP="${IP_FIXE}"

# Vérifier docker-compose
if command -v docker-compose &>/dev/null; then
    docker-compose up -d --build
elif docker compose version &>/dev/null; then
    docker compose up -d --build
else
    echo -e "${RED}docker-compose introuvable.${NC}"
    exit 1
fi

echo -e "${GREEN}Conteneurs démarrés. Attente de la base de données...${NC}"
sleep 10

# ---- 8. Commandes Laravel ----
echo -e "${YELLOW}[8/9] Exécution des commandes Laravel...${NC}"

# Attendre que l'app soit prête
for i in $(seq 1 30); do
    if docker exec checktime-app php artisan db:show 2>/dev/null; then
        echo -e "${GREEN}Base de données accessible.${NC}"
        break
    fi
    echo "Attente de l'application... ($i/30)"
    sleep 3
done

# Générer la clé
docker exec checktime-app php artisan key:generate --force
echo "APP_KEY générée."

# Migrations
docker exec checktime-app php artisan migrate --force
echo "Migrations exécutées."

# Créer le lien de stockage
docker exec checktime-app php artisan storage:link --force || true

# Cache
docker exec checktime-app php artisan config:cache
docker exec checktime-app php artisan route:cache
docker exec checktime-app php artisan view:cache

# Créer l'admin (modifier l'email/mot de passe si besoin)
echo -e "${YELLOW}Création de l'utilisateur admin...${NC}"
docker exec checktime-app php artisan tinker --execute="
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
    use Spatie\Permission\Models\Permission;

    \$role = Role::firstOrCreate(['name' => 'admin']);
    if (!\$user->hasRole('admin')) {
        \$user->assignRole('admin');
        echo 'Rôle admin assigné.\n';
    }
"

echo -e "${GREEN}Admin: admin@checktime.local / admin123${NC}"

# ---- 9. Configuration finale ----
echo -e "${YELLOW}[9/9] Finalisation...${NC}"

# Ajouter au démarrage automatique
docker update --restart unless-stopped checktime-app checktime-mysql 2>/dev/null || true

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  Installation terminée avec succès !${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "  Application : ${YELLOW}http://${IP_FIXE}${NC}"
echo -e "  Admin        : ${YELLOW}admin@checktime.local${NC}"
echo -e "  Mot de passe : ${YELLOW}admin123${NC}"
echo ""
echo -e "  phpMyAdmin   : ${YELLOW}http://${IP_FIXE}:8080${NC}"
echo -e "  (utilisateur : ${YELLOW}root${NC} / mot de passe root ci-dessous)"
echo ""
echo -e "  ${YELLOW}IMPORTANT: Changez les mots de passe en production !${NC}"
echo -e "  MYSQL_ROOT_PASSWORD : ${MYSQL_ROOT_PASSWORD}"
echo -e "  DB_PASSWORD         : ${DB_PASSWORD}"
echo ""
echo -e "${GREEN}Commandes utiles :${NC}"
echo "  sudo docker compose logs -f app       # Voir les logs"
echo "  sudo docker compose exec app bash     # Shell dans le conteneur"
echo "  sudo docker compose restart app       # Redémarrer l'application"
echo "  sudo docker compose down && sudo docker compose up -d  # Réinitialiser"
echo ""
