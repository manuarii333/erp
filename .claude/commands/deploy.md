# Deploy — HCS ERP vers PlanetHoster

Déploie tous les fichiers du projet vers `https://highcoffeeshirts.com/erp/` et vérifie que les fichiers clés sont bien uploadés.

```bash
cd "c:/Users/highc/HCS/hcs-erp" && python deploy-erp.py && python -c "
import ftplib, sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')
ftp = ftplib.FTP('ftp.highcoffeeshirts.com')
ftp.login('admin@highcoffeeshirts.com', \"VO'uL5LE3s\")
ftp.set_pasv(True)
print()
print('Verification fichiers cles sur le serveur:')
for f in [
    '/public_html/erp/index.html',
    '/public_html/erp/js/app.js',
    '/public_html/erp/modules/planning-dashboard.html',
]:
    lines = []
    ftp.retrlines('LIST ' + f, lines.append)
    parts = lines[0].split()
    print(f'  {parts[-4]:>8} octets  {parts[-3]} {parts[-2]}  {parts[-1].split(\"/\")[-1]}')
ftp.quit()
"
```

Après le déploiement, ouvre un **onglet incognito** pour tester sans cache.
