# Cache-Bust — Forcer le rechargement navigateur

Met à jour le numéro de version dans `index.html` pour forcer tous les navigateurs à recharger les scripts JS/CSS, puis déploie sur le serveur.

À utiliser quand : les utilisateurs voient encore l'ancienne version malgré Ctrl+Shift+R.

```bash
python -c "
import re, datetime
date = datetime.datetime.now().strftime('%Y%m%d%H')
path = 'c:/Users/highc/HCS/hcs-erp/index.html'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()
content = re.sub(r'\?v=\d+', f'?v={date}', content)
with open(path, 'w', encoding='utf-8') as f:
    f.write(content)
print(f'Version mise a jour : v={date}')
" && python -c "
import ftplib, sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')
ftp = ftplib.FTP('ftp.highcoffeeshirts.com')
ftp.login('admin@highcoffeeshirts.com', \"VO'uL5LE3s\")
ftp.set_pasv(True)
with open('c:/Users/highc/HCS/hcs-erp/index.html', 'rb') as f:
    ftp.storbinary('STOR /public_html/erp/index.html', f)
print('index.html deploye avec nouveau cache-bust')
ftp.quit()
"
```
