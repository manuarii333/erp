# Check Project — Vérification du bon dossier HCS ERP

Vérifie que Claude travaille dans le bon dossier (`hcs-erp` et non `hcs-erp ok`) et que tout est en ordre.

```bash
echo "=== Dossier de travail ===" && pwd && echo "" && echo "=== Git status ===" && git -C "c:/Users/highc/HCS/hcs-erp" log --oneline -3 && echo "" && echo "=== Fichiers modifies non commites ===" && git -C "c:/Users/highc/HCS/hcs-erp" status --short && echo "" && echo "=== Serveur (fichiers cles) ===" && python -c "
import ftplib, sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')
ftp = ftplib.FTP('ftp.highcoffeeshirts.com')
ftp.login('admin@highcoffeeshirts.com', \"VO'uL5LE3s\")
ftp.set_pasv(True)
for f in ['/public_html/erp/index.html', '/public_html/erp/js/app.js']:
    lines = []
    ftp.retrlines('LIST ' + f, lines.append)
    parts = lines[0].split()
    print(f'  {parts[-4]:>8} octets  {parts[-3]} {parts[-2]}  {parts[-1].split(\"/\")[-1]}')
ftp.quit()
"
```

**Dossier correct :** `c:/Users/highc/HCS/hcs-erp/`
**Ne pas utiliser :** `c:/Users/highc/HCS/hcs-erp ok/`
