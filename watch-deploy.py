"""
HCS ERP — Watch & Deploy automatique
Surveille les fichiers locaux et les déploie sur PlanetHoster dès modification.
Usage : python watch-deploy.py
"""

import ftplib
import os
import sys
import time
from pathlib import Path

# ── CONFIGURATION ──────────────────────────────────────────────
FTP_HOST   = os.environ.get("FTP_HOST",   "node41-ca.n0c.com")
FTP_USER   = os.environ.get("FTP_USER",   "admin@highcoffeeshirts.com")
FTP_PASS   = os.environ.get("FTP_PASS",   "VO'uL5LE3s")
REMOTE_DIR = os.environ.get("REMOTE_DIR", "/public_html/erp")
LOCAL_DIR  = Path(__file__).parent.resolve()
POLL_SECS  = 2   # vérification toutes les 2 secondes

# ── EXTENSIONS SURVEILLÉES ─────────────────────────────────────
WATCH_EXT = {'.html', '.js', '.css', '.php', '.json'}

# ── EXCLUSIONS ────────────────────────────────────────────────
EXCLUDES = {
    ".git", ".claude", "node_modules", "hcs-proxy-worker",
    "watch-deploy.py", "deploy-erp.py", "deploy-erp.sh",
    "PROMPTS-CLAUDE-CODE.md"
}

# ── ENCODAGE WINDOWS ──────────────────────────────────────────
if sys.stdout.encoding != 'utf-8':
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')

# ── COULEURS ──────────────────────────────────────────────────
def green(t):  return f"\033[32m{t}\033[0m"
def orange(t): return f"\033[33m{t}\033[0m"
def red(t):    return f"\033[31m{t}\033[0m"
def grey(t):   return f"\033[90m{t}\033[0m"

def should_exclude(name):
    return name in EXCLUDES or name.endswith(".md")

def get_all_files():
    """Retourne tous les fichiers surveillés avec leur mtime."""
    snapshot = {}
    for item in LOCAL_DIR.rglob("*"):
        if item.is_file() and item.suffix in WATCH_EXT:
            # Vérifier si dans un dossier exclu
            excluded = any(p.name in EXCLUDES for p in item.parents)
            if not excluded and not should_exclude(item.name):
                snapshot[item] = item.stat().st_mtime
    return snapshot

def remote_path(local_file):
    """Convertit le chemin local en chemin FTP distant."""
    rel = local_file.relative_to(LOCAL_DIR)
    return REMOTE_DIR + "/" + str(rel).replace("\\", "/")

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
            pass

def upload_file(ftp, local_file):
    """Upload un fichier vers le FTP."""
    remote = remote_path(local_file)
    ensure_remote_dir(ftp, "/".join(remote.split("/")[:-1]))
    with open(local_file, "rb") as f:
        ftp.storbinary("STOR " + remote, f)

def connect_ftp():
    """Connexion FTP avec retry."""
    ftp = ftplib.FTP(FTP_HOST)
    ftp.login(FTP_USER, FTP_PASS)
    ftp.set_pasv(True)
    return ftp

# ── MAIN ──────────────────────────────────────────────────────
def main():
    print()
    print("════════════════════════════════════════════")
    print("  ⬡ HCS ERP — Watch & Deploy automatique")
    print("════════════════════════════════════════════")
    print(f"  Local   : {LOCAL_DIR}")
    print(f"  Serveur : {FTP_HOST}{REMOTE_DIR}")
    print(f"  Polling : toutes les {POLL_SECS}s")
    print()
    print(grey("  Ctrl+C pour arrêter"))
    print()

    # Snapshot initial
    print(orange("▶ Scan initial des fichiers..."))
    snapshot = get_all_files()
    print(green(f"✓ {len(snapshot)} fichiers surveillés"))
    print()
    print(orange("▶ En attente de modifications..."))
    print()

    ftp = None

    try:
        while True:
            time.sleep(POLL_SECS)
            current = get_all_files()
            changed = []

            for file, mtime in current.items():
                if file not in snapshot or snapshot[file] != mtime:
                    changed.append(file)

            if changed:
                # Connexion FTP (ou reconnexion si perdue)
                try:
                    if ftp is None:
                        ftp = connect_ftp()
                except Exception as e:
                    print(red(f"✗ Connexion FTP échouée : {e}"))
                    ftp = None
                    snapshot = current
                    continue

                for f in changed:
                    rel = f.relative_to(LOCAL_DIR)
                    try:
                        upload_file(ftp, f)
                        ts = time.strftime("%H:%M:%S")
                        print(f"  {grey(ts)} {green('✓')} {rel}")
                    except Exception as e:
                        # Tentative de reconnexion
                        try:
                            ftp = connect_ftp()
                            upload_file(ftp, f)
                            ts = time.strftime("%H:%M:%S")
                            print(f"  {grey(ts)} {green('✓')} {rel} (reconnecté)")
                        except Exception as e2:
                            print(f"  {red('✗')} {rel} — {e2}")

                snapshot = current

    except KeyboardInterrupt:
        print()
        print(orange("⏹ Watch arrêté."))
        if ftp:
            try:
                ftp.quit()
            except Exception:
                pass
        print()

if __name__ == "__main__":
    main()
