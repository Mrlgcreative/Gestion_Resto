# Configuration du projet Gestion_Resto avec XAMPP

## Prérequis

-   XAMPP installé avec Apache et MySQL
-   PHP 8.x
-   Composer
-   Node.js et npm

---

## 1. Configuration du VirtualHost Apache

### Fichier : `C:\xampp\apache\conf\extra\httpd-vhosts.conf`

Ajouter les configurations suivantes :

```apache
# VirtualHost pour localhost (obligatoire)
<VirtualHost *:80>
    ServerAdmin admin@localhost
    DocumentRoot "C:/xampp/htdocs"
    ServerName localhost
</VirtualHost>

# Gestion Resto
<VirtualHost *:80>
    ServerAdmin admin@localhost
    DocumentRoot "C:/xampp/htdocs/Gestion_Resto/public"
    ServerName gestion-resto.local
    <Directory "C:/xampp/htdocs/Gestion_Resto/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

---

## 2. Configuration du fichier hosts Windows

### Fichier : `C:\Windows\System32\drivers\etc\hosts`

Ajouter la ligne suivante (nécessite les droits administrateur) :

```
127.0.0.1       gestion-resto.local
```

### Comment modifier le fichier hosts :

1. Ouvrir le **Bloc-notes** en tant qu'Administrateur
2. Fichier → Ouvrir → `C:\Windows\System32\drivers\etc\hosts`
3. Ajouter la ligne ci-dessus à la fin du fichier
4. Sauvegarder et fermer

---

## 3. Configuration du fichier .env

### Paramètres importants :

```env
APP_URL=http://gestion-resto.local

SESSION_PATH=/
SESSION_DOMAIN=gestion-resto.local
```

---

## 4. Lien symbolique pour le stockage des images

Exécuter la commande suivante dans le terminal :

```bash
php artisan storage:link
```

Cela crée un lien symbolique de `public/storage` vers `storage/app/public`.

---

## 5. Vider les caches après modification

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

---

## 6. Vérifications importantes

### Module mod_rewrite Apache

Vérifier que le module est activé dans `C:\xampp\apache\conf\httpd.conf` :

```apache
LoadModule rewrite_module modules/mod_rewrite.so
```

(La ligne ne doit pas être commentée avec `#`)

### Redémarrer Apache

Après toute modification de configuration, redémarrer Apache via le panneau de contrôle XAMPP.

---

## Accès à l'application

URL : **http://gestion-resto.local**

---

## Structure des fichiers de stockage

```
storage/
└── app/
    └── public/
        ├── products/     # Images des produits
        └── settings/     # Logo et autres fichiers de configuration
```

Les fichiers uploadés sont accessibles via : `http://gestion-resto.local/storage/...`

---

## Dépannage

### Les images ne s'affichent pas

1. Vérifier que le lien symbolique existe : `php artisan storage:link`
2. Vérifier que `APP_URL` est correct dans `.env`
3. Vider le cache : `php artisan config:clear`

### Erreur 404 sur les routes

1. Vérifier que `mod_rewrite` est activé
2. Vérifier le fichier `.htaccess` dans le dossier `public/`
3. Redémarrer Apache

### Problème de session

Vérifier les paramètres de session dans `.env` :

-   `SESSION_DOMAIN` doit correspondre au domaine utilisé
-   `SESSION_PATH` doit être `/`

---

## Date de configuration

21 décembre 2025
