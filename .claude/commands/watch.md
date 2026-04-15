# Watch & Deploy — HCS ERP

Lance le watcher FTP qui synchronise automatiquement les fichiers locaux vers `https://highcoffeeshirts.com/erp/` dès qu'une modification est détectée.

```bash
cd "c:/Users/highc/HCS/hcs-erp" && python watch-deploy.py
```

Le watcher surveille tous les fichiers `.html`, `.js`, `.css`, `.php` du projet.
Dès qu'un fichier est sauvegardé, il est automatiquement uploadé sur PlanetHoster.

**Arrêter :** `Ctrl+C`
