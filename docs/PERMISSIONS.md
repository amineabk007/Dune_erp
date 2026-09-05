# Guide des rôles et permissions

Référence pour un administrateur qui configure les comptes de Dune ERP.
Le catalogue complet des permissions vit dans
`database/seeders/PermissionSeeder::CATALOG` ; les droits par défaut de
chaque rôle dans `database/seeders/RoleSeeder::GRANTS`. Cette page en donne
une vue lisible, mais les deux fichiers restent la source de vérité — un
administrateur peut ajuster librement chaque rôle (sauf `admin`) depuis
l'écran **Rôles & permissions**.

## Principe

- **`admin`** a un accès total incontournable (`Gate::before` dans
  `AppServiceProvider`) — ce rôle ne doit être donné qu'aux personnes ayant
  la responsabilité technique complète du système.
- Tous les autres rôles suivent le **principe du moindre privilège** :
  chaque écran et chaque action sont protégés par une permission explicite
  au format `module.action` (ex. `payments.refund`, `stock.adjust`).
- Aucun compte n'est jamais supprimé, seulement désactivé
  (`is_active = false`) — l'historique (commandes, paiements, audit) reste
  intact et consultable.

## Rôles par défaut et leur usage prévu

| Rôle | Usage prévu |
|---|---|
| `admin` | Administrateur technique, accès total. |
| `direction` | Direction : consultation large (rapports, caisse, stock) + gestion commerciale (clients, événements, achats) sans opérations de caisse au quotidien. |
| `manager` | Responsable de salle/service : opérations complètes (commandes, caisse, stock, réservations, référentiels) au jour le jour. |
| `caissier` | Caisse : prise de commande, encaissement, ouverture/fermeture de session de caisse. |
| `serveur` | Service en salle : prise de commande, gestion des tables, réservations. |
| `cuisine` | Écran cuisine uniquement, consultation du stock. |
| `bar` | Écran bar uniquement, consultation du stock. |
| `stock` | Gestion des ingrédients, recettes, achats et fournisseurs. |
| `comptable` | Suivi financier : dépenses, achats, fournisseurs, consultation des rapports et paiements. |

## Matrice des permissions par module

Chaque cellule liste les actions accordées par défaut à ce rôle. `admin`
n'est pas listé : il a systématiquement tout.

| Module | direction | manager | caissier | serveur | cuisine | bar | stock | comptable |
|---|---|---|---|---|---|---|---|---|
| `users` | view | view | — | — | — | — | — | — |
| `roles` | — | — | — | — | — | — | — | — |
| `audit` | view | — | — | — | — | — | — | — |
| `orders` | view, discount | view, create, update, cancel, discount | view, create, update | view, create, update | view | view | — | — |
| `payments` | view, refund | view, create, refund | view, create | — | — | — | — | view |
| `cash` | view | view, open, close, movement | view, open, close, movement | — | — | — | — | — |
| `tables` | manage | manage | — | manage | — | — | — | — |
| `reservations` | view | view, create, update, cancel | view | view, create, update | — | — | — | — |
| `kitchen` | — | view | — | — | view | — | — | — |
| `bar` | — | view | — | — | — | view | — | — |
| `products` | view | view, create, update | view | view | — | — | view | — |
| `categories` | manage | manage | — | — | — | — | — | — |
| `recipes` | manage | manage | — | — | — | — | manage | — |
| `stock` | view | view, adjust, inventory | — | — | view | view | view, adjust, inventory | — |
| `purchases` | manage | manage | — | — | — | — | manage | manage |
| `suppliers` | manage | manage | — | — | — | — | manage | manage |
| `customers` | manage | manage | manage | — | — | — | — | — |
| `events` | manage | manage | — | — | — | — | — | — |
| `expenses` | manage | manage | — | — | — | — | — | manage |
| `employees` | manage | manage | — | — | — | — | — | — |
| `reports` | view | view | — | — | — | — | view | view |

Note : `roles.manage` (édition de la matrice ci-dessus) et
`users.manage` (création/modification de comptes) ne sont accordés à
**aucun rôle par défaut hors `admin`** — c'est un choix de sécurité
volontaire : la gestion des comptes et des permissions reste une
opération d'administrateur système, jamais une tâche métier courante. Un
administrateur peut évidemment déléguer ces droits à un rôle via l'écran
Rôles & permissions si l'exploitant le souhaite.

`orders.delete` existe dans le catalogue mais n'est utilisé par aucune
route : les commandes ne sont jamais supprimées, seulement annulées
(`orders.cancel`), afin de préserver l'historique commercial et
financier — cette permission est réservée pour un usage futur éventuel.

## Ajuster les permissions d'un rôle

1. Se connecter avec un compte `admin`.
2. Aller sur **Rôles & permissions**.
3. Cocher/décocher les permissions du rôle voulu, puis enregistrer — le
   changement est immédiat et journalisé dans l'audit. Le rôle `admin`
   n'est pas modifiable (il garde toujours l'accès global).

## Créer un nouveau rôle métier

Depuis l'écran **Rôles & permissions**, un administrateur peut créer un
rôle directement depuis l'interface (bouton **Nouveau rôle**), sans
toucher au code ni redéployer :

1. Choisir un nom en minuscules, sans espaces ni accents (ex.
   `voiturier`, `sommelier`) — c'est le nom technique utilisé partout
   dans le système.
2. Cocher les permissions initiales du nouveau rôle (modifiables plus
   tard comme n'importe quel autre rôle).
3. Enregistrer — le rôle est immédiatement assignable à un utilisateur
   depuis l'écran Utilisateurs, et sa création est journalisée dans
   l'audit.

Un rôle peut être supprimé (bouton **Supprimer**, avec confirmation)
seulement s'il n'est **assigné à aucun utilisateur** — sinon la
suppression est refusée pour ne pas retirer silencieusement l'accès de
quelqu'un ; il faut d'abord réassigner ces utilisateurs à un autre rôle.
Le rôle `admin` ne peut jamais être supprimé, même par un administrateur
(règle de sécurité appliquée au niveau du contrôleur, indépendamment du
bypass `Gate::before` qui donne autrement tout accès à `admin`).
