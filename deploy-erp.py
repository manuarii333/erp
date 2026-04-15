"""
HCS ERP — Déploiement FTP PlanetHoster (Python)
Alternative à deploy-erp.sh si lftp n'est pas disponible (Windows natif)
Usage : python deploy-erp.py
"""

import ftplib
import os
import sys
from pathlib import Path

# Forcer UTF-8 sur Windows pour éviter les erreurs d'encodage
if sys.stdout.encoding != 'utf-8':
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')

# ── CONFIGURATION ──────────────────────────────────────────────
FTP_HOST   = os.environ.get("FTP_HOST",   "ftp.highcoffeeshirts.com")
FTP_USER   = os.environ.get("FTP_USER",   "VOTRE_LOGIN_FTP")
FTP_PASS   = os.environ.get("FTP_PASS",   "VOTRE_MOT_DE_PASSE_FTP")
REMOTE_DIR = os.environ.get("REMOTE_DIR", "/public_html/erp")
LOCAL_DIR  = Path(__file__).parent.resolve()

# ── FICHIERS / DOSSIERS EXCLUS ─────────────────────────────────
EXCLUDES = {
    ".git", ".claude", "node_modules", "hcs-proxy-worker",
    "deploy-erp.sh", "deploy-erp.py", "PROMPTS-CLAUDE-CODE.md",
    "picwish-pipeline.html"
}

# ── COULEURS TERMINAL ──────────────────────────────────────────
def green(t):  return f"\033[32m{t}\033[0m"
def orange(t): return f"\033[33m{t}\033[0m"
def red(t):    return f"\033[31m{t}\033[0m"

def should_exclude(name):
    return name in EXCLUDES or name.endswith(".md")

def ensure_remote_dir(ftp, path):
    """Crée le dossier distant s'il n'existe pas."""
    parts = path.replace("\\", "/").split("/")
    current = ""
    for part in parts:
        if not part:
            continue
        current += "/" + part
        try:
            ftp.mkd(current)
        except ftplib.error_perm:
            pass  # Dossier déjà existant

def upload_dir(ftp, local_path, remote_path, stats):
    """Upload récursif d'un dossier local vers le FTP."""
    ensure_remote_dir(ftp, remote_path)

    for item in sorted(local_path.iterdir()):
        if should_exclude(item.name):
            continue

        remote_item = f"{remote_path}/{item.name}"

        if item.is_dir():
            upload_dir(ftp, item, remote_item, stats)
        elif item.is_file():
            try:
                with open(item, "rb") as f:
                    ftp.storbinary(f"STOR {remote_item}", f)
                stats["uploaded"] += 1
                print(f"  ✓ {item.relative_to(LOCAL_DIR)}")
            except Exception as e:
                stats["errors"] += 1
                print(red(f"  ✗ {item.relative_to(LOCAL_DIR)} — {e}"))

def main():
    print()
    print("══════════════════════════════════════════")
    print("  ⬡ HCS ERP — Déploiement PlanetHoster")
    print("══════════════════════════════════════════")
    print(f"  Serveur : {FTP_HOST}")
    print(f"  Dossier : {REMOTE_DIR}")
    print(f"  Local   : {LOCAL_DIR}")
    print()

    # Vérification identifiants
    if "VOTRE_LOGIN" in FTP_USER or "VOTRE_MOT" in FTP_PASS:
        print(red("✗ Configure FTP_HOST, FTP_USER, FTP_PASS d'abord !"))
        print("  Option 1 — variables d'environnement :")
        print("    set FTP_HOST=ftp.highcoffeeshirts.com")
        print("    set FTP_USER=ton_login")
        print("    set FTP_PASS=ton_mdp")
        print("    python deploy-erp.py")
        print()
        print("  Option 2 — modifier deploy-erp.py directement (lignes 12-15)")
        sys.exit(1)

    print(orange("▶ Connexion au serveur FTP..."))

    stats = {"uploaded": 0, "errors": 0}

    try:
        with ftplib.FTP(FTP_HOST) as ftp:
            ftp.login(FTP_USER, FTP_PASS)
            ftp.set_pasv(True)
            print(green(f"✓ Connecté à {FTP_HOST}"))
            print()
            print(orange("▶ Upload des fichiers..."))
            print()

            upload_dir(ftp, LOCAL_DIR, REMOTE_DIR, stats)

        print()
        if stats["errors"] == 0:
            print(green(f"✅ Déploiement réussi ! {stats['uploaded']} fichiers uploadés"))
            print(f"   URL : https://highcoffeeshirts.com/erp/")
        else:
            print(orange(f"⚠ Terminé avec {stats['errors']} erreur(s) — {stats['uploaded']} fichiers uploadés"))

    except ftplib.all_errors as e:
        print(red(f"✗ Erreur FTP : {e}"))
        print("  Vérifie host, login et mot de passe")
        sys.exit(1)

    print()

if __name__ == "__main__":
    main()
