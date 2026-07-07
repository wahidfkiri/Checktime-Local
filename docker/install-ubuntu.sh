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
DEFAULT_IP=$(ip -o -4 addr show | awk '{print $4}' | cut -d/ -f1 | grep -v 127.0.0.1 | head -1)
DEFAULT_IP=${DEFAULT_IP:-192.168.1.100}
read -p "Adresse IP fixe du serveur [${DEFAULT_IP}] : " IP_FIXE
IP_FIXE=${IP_FIXE:-$DEFAULT_IP}
echo -e "${GREEN}IP configurée: $IP_FIXE${NC}"

INSTALL_DIR="/opt/checktime"
DB_PASSWORD="P@ssw0rd"
MYSQL_ROOT_PASSWORD=$(openssl rand -base64 24 | tr -dc 'a-zA-Z0-9!@#%^&*()_+' | head -c 25)

# ---- 1. Installer Docker ----
echo -e "${YELLOW}[1/8] Installation de Docker...${NC}"
if ! command -v docker &>/dev/null; then
    apt-get update -qq
    apt-get install -y -qq ca-certificates curl gnupg lsb-release
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

# ---- 2. Configurer l'IP fixe ----
echo -e "${YELLOW}[2/8] Configuration de l'IP fixe ${IP_FIXE}...${NC}"
# Vérifier l'interface réseau active
INTERFACE=$(ip -o -4 route show to default | awk '{print $5}' | head -1)
if [ -z "$INTERFACE" ]; then
    echo -e "${RED}Impossible de détecter l'interface réseau. Configuration manuelle requise.${NC}"
    echo -e "${YELLOW}Après l'installation, configurez l'IP fixe avec:${NC}"
    echo "  sudo nano /etc/netplan/00-installer-config.yaml"
    echo "  sudo netplan apply"
else
    echo "Interface détectée: $INTERFACE"
    # Créer config Netplan pour IP fixe
    cat > /etc/netplan/01-checktime-static.yaml << NETPLAN
network:
  version: 2
  renderer: networkd
  ethernets:
    $INTERFACE:
      addresses:
        - $IP_FIXE/24
      routes:
        - to: default
          via: $(ip -o -4 route show to default | awk '{print $3}')
      nameservers:
        addresses:
          - 8.8.8.8
          - 1.1.1.1
NETPLAN
    netplan apply || echo -e "${RED}Erreur netplan. Vérifiez manuellement.${NC}"
    echo -e "${GREEN}IP fixe $IP_FIXE configurée.${NC}"
fi

# ---- 3. Cloner le projet ----
echo -e "${YELLOW}[3/8] Clonage de l'application...${NC}"
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

# ---- 4. Créer le fichier .env ----
echo -e "${YELLOW}[4/8] Configuration de l'environnement...${NC}"
if [ ! -f .env ]; then
    cp .env.example .env
fi
sed -i "s|^APP_NAME=.*|APP_NAME=CheckTime|" .env
sed -i "s|^APP_ENV=.*|APP_ENV=production|" .env
sed -i "s|^APP_DEBUG=.*|APP_DEBUG=false|" .env
sed -i "s|^APP_URL=.*|APP_URL=http://${IP_FIXE}|" .env
sed -i "s|^DB_HOST=.*|DB_HOST=mysql|" .env
sed -i "s|^DB_DATABASE=.*|DB_DATABASE=checktime|" .env
sed -i "s|^DB_USERNAME=.*|DB_USERNAME=checktime_user|" .env
sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|" .env
sed -i "s|^QUEUE_CONNECTION=.*|QUEUE_CONNECTION=database|" .env
sed -i "s|^SESSION_DRIVER=.*|SESSION_DRIVER=file|" .env

# ---- 5. Créer les répertoires persistants ----
echo -e "${YELLOW}[5/8] Création des répertoires de données...${NC}"
mkdir -p storage/app/public
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache
mkdir -p public/storage
chmod -R 775 storage bootstrap/cache public/storage

# ---- 6. Lancer Docker Compose ----
echo -e "${YELLOW}[6/8] Lancement des conteneurs Docker...${NC}"
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

# ---- 7. Commandes Laravel ----
echo -e "${YELLOW}[7/8] Exécution des commandes Laravel...${NC}"

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

# ---- 8. Configuration finale ----
echo -e "${YELLOW}[8/8] Finalisation...${NC}"

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
