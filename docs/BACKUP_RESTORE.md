# Sauvegarde et restauration

Dune ERP stocke toute la donnée métier (commandes, paiements, stock,
audit) dans MySQL. Aucun fichier n'est écrit hors de la base en V1 (pas
de photos produit ni de pièces jointes), donc **sauvegarder la base
suffit** à restaurer intégralement l'état du système.

## Sauvegarde

### Sauvegarde manuelle ponctuelle

```bash
mysqldump -u dune -p \
  --single-transaction --routines --triggers \
  dune_erp > dune_erp_$(date +%Y%m%d_%H%M%S).sql
```

`--single-transaction` évite de verrouiller les tables pendant la
sauvegarde (moteur InnoDB) — indispensable pour ne pas bloquer la caisse
pendant le service.

### Sauvegarde automatique quotidienne (cron)

Créer `/usr/local/bin/dune-erp-backup.sh` :

```bash
#!/bin/bash
set -euo pipefail

BACKUP_DIR="/var/backups/dune-erp"
KEEP_DAYS=30
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p "$BACKUP_DIR"

mysqldump -u dune -p"$DB_PASSWORD" \
  --single-transaction --routines --triggers \
  dune_erp | gzip > "$BACKUP_DIR/dune_erp_${DATE}.sql.gz"

# Purge les sauvegardes de plus de $KEEP_DAYS jours
find "$BACKUP_DIR" -name "dune_erp_*.sql.gz" -mtime +$KEEP_DAYS -delete
```

```bash
chmod +x /usr/local/bin/dune-erp-backup.sh
```

Cron (tous les jours à 3h du matin, après la clôture de caisse) :

```
0 3 * * * DB_PASSWORD='...' /usr/local/bin/dune-erp-backup.sh >> /var/log/dune-erp-backup.log 2>&1
```

**Copier les archives générées vers un stockage hors du serveur**
(S3, autre machine, etc.) — une sauvegarde qui reste uniquement sur le
serveur qu'elle est censée protéger ne protège de rien en cas de panne
disque ou de compromission du serveur.

## Restauration

⚠️ Une restauration écrase toutes les données actuelles de la base
cible. Toujours vérifier qu'on restaure vers le bon environnement.

```bash
# Si le fichier est compressé :
gunzip -c dune_erp_20260101_030000.sql.gz > /tmp/restore.sql

mysql -u dune -p dune_erp < /tmp/restore.sql
```

Après restauration :

```bash
php artisan config:clear
php artisan cache:clear
```

## Test de restauration (recommandé)

Une sauvegarde n'a de valeur que si elle a été testée. Vérifier
périodiquement (ex. mensuellement) qu'une sauvegarde récente se restaure
sans erreur, idéalement sur une base de test dédiée :

```bash
mysql -u root -e "CREATE DATABASE dune_erp_restore_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u dune -p dune_erp_restore_test < /tmp/restore.sql
mysql -u root -e "DROP DATABASE dune_erp_restore_test;"
```

## Ce qui n'est PAS couvert par la sauvegarde base de données

- Le fichier `.env` (secrets, clé d'application) — le sauvegarder
  séparément et de façon sécurisée (il n'est jamais versionné dans git).
- Le code applicatif — déjà versionné dans le dépôt git ; s'assurer que
  le commit déployé en production est identifiable (tag ou hash de
  commit consigné à chaque déploiement).
