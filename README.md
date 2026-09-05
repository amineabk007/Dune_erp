# Dune ERP V1

Système de gestion intégré pour Dune Rooftop (Marrakech) — restaurant, caisse,
stock, réservations, événements et pilotage. Construit avec Laravel 12, PHP
8.4+, MySQL 8, Blade, Bootstrap 5 et Livewire, selon le cahier des charges
fonctionnel et technique du projet.

Ce dépôt est développé **par phases** (voir "État d'avancement" ci-dessous).
Chaque phase livre des fonctionnalités réelles et testées — pas de maquette
statique, pas de bouton factice.

## Stack technique

- Laravel 12 / PHP 8.4+
- MySQL 8 (DECIMAL pour tous les montants, BIGINT UNSIGNED pour les clés)
- Blade + Bootstrap 5 (SCSS compilé via Vite)
- Livewire (écrans interactifs, à partir de la phase POS/Cuisine/Bar)
- [spatie/laravel-permission](https://spatie.be/docs/laravel-permission) pour
  les rôles et permissions (tables `roles`, `permissions`,
  `model_has_roles`, `role_has_permissions` — conforme au nommage attendu par
  le cahier des charges)
- Policies Laravel natives pour l'autorisation contextuelle (ex. un
  utilisateur ne peut pas désactiver son propre compte)

## Installation

### Prérequis

- PHP 8.4+ avec extensions `pdo_mysql`, `mbstring`
- Composer 2.x
- Node.js 20+ / npm
- MySQL 8

### Étapes

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configurer `.env` avec vos identifiants MySQL :

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dune_erp
DB_USERNAME=dune
DB_PASSWORD=votre_mot_de_passe
```

Créer la base de données, migrer et semer les données de démonstration :

```bash
mysql -u root -e "CREATE DATABASE dune_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate --seed
```

Compiler les assets front-end :

```bash
npm install
npm run build   # ou `npm run dev` en développement
```

Lancer le serveur de développement :

```bash
php artisan serve
```

### Tests automatisés

Les tests s'exécutent contre une base MySQL dédiée (`dune_erp_testing`), pas
sqlite, afin de rester fidèles au moteur de production (DECIMAL, contraintes,
etc.) :

```bash
mysql -u root -e "CREATE DATABASE dune_erp_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan test
```

Les identifiants de la base de test sont définis dans `phpunit.xml`.

## Comptes de démonstration

⚠️ **Développement local uniquement.** Ne jamais utiliser ces identifiants en
production ; créez de vrais comptes via l'écran Utilisateurs une fois en
production, puis désactivez/supprimez ces comptes de démo.

| Rôle | E-mail | Mot de passe |
|---|---|---|
| admin | admin@dune-erp.test | password |
| direction | direction@dune-erp.test | password |
| manager | manager@dune-erp.test | password |
| caissier | caissier@dune-erp.test | password |
| serveur | serveur@dune-erp.test | password |
| cuisine | cuisine@dune-erp.test | password |
| bar | bar@dune-erp.test | password |
| stock | stock@dune-erp.test | password |
| comptable | comptable@dune-erp.test | password |

## Rôles & permissions

Le rôle **admin** a un accès global (bypass total, voir
`AppServiceProvider::boot()`). Tous les autres rôles fonctionnent selon le
principe du moindre privilège : chaque action sensible est protégée par une
permission explicite (`orders.create`, `payments.refund`, `cash.close`,
`stock.adjust`, `users.manage`, `audit.view`, etc. — catalogue complet dans
`database/seeders/PermissionSeeder::CATALOG`).

Un administrateur peut ajuster les permissions de chaque rôle (sauf `admin`,
toujours global) depuis l'écran **Rôles & permissions**. Chaque changement est
journalisé dans l'audit.

Les comptes ne sont **jamais supprimés** : ils sont désactivés (`is_active =
false`), ce qui préserve l'intégrité de l'historique (commandes, paiements,
logs d'audit) tout en bloquant la connexion. Un utilisateur ne peut pas
désactiver son propre compte, pour éviter un auto-verrouillage.

## Audit / traçabilité

Toute action sensible (création/modification d'utilisateur, changement de
permissions d'un rôle, désactivation de compte, et — dans les phases
suivantes — annulations, remboursements, remises, ajustements de stock,
clôtures de caisse) est enregistrée dans `audit_logs` via
`App\Services\AuditService`. Les enregistrements d'audit sont **immuables** :
le modèle `AuditLog` lève une exception si on tente de les modifier ou de les
supprimer.

## État d'avancement

### Phase 1 — Fondation ✅ Implémentée et testée

- Projet Laravel 12 / PHP 8.4 / MySQL 8, structure MVC + Services + Form
  Requests + Policies.
- Authentification (login/logout), hachage des mots de passe, limitation des
  tentatives de connexion (5 essais par couple e-mail+IP), sessions
  sécurisées, déconnexion automatique si le compte est désactivé en cours de
  session.
- Rôles & permissions granulaires (9 rôles du cahier des charges), écran
  d'administration des permissions par rôle.
- Gestion des utilisateurs (créer, modifier, assigner des rôles, activer/
  désactiver — jamais de suppression).
- Infrastructure d'audit (`audit_logs`, `AuditService`, journal consultable
  et filtrable).
- Interface Bootstrap 5 avec navigation adaptée aux permissions de
  l'utilisateur connecté.
- 23 tests automatisés (authentification, autorisation, gestion des
  utilisateurs, gestion des rôles, immutabilité de l'audit) — tous verts
  contre une vraie base MySQL 8.

### Phase 2 — Référentiels ✅ Implémentée et testée

- Zones et tables (plan de salle), catégories et produits (avec historique des
  prix), clients. CRUD complet gardé par permission par action, jamais de
  suppression quand des données dépendantes existent (zone avec tables,
  catégorie avec produits).
- 11 tests supplémentaires (34 au total).

### Phase 3 — POS / Caisse ✅ Implémentée et testée

- Commandes (table ou vente directe), numérotées automatiquement, avec
  articles ajoutés depuis un catalogue **Livewire** entièrement réactif
  (recherche, filtre catégorie, +/- quantité, totaux en direct) — sans
  rechargement de page.
- Calcul serveur strict du sous-total, de la taxe et du total à chaque
  modification ; le client ne peut jamais imposer un montant.
- Remises soumises à permission dédiée (`orders.discount`, réservée aux
  rôles manager/direction/admin) et tracées dans l'audit avec motif.
- Paiements multi-moyens (espèces/carte/virement/autre), paiement partiel/
  fractionné, impossible de dépasser le solde dû. Le paiement complet passe
  la commande à `paid` et la table à `cleaning`.
- Remboursements tracés (motif obligatoire), jamais de suppression d'un
  paiement ou d'une commande payée.
- Sessions de caisse : une seule session ouverte à la fois, mouvements de
  caisse (entrée/sortie), clôture avec calcul automatique du montant attendu
  et de l'écart par rapport au comptage réel.
- Documents imprimables : addition/reçu par commande, rapport de caisse par
  session.
- Toutes les opérations financières (paiement, remboursement, clôture de
  caisse) sont exécutées dans des transactions de base de données.
- 22 tests supplémentaires (56 au total), plus une vérification manuelle du
  parcours complet (ouverture de caisse → commande → ajout d'articles en
  direct → encaissement → commande soldée) dans un vrai navigateur.

Modules restants (plan de salle visuel/réservations, écrans cuisine/bar,
recettes, stock, achats, CRM événements, dépenses, personnel, rapports de
pilotage) seront livrés phase par phase, en suivant le plan de développement
du cahier des charges (section 25), chacune vérifiée et testée avant de
passer à la suivante.

## Licence

Projet propriétaire — Dune Rooftop Marrakech.
