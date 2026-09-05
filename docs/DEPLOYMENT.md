# Guide de déploiement en production

## Prérequis serveur

- PHP 8.4+ avec extensions `pdo_mysql`, `mbstring`, `bcmath` (voir note
  ci-dessous), `intl`, `opcache`
- MySQL 8
- Nginx (ou Apache) + un utilisateur système dédié (jamais `root`)
- Composer 2.x, Node.js 20+ pour la compilation des assets
- Un certificat TLS valide (Let's Encrypt ou équivalent) — **obligatoire**,
  voir la section Sécurité ci-dessous

> **Note bcmath** : cet environnement de développement ne disposait pas de
> l'extension `bcmath` ; tous les calculs monétaires ont donc été écrits en
> arithmétique flottante native + `round()` + comparaisons de chaînes sur
> les valeurs `decimal:N` castées (voir `Order::balanceDue()`,
> `PaymentService`, etc.). C'est fonctionnellement correct pour les montants
> manipulés ici, mais si `bcmath` est disponible en production, l'installer
> ne change rien au comportement actuel (le code ne l'utilise pas) ; ce
> n'est donc pas un pré-requis strict, juste une amélioration possible si un
> futur mainteneur préfère migrer vers `bcmath` pour plus de rigueur
> décimale sur de très gros volumes.

## Déploiement initial

```bash
git clone <url-du-depot> dune-erp
cd dune-erp

composer install --no-dev --optimize-autoloader
npm install
npm run build

cp .env.example .env
php artisan key:generate
```

Éditer `.env` pour la production (voir la checklist ci-dessous), puis :

```bash
php artisan migrate --force
php artisan storage:link
php artisan db:seed --class=PermissionSeeder --force
php artisan db:seed --class=RoleSeeder --force
# Ne PAS lancer UserSeeder ni ReferenceDataSeeder en production : ce sont
# des données de démonstration (comptes "@dune-erp.test", produits fictifs).
# Créer les vrais comptes via l'écran Utilisateurs une fois connecté en admin.
```

`storage:link` crée le lien symbolique `public/storage` → `storage/app/public`,
nécessaire pour que les photos de produits uploadées depuis l'écran
Produits soient servies par le serveur web. À relancer après chaque
nouveau déploiement si le lien n'existe pas déjà (il n'est pas versionné
dans git).

Créer un premier compte admin directement en base (aucun compte
n'existe encore pour se connecter à l'écran Utilisateurs) :

```bash
php artisan tinker
>>> $u = App\Models\User::create(['name' => 'Admin', 'email' => 'admin@dune-rooftop.ma', 'password' => Hash::make('changez-moi'), 'is_active' => true]);
>>> $u->assignRole('admin');
```

Changer ce mot de passe immédiatement après la première connexion.

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Checklist `.env` de production

| Variable | Valeur attendue |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` — **jamais `true` en production** (fuite d'informations sensibles dans les pages d'erreur) |
| `APP_URL` | URL publique réelle (`https://...`) |
| `SESSION_SECURE_COOKIE` | `true` — le site est servi en HTTPS, les cookies de session ne doivent jamais transiter en clair |
| `SESSION_ENCRYPT` | `true` recommandé |
| `DB_PASSWORD` | Mot de passe fort dédié, différent de tout autre environnement |
| `MAIL_MAILER` | `smtp` (ou tout transport supporté par Laravel) — voir ci-dessous |

## Notifications par e-mail

Deux notifications sont envoyées automatiquement : confirmation de
réservation au client, et alerte de stock bas aux utilisateurs disposant
de `stock.adjust`. Par défaut (`.env.example`), `MAIL_MAILER=log` — les
e-mails sont écrits dans `storage/logs/laravel.log` au lieu d'être
réellement envoyés, ce qui est volontaire pour le développement et les
tests (aucun risque d'envoyer un vrai e-mail par accident).

Pour activer l'envoi réel en production, configurer un transport SMTP
réel dans `.env` :

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.votre-fournisseur.com
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_SCHEME=tls
MAIL_FROM_ADDRESS=reservations@dune-rooftop.ma
MAIL_FROM_NAME="Dune Rooftop"
```

Un échec d'envoi (identifiants invalides, serveur SMTP indisponible)
est journalisé dans les logs applicatifs mais ne bloque jamais l'action
métier (confirmer une réservation, enregistrer un mouvement de stock
reste possible même si l'e-mail échoue) — voir `NotificationService`.

## Serveur web (exemple Nginx)

```nginx
server {
    listen 443 ssl http2;
    server_name dune-rooftop.ma;
    root /var/www/dune-erp/public;

    ssl_certificate     /etc/letsencrypt/live/dune-rooftop.ma/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/dune-rooftop.ma/privkey.pem;

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}

server {
    listen 80;
    server_name dune-rooftop.ma;
    return 301 https://$host$request_uri;
}
```

## Tâches planifiées

Le scheduler Laravel (`php artisan schedule:run`) doit tourner chaque
minute via cron, même si aucune tâche planifiée n'est encore définie en
V1 — cela évite un oubli quand une sera ajoutée (ex. purge de sessions,
rapports automatiques) :

```
* * * * * cd /var/www/dune-erp && php artisan schedule:run >> /dev/null 2>&1
```

## Mise à jour d'une version existante

```bash
git pull
composer install --no-dev --optimize-autoloader
npm install && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Toujours **sauvegarder la base de données avant toute migration** en
production — voir `docs/BACKUP_RESTORE.md`.

## Sécurité — rappel

- HTTPS obligatoire : `SESSION_SECURE_COOKIE=true` n'a de sens que
  derrière un reverse proxy/serveur qui termine du TLS. Sans HTTPS, les
  cookies de session ne seront jamais envoyés par le navigateur et
  personne ne pourra se connecter — ne pas activer ce réglage tant que le
  certificat n'est pas en place.
- Le middleware `SecurityHeaders` (X-Frame-Options, X-Content-Type-Options,
  Referrer-Policy, Permissions-Policy, HSTS conditionnel) s'applique déjà à
  toutes les réponses, aucune configuration serveur supplémentaire n'est
  nécessaire pour ces en-têtes.
- Désactiver ou supprimer les comptes de démonstration
  (`*@dune-erp.test`) avant l'ouverture au personnel réel.
