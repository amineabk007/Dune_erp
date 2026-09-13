# Dune ERP V1

Système de gestion intégré pour Dune Rooftop (Marrakech) — restaurant, caisse,
stock, réservations, événements et pilotage. Construit avec Laravel 12, PHP
8.4+, MySQL 8, Blade, Bootstrap 5 et Livewire, selon le cahier des charges
fonctionnel et technique du projet.

Ce dépôt est développé **par phases** (voir "État d'avancement" ci-dessous).
Chaque phase livre des fonctionnalités réelles et testées — pas de maquette
statique, pas de bouton factice. Les 10 phases prévues sont maintenant
livrées ; voir `docs/ACCEPTANCE_CHECKLIST.md` pour la checklist de recette
complète.

## Documentation

- [`docs/PERMISSIONS.md`](docs/PERMISSIONS.md) — matrice des rôles et
  permissions, comment les ajuster.
- [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) — déploiement en production
  (prérequis, `.env`, Nginx, mise à jour).
- [`docs/BACKUP_RESTORE.md`](docs/BACKUP_RESTORE.md) — sauvegarde et
  restauration de la base de données.
- [`docs/ACCEPTANCE_CHECKLIST.md`](docs/ACCEPTANCE_CHECKLIST.md) —
  checklist de recette fonctionnelle par module.

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

Voir [`docs/PERMISSIONS.md`](docs/PERMISSIONS.md) pour la matrice complète
rôle × permission. Résumé :

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

### Phase 4 — Opérations ✅ Implémentée et testée

- Plan de salle visuel (zones + tables, couleur par statut), avec ouverture
  directe d'une commande depuis une table libre, transfert d'une commande
  vers une autre table, et remise en service d'une table en nettoyage.
- Réservations : client, date/heure, couverts, affectation d'une ou
  plusieurs tables, prévention des doubles réservations sur un créneau
  (fenêtre de 2h par table), cycle de statuts complet (pending → confirmed
  → seated → completed, ou cancelled/no_show). Le flux Réservation → Table
  → Commande fonctionne de bout en bout (une réservation installée peut
  générer sa commande en un clic, avec la bonne table déjà affectée).
- Écrans Cuisine et Bar séparés (Livewire, rafraîchissement automatique
  toutes les 5s), filtrés par destination de l'article (déterminée par le
  type de catégorie du produit), avec transition de statut par article
  (envoyé → préparation → prêt → servi) tracée avec horodatage et
  utilisateur.
- 14 tests supplémentaires (70 au total), plus une vérification manuelle en
  navigateur du parcours commande → envoi cuisine → écran cuisine → passage
  en préparation, en direct sans rechargement de page.

### Phase 5 — Stock ✅ Implémentée et testée

- Fiches ingrédients (unité, stock actuel, minimum, coût unitaire) avec
  ledger de mouvements immuable (`stock_movements`) : chaque changement de
  stock — ouverture, ajustement, casse, retour, transfert, consommation
  vente — est un enregistrement séparé et traçable ; le stock ne se modifie
  jamais silencieusement par simple édition du formulaire.
- Recettes / fiches techniques par produit (ingrédients + quantités +
  rendement) avec calcul automatique du coût matière par portion, comparé
  au prix de vente.
- **Vente → consommation stock** : au moment où une commande est
  intégralement payée, les ingrédients de chaque article vendu (selon sa
  recette et son rendement) sont automatiquement décrémentés et
  enregistrés comme mouvement `sale_consumption` — exactement le critère
  d'acceptation du cahier des charges. Les articles annulés ne consomment
  rien.
- Inventaire physique (correction tracée vers la quantité comptée) et
  alertes de stock bas (liste des ingrédients à ou sous leur minimum).
- 11 tests supplémentaires (81 au total, tous verts contre MySQL réel),
  plus une vérification manuelle en navigateur de la création d'ingrédient,
  de la création de recette (ajout/suppression dynamique de lignes) et de
  l'affichage du coût matière.

### Phase 6 — Achats & fournisseurs ✅ Implémentée et testée

- Fiches fournisseurs (contact, coordonnées, actif/inactif) et commandes
  d'achat multi-lignes (ingrédient, quantité, coût unitaire) avec calcul
  automatique du montant total.
- Cycle de statut d'une commande : `ordered` → `received` (réception qui
  entre chaque ligne en stock via un mouvement `purchase` par ingrédient et
  met à jour le coût unitaire de l'ingrédient au dernier prix d'achat) ou
  `cancelled` (impossible une fois la commande réceptionnée — l'entrée en
  stock qu'elle a générée reste définitive et tracée).
- Chaque création, réception et annulation de commande est auditée.
- 8 tests supplémentaires (89 au total, tous verts contre MySQL réel), plus
  une vérification manuelle en navigateur du parcours complet fournisseur →
  commande → réception, avec mise à jour du stock et du coût observée en
  direct.

### Phase 7 — Finance ✅ Implémentée et testée

- Dépenses manuelles (catégorie, description, montant, date, mode de
  paiement, fournisseur optionnel) avec journal d'audit sur chaque
  création/modification ; pas de suppression, seulement une correction
  tracée.
- Rapports (période filtrable) : chiffre d'affaires réellement encaissé
  (basé sur les paiements non remboursés, ventilé par mode de paiement),
  nombre de commandes payées et ticket moyen, produits les plus vendus,
  dépenses par catégorie, et un résultat net simplifié (CA encaissé −
  dépenses − achats réceptionnés sur la période). C'est une approximation
  V1 volontairement simple, documentée comme telle dans le code — pas de
  comptabilité d'engagement ni de coût matière consommé au réel.
- Tableau de bord avec KPIs réels pour les rôles ayant `reports.view` : CA
  du jour, commandes en cours, occupation des tables, alertes de stock bas,
  statut de la session de caisse en cours.
- 9 tests supplémentaires (98 au total, tous verts contre MySQL réel), plus
  une vérification manuelle en navigateur du tableau de bord, de la
  création d'une dépense et de l'écran de rapports avec des données réelles
  (commande payée, dépense), et de l'accès refusé (403) pour un rôle sans
  `reports.view`.

### Phase 8 — Événements & Personnel ✅ Implémentée et testée

- Événements privés (nom, date/heure, client optionnel, nombre d'invités,
  montant total du devis) avec cycle de statut `pending` → `confirmed` →
  `completed`, annulable (avec motif obligatoire) depuis les deux états
  ouverts ; les transitions non autorisées (ex. terminer un événement en
  attente) sont rejetées.
- Acomptes et paiements multiples par événement (`event_payments` : type
  acompte/solde/autre, méthode, montant), avec solde dû calculé
  automatiquement et impossibilité de dépasser le montant total du devis ;
  un événement annulé ne peut plus recevoir de paiement.
- Fiches personnel (poste, coordonnées, date d'embauche, salaire,
  activer/désactiver — jamais de suppression) avec liaison optionnelle vers
  un compte utilisateur existant pour la connexion (un compte ne peut être
  lié qu'à un seul employé).
- 12 tests supplémentaires (110 au total, tous verts contre MySQL réel),
  plus une vérification manuelle en navigateur du parcours création
  d'événement → confirmation → encaissement d'acompte, et de la création /
  désactivation d'un employé.

### Phase 9 — Audit, QA, sécurité et performance ✅ Implémentée et testée

Passe de revue transverse sur les phases 1 à 8, avant la mise en
production :

- **Audit** : comblé les trous restants — création/modification/suppression
  de recettes, modification des fiches ingrédients (coût, stock minimum) et
  remise en service d'une table depuis le plan de salle sont désormais
  tracées dans `audit_logs`, au même titre que tout le reste (aucune faille
  de traçabilité identifiée sur les actions financières ou de stock).
- **Performance** : ajout d'index composites/simples sur les colonnes
  réellement filtrées par le tableau de bord et la caisse en production —
  `payments(refunded, created_at)` (interrogée à chaque calcul de CA),
  `cash_sessions.status` (session ouverte, vérifiée à chaque paiement),
  `restaurant_tables.status`, `cash_movements.type`, `ingredients.is_active`.
  Revue des requêtes N+1 sur tous les écrans : aucune trouvée, chaque
  `index()`/`show()` charge déjà les relations utilisées par sa vue.
- **Sécurité** : nouveau middleware `SecurityHeaders` (X-Frame-Options,
  X-Content-Type-Options, Referrer-Policy, Permissions-Policy, et HSTS
  quand la requête est en HTTPS) appliqué à toute réponse ; documentation
  de `SESSION_SECURE_COOKIE` dans `.env.example` pour la mise en
  production HTTPS ; limitation de tentatives de connexion déjà en place
  depuis la Phase 1 (5 essais par couple e-mail + IP) confirmée
  fonctionnellement équivalente à un throttle middleware classique.
- **Validation** : ajout de bornes maximales manquantes sur les montants et
  quantités saisissables (ingrédients, événements) pour empêcher la saisie
  de valeurs aberrantes.
- 2 tests supplémentaires ciblés (112 au total, tous verts contre MySQL
  réel) plus une vérification manuelle en navigateur (en-têtes de sécurité
  visibles sur toute réponse, parcours recette modifiée/supprimée toujours
  fonctionnel après l'ajout des logs d'audit).

### Phase 10 — Production readiness & documentation ✅ Implémentée

Dernière phase du plan de développement (section 25 du cahier des
charges) : rendre le système prêt à être exploité, documenté au-delà du
code.

- Guide de déploiement complet (`docs/DEPLOYMENT.md`) : prérequis serveur,
  étapes d'installation initiale, checklist des variables `.env` de
  production, configuration Nginx d'exemple, procédure de mise à jour.
- Procédure de sauvegarde et restauration (`docs/BACKUP_RESTORE.md`) :
  script de sauvegarde quotidienne automatisée, commandes de restauration,
  et rappel qu'une sauvegarde non testée n'est pas une sauvegarde.
- Guide des rôles et permissions détaillé (`docs/PERMISSIONS.md`) :
  matrice complète rôle × module, avec le rôle métier prévu pour chacun.
- Checklist de recette fonctionnelle par module
  (`docs/ACCEPTANCE_CHECKLIST.md`), incluant une section transparente sur
  ce qui reste volontairement hors périmètre V1.

Toutes les 10 phases du plan de développement sont maintenant livrées,
testées (112 tests automatisés contre MySQL réel) et documentées.

### Phase 11 — Création de rôles depuis l'interface ✅ Implémentée et testée

Extension post-V1 demandée par l'exploitant : gérer les rôles métier sans
toucher au code.

- Un administrateur peut créer un nouveau rôle (nom + permissions
  initiales) directement depuis l'écran **Rôles & permissions**, et le
  rendre immédiatement assignable à un utilisateur — plus besoin de
  modifier `RoleSeeder` et redéployer.
- Un rôle ne peut être supprimé que s'il n'est assigné à **aucun**
  utilisateur (sinon suppression refusée, pour ne pas retirer
  silencieusement l'accès de quelqu'un) ; le rôle `admin` ne peut jamais
  être supprimé. Ces deux garde-fous sont appliqués explicitement dans le
  contrôleur, pas seulement dans la policy — nécessaire ici car le bypass
  `Gate::before` qui donne un accès global à `admin` court-circuiterait
  sinon silencieusement ces règles pour l'acteur `admin` lui-même,
  exactement comme la protection anti-auto-verrouillage déjà en place sur
  la désactivation d'utilisateur.
- Création et suppression de rôle journalisées dans l'audit.
- 6 tests supplémentaires (118 au total, tous verts contre MySQL réel),
  plus une vérification manuelle en navigateur confirmant que le bouton
  de suppression n'apparaît que pour les rôles sans utilisateur assigné.

### Phase 12 — Photos des plats ✅ Implémentée et testée

Extension post-V1 : donner un visage aux produits, dans le catalogue
POS comme dans la fiche produit.

- Upload d'une photo par produit (JPG/PNG/WebP, 2 Mo max) depuis l'écran
  Produits, avec remplacement ou retrait possible. Le fichier est stocké
  sur le disque `public` (`storage/app/public/products`), servi via le
  lien symbolique `public/storage` — voir `docs/DEPLOYMENT.md`.
- La photo s'affiche partout où le produit apparaît visuellement : la
  liste des produits, la fiche produit, et surtout les cartes du
  catalogue dans l'écran de prise de commande (POS) — c'est là que
  serveurs et caissiers en profitent le plus au quotidien. Un produit
  sans photo affiche un espace réservé neutre plutôt qu'une case vide.
- Remplacer ou retirer une photo supprime l'ancien fichier du disque
  (pas de fichiers orphelins qui s'accumulent).
- 3 tests supplémentaires (121 au total, tous verts contre MySQL réel,
  utilisant `Storage::fake()` pour ne jamais toucher au disque réel
  pendant les tests), plus une vérification manuelle en navigateur de
  l'upload et de son affichage dans le catalogue POS.

### Phase 13 — Notifications par e-mail ✅ Implémentée et testée

Extension post-V1 : prévenir automatiquement le client et l'équipe
plutôt que de compter sur une vérification manuelle des écrans.

- **Confirmation de réservation** : dès qu'une réservation passe au
  statut `confirmed`, le client reçoit un e-mail (s'il a une adresse
  enregistrée) avec la date, l'heure et le nombre de couverts.
- **Alerte de stock bas** : dès qu'un mouvement de stock fait franchir à
  un ingrédient son seuil minimum, un e-mail est envoyé à tous les
  utilisateurs disposant de `stock.adjust` (manager, stock, direction,
  admin). L'alerte ne part qu'une seule fois au moment du franchissement
  — les mouvements suivants pendant que le stock reste bas ne renvoient
  pas d'e-mail.
- Un échec d'envoi (SMTP indisponible) est journalisé mais **n'interrompt
  jamais l'action métier** sous-jacente — confirmer une réservation ou
  enregistrer un mouvement de stock reste possible même si l'e-mail
  échoue (`NotificationService::send()`).
- Par défaut, `MAIL_MAILER=log` écrit les e-mails dans
  `storage/logs/laravel.log` au lieu de les envoyer réellement — aucun
  risque d'envoi accidentel en développement ou pendant les tests ; voir
  `docs/DEPLOYMENT.md` pour activer un vrai transport SMTP en
  production. Les SMS restent hors périmètre (nécessiteraient un
  fournisseur tiers type Twilio).
- 4 tests supplémentaires (125 au total, tous verts contre MySQL réel,
  utilisant `Mail::fake()`), plus une vérification manuelle de bout en
  bout en navigateur confirmant que l'e-mail de réservation et l'alerte
  de stock bas sont bien écrits dans les logs avec le bon destinataire
  et le bon contenu.

### Phase 14 — Confort salle/cuisine (retours d'usage réel) ✅ Implémentée et testée

Cinq améliorations demandées après les premiers tests grandeur nature du
restaurant, toutes centrées sur ce qui se passe concrètement en salle et
en cuisine pendant le coup de feu.

- **Durée d'occupation des tables** : chaque table occupée sur le plan de
  salle affiche depuis combien de temps ("Occupée depuis 45 min"),
  calculé et rafraîchi côté navigateur toutes les 30 secondes — aucune
  requête serveur supplémentaire.
- **Pertes de plats préparés ou d'ingrédients bruts** (`Déclarer une
  perte`, sous Stock) : un même écran, avec un bouton pour basculer entre
  les deux cas. Pour un plat déjà préparé (tombé, brûlé, retourné), les
  ingrédients de sa recette sont décomptés automatiquement du stock —
  exactement comme une vente. Pour une matière première perdue
  directement (périmée, cassée, renversée), l'ingrédient est décompté
  directement. Le coût perdu est affiché dans les deux cas et alimente
  le rapport Pertes (voir Phase 15). Accessible aux rôles `cuisine` et
  `bar` en plus de `manager`/`stock` (`stock.adjust`), puisque ce sont
  eux qui constatent la perte en premier.
- **Transfert d'un article entre tables** (`orders.transfer_item`,
  réservé à `manager`) : contrairement au transfert de commande entière
  déjà existant, un seul article peut être déplacé vers la commande
  ouverte d'une autre table — utile quand un groupe se scinde après
  avoir commandé. La table de destination doit déjà avoir une commande
  ouverte ; les totaux des deux commandes sont recalculés.
- **Alertes sonores persistantes** : la cuisine et le bar entendent un
  bip en boucle (Web Audio, aucun fichier audio à héberger) dès qu'une
  nouvelle commande leur est envoyée, avec une bannière qui reste tant
  qu'elle n'est pas cliquée ("OK, j'ai vu"). Le serveur qui a pris la
  commande reçoit la même alerte, où qu'il navigue dans l'application,
  dès qu'un de ses articles passe à "prêt". Aucune commande déjà en
  place au moment où l'écran s'ouvre ne déclenche d'alerte rétroactive.
- **Écrans cuisine/bar groupés par table** : les articles ne sont plus
  listés en vrac mais regroupés par commande, sous un bandeau affichant
  la table — chaque table garde une couleur cohérente entre les deux
  écrans (et entre plusieurs commandes successives à la même table),
  pour ne plus mélanger les tickets pendant le coup de feu.
- 12 tests supplémentaires (137 au total, tous verts contre MySQL réel),
  plus une vérification de bout en bout en navigateur réel (Playwright) :
  écran cuisine ouvert dans un onglet, commande envoyée depuis un
  processus séparé, bannière sonore confirmée à l'apparition du nouvel
  article groupé sous sa propre table.

### Phase 15 — Rapport des pertes ✅ Implémentée et testée

Complément direct de la Phase 14 : la déclaration de perte ne servait à
rien sans un endroit où en voir le coût cumulé.

- `StockService::recordWaste()` et `recordDishWaste()` enregistrent
  désormais le coût unitaire de l'ingrédient au moment du mouvement
  (`stock_movements.unit_cost`), pour que le rapport reste exact même si
  le coût de l'ingrédient change ensuite (nouvel achat à un prix
  différent).
- Nouvelle section **Pertes (casse)** sur l'écran Rapports (période
  filtrable, comme le reste des rapports) : coût total des pertes,
  décomposé entre plats préparés et matières premières, avec le détail
  par ingrédient. Visible par `admin`, `direction` et `manager`
  (permission `reports.view` déjà en place, aucune nouvelle permission).
- 3 tests supplémentaires (140 au total, tous verts contre MySQL réel),
  plus une vérification en navigateur réel : bascule entre les deux
  modes du formulaire, perte déclarée, coût retrouvé dans le rapport.

### Phase 16 — Connexion par code PIN ✅ Implémentée et testée

Deuxième méthode de connexion, pensée pour le personnel qui partage une
tablette en salle/cuisine et n'a pas à taper un mot de passe à chaque
prise de service.

- Code PIN à 8 chiffres, optionnel, configuré par un administrateur
  depuis la fiche utilisateur (écran Utilisateurs) — un champ vide
  laisse le PIN existant inchangé, exactement comme le mot de passe.
  Stocké haché (`Hash`), jamais en clair.
- Écran `/pin-login` avec pavé numérique tactile (0-9, effacer,
  correction) sous un indicateur à 8 points ; la connexion se déclenche
  automatiquement dès le 8ᵉ chiffre saisi — aucun clavier requis sur une
  tablette. Un lien réciproque relie les deux écrans de connexion.
- Un PIN étant haché, il ne peut pas être recherché par une requête
  directe : `User::findByPin()` compare le code saisi au PIN de chaque
  utilisateur actif qui en a un configuré — tout à fait viable pour
  l'effectif d'un seul restaurant, et réutilisé pour empêcher qu'un
  administrateur assigne le même PIN à deux comptes.
- Comptes désactivés et débit de tentatives (5 par IP, comme la
  connexion classique) s'appliquent de la même façon.
- Le code PIN est l'écran d'accueil par défaut : un visiteur non connecté
  (y compris `/` et toute page protégée) atterrit sur `/pin-login`, pas
  sur l'écran e-mail/mot de passe — celui-ci reste accessible via le lien
  réciproque, pour la configuration initiale ou un accès administrateur
  depuis un poste personnel.
- 12 tests supplémentaires (152 au total, tous verts contre MySQL réel),
  plus une vérification de bout en bout en navigateur réel (Playwright) :
  saisie au pavé numérique jusqu'au 8ᵉ chiffre, connexion automatique,
  arrivée sur le tableau de bord.

### Phase 17 — Menu latéral adaptatif (mobile) ✅ Implémentée et testée

Le menu latéral fixe (250px) prenait la moitié de l'écran sur un
téléphone, écrasant le contenu de toutes les pages protégées (textes
coupés, boutons tronqués).

- Le menu latéral devient un panneau coulissant (`offcanvas` Bootstrap)
  en dessous du seuil `lg` : masqué par défaut sur mobile/tablette
  portrait, ouvert via une icône ☰ dans l'en-tête, refermé via une croix
  dans le panneau. Au-delà du seuil `lg` (ordinateur, tablette paysage),
  le comportement est inchangé : menu fixe, toujours visible.
- Correction associée : le fond sombre du menu était neutralisé par une
  règle Bootstrap plus spécifique une fois le panneau rendu statique en
  desktop — fixé par une priorité explicite sur les couleurs du menu.
- Vérifié sur tableau de bord, cuisine, commandes et plan de salle, à
  largeur téléphone (390px) et ordinateur (1440px) : aucune régression
  desktop, plus de texte tronqué sur mobile.
- Changement d'interface pur (Blade + SCSS) : les 152 tests existants
  restent verts, aucun test supplémentaire nécessaire.

### Phase 19 — Caisse façon terminal pro, dropdowns cherchables, alerte agrandie ✅ Implémentée et testée

Trois améliorations d'interface validées sur maquette avant développement.

- L'écran Commandes/POS remplace le menu déroulant de catégories et la
  liste de boutons simples par un catalogue façon terminal de caisse
  professionnel : catégories en onglets colorés sur le côté, grille de
  produits en boutons colorés (couleur fixe par catégorie, comme les
  tables sur les écrans cuisine/bar).
- Tous les menus déroulants de l'application (catégories, ingrédients,
  tables, fournisseurs, transfert d'article, moyen de paiement...) sont
  désormais cherchables : taper filtre la liste au lieu de faire défiler
  des dizaines de lignes. Un menu Livewire (transfert d'article, moyen
  de paiement) reste synchronisé avec le composant via `wire:ignore` sur
  le champ, qui laisse la recherche fonctionner sans que Livewire
  n'écrase le widget à chaque rafraîchissement.
- La notification de commande envoyée/prête passe d'un petit encart en
  coin d'écran à une grande carte centrée, contour pulsé, sur fond
  assombri — impossible à manquer sur un écran de cuisine chargé. Le
  fonctionnement (son en boucle, fermeture manuelle) est inchangé.
- Changement d'interface (Blade, SCSS, JS) : les 159 tests existants
  restent verts ; vérifié en navigateur réel (Playwright) sur mobile et
  desktop, transfert d'article via le nouveau menu cherchable inclus.

### Phase 20 — Mode sombre, couverts, TVA affichée, envoi direct au plan de salle ✅ Implémentée et testée

Quatre ajustements demandés après usage réel de l'écran de caisse.

- Un bouton 🌙/☀️ dans l'en-tête bascule l'application entre mode clair
  et mode sombre (variables de couleur Bootstrap 5.3), mémorisé par
  navigateur (`localStorage`) et appliqué avant même le premier rendu
  pour éviter tout flash du mauvais thème.
- Le nombre de couverts se saisit à la création d'une commande (repris
  automatiquement du nombre de convives lors de la transformation d'une
  réservation en commande) et apparaît en KPI "Couverts aujourd'hui" sur
  le tableau de bord.
- Tous les prix visibles pendant la prise de commande (catalogue,
  ticket, addition imprimée) affichent désormais le prix TTC — celui de
  la carte papier — au lieu du prix hors taxe stocké en base ; le calcul
  du sous-total/TVA/total au bas du ticket, lui, reste inchangé (déjà
  correct).
- Cliquer sur "Envoyer en cuisine/bar" redirige automatiquement vers le
  plan de salle au lieu de rester sur l'écran de commande.
- 5 tests supplémentaires (164 au total, tous verts contre MySQL réel),
  plus une vérification de bout en bout en navigateur réel (Playwright) :
  bascule de thème persistée après rechargement, prix HT stocké (36.36 DH
  + 10% TVA) affiché correctement à 40.00 DH partout, redirection
  confirmée après envoi en production.

### Phase 21 — Redirection après encaissement, statut cuisine/bar sur le plan de salle, temps moyen de service ✅ Implémentée et testée

- "Encaisser" redirige désormais vers le plan de salle, comme "Envoyer
  en cuisine/bar".
- Chaque table occupée du plan de salle affiche le statut de sa
  commande (Ouverte / Envoyée / En préparation / Prête / Servie), pas
  seulement le statut physique de la table.
- Les commandes enregistrent maintenant `sent_at` (premier envoi en
  cuisine/bar) et `served_at` (passage à "servie"). Le tableau de bord
  affiche un nouveau KPI "Temps moyen de service" (moyenne de
  `served_at - sent_at` sur les commandes du jour).
- 5 tests supplémentaires (169 au total, tous verts contre MySQL réel),
  plus une vérification en navigateur réel (Playwright) : badge de
  statut visible sur le plan de salle après envoi en production.

### Phase 22 — Installation en application (PWA) et écran par défaut selon le rôle ✅ Implémentée et testée

Pour les futures tablettes bar/cuisine/serveurs (et la caisse tactile
existante) : Dune ERP peut s'installer comme une vraie application,
sans jamais afficher le navigateur.

- Manifeste web + icône + service worker minimal : sur Chrome/Edge,
  "Installer la page en tant qu'application" ajoute une icône Dune ERP
  au bureau/écran d'accueil, qui s'ouvre ensuite en fenêtre autonome
  (sans barre d'adresse). Aucune installation par store, aucun coût.
- Un compte n'ayant qu'un rôle opérationnel (cuisine, bar, caissier,
  serveur) est envoyé directement sur son écran dédié après connexion
  (PIN ou email/mot de passe) au lieu du tableau de bord général — un
  compte admin/manager/direction/comptable/stock continue de voir le
  tableau de bord complet, même s'il détient aussi un rôle opérationnel.
  Un lien profond (deep link) reste prioritaire sur ce choix par défaut.
- 7 tests supplémentaires (176 au total, tous verts contre MySQL réel),
  plus vérification en navigateur réel (Playwright) : manifeste lié,
  service worker enregistré sans erreur, icônes servies correctement.

### Phase 23 — Corrections suite au test complet du parcours personnel ✅ Implémentée et testée

Un passage de test complet sur tout le parcours opérationnel (PIN,
plan de salle, POS, cuisine, bar, encaissement, dashboard) a remonté
plusieurs problèmes, tous corrigés :

- **Transfert d'article qui ne répondait qu'au 2ᵉ clic sur "OK"** :
  le bouton entrait en conflit avec la fermeture du menu déroulant.
  Le transfert se déclenche maintenant dès qu'on choisit la table dans
  le menu — plus de bouton, plus de double-clic nécessaire.
- **Toute l'application était en anglais côté messages système** (
  `APP_LOCALE=en` par défaut) : les statuts de table/commande/article,
  le mode de paiement, les dates relatives ("il y a 5 jours") et les
  messages d'erreur de connexion s'affichaient en anglais malgré une
  interface écrite en français. Bascule sur le français par défaut,
  avec des traductions ajoutées pour les validations et l'authentification.
- **Le menu "Transférer vers…" débordait de sa carte** sur le plan de
  salle (largeur tablette) : passé en disposition verticale, ne déborde
  plus.
- **Le catalogue POS causait un défilement horizontal de toute la page
  sur téléphone** : corrigé (largeur minimale du bandeau de catégories),
  ainsi qu'un débordement similaire, plus discret, sur la ligne d'en-tête
  (nom d'utilisateur + bouton mode sombre + déconnexion) qui ne repassait
  pas à la ligne sur petit écran.
- **Bouton "Déconnexion" peu lisible en mode sombre** : contraste corrigé
  pour tous les boutons de ce style en mode sombre.
- 7 tests supplémentaires (182 au total, tous verts contre MySQL réel),
  plus vérification en navigateur réel (Playwright) : transfert en un
  seul geste confirmé, absence de défilement horizontal confirmée sur
  mobile, lisibilité du mode sombre confirmée.

## Licence

Projet propriétaire — Dune Rooftop Marrakech.
