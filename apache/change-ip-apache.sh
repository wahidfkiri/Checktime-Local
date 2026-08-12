#!/bin/bash
set -e

# ===========================================================
# Change l'adresse IP fixe du serveur CheckTime (installation native).
# Usage: sudo bash apache/change-ip-apache.sh 192.168.100.169
# ===========================================================

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}Veuillez exécuter en tant que root (sudo).${NC}"
    exit 1
fi

NEW_IP="$1"
if [ -z "$NEW_IP" ]; then
    read -p "Nouvelle adresse IP du serveur : " NEW_IP
fi

if ! echo "$NEW_IP" | grep -Eq '^([0-9]{1,3}\.){3}[0-9]{1,3}$'; then
    echo -e "${RED}Adresse IP invalide : $NEW_IP${NC}"
    exit 1
fi

INSTALL_DIR="${INSTALL_DIR:-/var/www/checktime}"
cd "$INSTALL_DIR"

if [ ! -f .env ]; then
    echo -e "${RED}.env introuvable dans $INSTALL_DIR${NC}"
    exit 1
fi

# ---- 1. Mettre à jour le .env ----
echo -e "${YELLOW}[1/4] Mise à jour du .env...${NC}"
sed -i "s|^APP_URL=.*|APP_URL=http://${NEW_IP}|" .env

# ---- 2. Mettre à jour l'IP système ----
echo -e "${YELLOW}[2/4] Mise à jour de l'IP système...${NC}"
NETPLAN_FILE="/etc/netplan/01-checktime-static.yaml"
if [ -f "$NETPLAN_FILE" ]; then
    sed -i "s|- [0-9.]*/24|- ${NEW_IP}/24|" "$NETPLAN_FILE"
    chmod 600 "$NETPLAN_FILE"
    netplan apply || echo -e "${RED}Erreur netplan. Vérifiez $NETPLAN_FILE manuellement.${NC}"
    echo -e "${GREEN}IP système mise à jour.${NC}"
else
    # Cas Wi-Fi / Ubuntu Desktop : l'IP est gérée par NetworkManager.
    echo -e "${YELLOW}$NETPLAN_FILE absent : IP système gérée hors netplan.${NC}"
    if command -v nmcli >/dev/null 2>&1; then
        echo -e "${YELLOW}Avec NetworkManager :${NC}"
        echo "  nmcli connection show"
        echo "  sudo nmcli connection modify \"<NomDeLaConnexion>\" \\"
        echo "    ipv4.method manual ipv4.addresses ${NEW_IP}/24 \\"
        echo "    ipv4.gateway <passerelle> ipv4.dns \"8.8.8.8,1.1.1.1\""
    fi
fi

# ---- 3. Mettre à jour le VirtualHost Apache ----
echo -e "${YELLOW}[3/4] Mise à jour du VirtualHost Apache...${NC}"
APACHE_CONF="/etc/apache2/sites-available/checktime.conf"
if [ -f "$APACHE_CONF" ]; then
    sed -i "s|ServerName .*|ServerName ${NEW_IP}|" "$APACHE_CONF"
    apache2ctl configtest && systemctl reload apache2
    echo -e "${GREEN}VirtualHost Apache mis à jour.${NC}"
else
    echo -e "${YELLOW}$APACHE_CONF introuvable : à mettre à jour manuellement.${NC}"
fi

# ---- 4. Vider et régénérer le cache Laravel ----
echo -e "${YELLOW}[4/4] Régénération du cache Laravel...${NC}"
sudo -u www-data php artisan config:clear || true
sudo -u www-data php artisan config:cache || true

echo ""
echo -e "${GREEN}Terminé. Application : http://${NEW_IP}${NC}"
echo -e "${GREEN}phpMyAdmin          : http://${NEW_IP}/phpmyadmin${NC}"
