#!/bin/bash
# ═══════════════════════════════════════════════════════════════
#  HCS ERP — Déploiement FTP PlanetHoster
#  Usage : bash deploy-erp.sh
#  Ce script sync tous les fichiers de l'ERP vers le serveur
# ═══════════════════════════════════════════════════════════════

# ── CONFIGURATION ── (à remplir ou mettre dans .env)
FTP_HOST="${FTP_HOST:-ftp.highcoffeeshirts.com}"
FTP_USER="${FTP_USER:-VOTRE_LOGIN_FTP}"
FTP_PASS="${FTP_PASS:-VOTRE_MOT_DE_PASSE_FTP}"
REMOTE_DIR="${REMOTE_DIR:-/public_html/erp}"
LOCAL_DIR="$(cd "$(dirname "$0")" && pwd)"

# ── COULEURS ──
GREEN='\033[0;32m'
ORANGE='\033[0;33m'
RED='\033[0;31m'
NC='\033[0m'

echo ""
echo "══════════════════════════════════════════"
echo "  ⬡ HCS ERP — Déploiement PlanetHoster"
echo "══════════════════════════════════════════"
echo "  Serveur : $FTP_HOST"
echo "  Dossier : $REMOTE_DIR"
echo ""

# ── VÉRIFICATION lftp ──
if ! command -v lftp &> /dev/null; then
  echo -e "${RED}✗ lftp non trouvé.${NC}"
  echo "  Installe-le : sudo apt install lftp (WSL) ou utilise WinSCP en CLI"
  echo ""
  echo "  Alternative Python (sans lftp) :"
  echo "  python deploy-erp.py"
  exit 1
fi

# ── FICHIERS À EXCLURE ──
EXCLUDES="
--exclude .git
--exclude .claude
--exclude node_modules
--exclude deploy-erp.sh
--exclude deploy-erp.py
--exclude PROMPTS-CLAUDE-CODE.md
--exclude hcs-proxy-worker
--exclude *.md
--exclude picwish-pipeline.html
"

echo -e "${ORANGE}▶ Connexion et synchronisation en cours...${NC}"
echo ""

# ── SYNC FTP avec lftp ──
lftp -u "$FTP_USER","$FTP_PASS" "$FTP_HOST" <<EOF
set ssl:verify-certificate no
set ftp:passive-mode yes
mirror --reverse --delete --verbose \
  --exclude=.git/ \
  --exclude=.claude/ \
  --exclude=node_modules/ \
  --exclude=hcs-proxy-worker/ \
  --exclude=deploy-erp.sh \
  --exclude=deploy-erp.py \
  --exclude=PROMPTS-CLAUDE-CODE.md \
  "$LOCAL_DIR" "$REMOTE_DIR"
bye
EOF

# ── RÉSULTAT ──
if [ $? -eq 0 ]; then
  echo ""
  echo -e "${GREEN}✅ Déploiement réussi !${NC}"
  echo "   URL : https://highcoffeeshirts.com/erp/"
  echo ""
else
  echo ""
  echo -e "${RED}✗ Erreur lors du déploiement — vérifie les identifiants FTP${NC}"
  echo ""
  exit 1
fi
