# Checklist de recette — Dune ERP V1

Liste de vérification fonctionnelle, phase par phase, pour valider que
chaque module livré fonctionne réellement (pas de bouton factice, pas de
donnée figée) avant mise en service. Toutes les cases ci-dessous ont été
vérifiées par la suite de tests automatisés (112 tests contre une vraie
base MySQL) et par un parcours manuel en navigateur à la fin de chaque
phase — voir la section "État d'avancement" du README pour le détail par
phase.

## Fondation & sécurité

- [x] Connexion/déconnexion réelles, mot de passe haché, limitation à 5
      tentatives par couple e-mail + IP.
- [x] Un compte désactivé en cours de session est déconnecté
      immédiatement à la requête suivante.
- [x] 9 rôles avec permissions granulaires ; un administrateur peut
      ajuster les permissions de chaque rôle (sauf `admin`) sans toucher
      au code.
- [x] Aucune suppression physique d'utilisateur — désactivation
      uniquement, historique préservé.
- [x] Toute action sensible (création/modification de compte, permission,
      commande, paiement, remise, stock, achat, dépense, événement) est
      journalisée dans un audit **immuable**.
- [x] En-têtes de sécurité (X-Frame-Options, X-Content-Type-Options,
      Referrer-Policy, HSTS conditionnel) présents sur toute réponse.

## Référentiels

- [x] Zones, tables, catégories, produits, clients : CRUD complet,
      permission par action.
- [x] Impossible de supprimer une zone avec des tables ou une catégorie
      avec des produits (intégrité référentielle appliquée côté métier,
      pas seulement en base).
- [x] Historique des changements de prix produit conservé.

## Point de vente (caisse)

- [x] Une commande peut être créée, ses articles ajoutés/modifiés/retirés
      tant qu'elle n'est pas envoyée en préparation ; un article envoyé ne
      peut être qu'annulé, pas supprimé.
- [x] Totaux (sous-total, remise, taxe, total, reste dû) recalculés
      **côté serveur** à chaque changement, jamais approximés côté client.
- [x] Remise soumise à permission dédiée (`orders.discount`), motif
      obligatoire, tracée.
- [x] Paiement multi-méthodes, paiement partiel, blocage du dépassement du
      montant dû ; remboursement tracé avec motif.
- [x] Une session de caisse doit être ouverte pour encaisser ; la
      fermeture calcule automatiquement l'écart entre montant attendu et
      montant compté.
- [x] Reçus et rapport de caisse imprimables.

## Opérations

- [x] Plan de salle en temps réel (couleur par statut), transfert de
      commande d'une table à une autre, remise en service après nettoyage.
- [x] Réservations avec prévention des doubles réservations sur un
      créneau, et génération de commande en un clic depuis une réservation
      installée.
- [x] Écrans cuisine et bar séparés, filtrés automatiquement par type de
      produit, avec suivi de statut par article horodaté.

## Stock

- [x] Chaque mouvement de stock (achat, vente, ajustement, casse,
      inventaire) est un enregistrement séparé et immuable — le stock ne
      change jamais silencieusement.
- [x] Vente d'un produit lié à une recette → consommation automatique des
      ingrédients au moment du paiement complet, jamais avant, jamais pour
      un article annulé.
- [x] Alertes de stock bas et coût matière par recette calculés
      automatiquement.

## Achats

- [x] Commande d'achat multi-lignes avec calcul automatique du total.
- [x] Réception d'une commande → entrée en stock tracée + mise à jour du
      coût unitaire de l'ingrédient au dernier prix payé.
- [x] Une commande réceptionnée ne peut plus être annulée.

## Finance

- [x] Dépenses manuelles catégorisées, tracées, jamais supprimées.
- [x] Rapports de chiffre d'affaires (réellement encaissé, pas
      théorique), produits les plus vendus, dépenses par catégorie,
      résultat net simplifié — filtrables par période.
- [x] Tableau de bord avec indicateurs réels (CA du jour, occupation des
      tables, alertes stock, statut de la caisse).

## Événements & personnel

- [x] Événement privé avec cycle de statut contrôlé (transitions
      invalides rejetées) et acomptes/paiements multiples plafonnés au
      montant du devis.
- [x] Fiches personnel avec activation/désactivation et lien optionnel
      vers un compte de connexion (un compte = un employé maximum).

## Rôles & permissions (V1.1)

- [x] Un administrateur peut créer un nouveau rôle métier directement
      depuis l'écran Rôles & permissions (nom + permissions initiales),
      sans toucher au code ni redéployer.
- [x] Un rôle ne peut être supprimé que s'il n'est assigné à aucun
      utilisateur ; le rôle `admin` ne peut jamais être supprimé — ces
      deux règles sont appliquées même pour un acteur `admin`, qui
      contourne pourtant les permissions ordinaires.

## Notifications (V1.2)

- [x] Confirmation de réservation par e-mail au client (si une adresse
      est renseignée), envoyée automatiquement au passage au statut
      `confirmed`.
- [x] Alerte de stock bas par e-mail à tous les utilisateurs disposant de
      `stock.adjust`, déclenchée une seule fois au moment où l'ingrédient
      franchit son seuil minimum (pas de spam à chaque mouvement suivant
      tant qu'il reste bas).
- [x] Un échec d'envoi (SMTP indisponible, etc.) est journalisé mais
      n'interrompt jamais l'action métier sous-jacente (confirmer une
      réservation ou enregistrer un mouvement de stock reste possible
      même si l'e-mail ne part pas).

## Hors périmètre V1 (assumé, à considérer pour une V2)

Ces points ont été identifiés pendant le développement mais
délibérément laissés hors du périmètre V1 pour rester fidèle au cahier
des charges initial sans sur-ingénierie :

- Notifications par SMS (les notifications par e-mail sont livrées en
  V1.2 ; le SMS nécessiterait un compte chez un fournisseur tiers comme
  Twilio, non inclus).
- Application mobile native (l'interface Bootstrap est responsive mais
  pensée pour un usage tablette/desktop en salle et en cuisine).
- Comptabilité d'engagement complète ou export vers un logiciel
  comptable tiers — le module Finance V1 est un tableau de bord
  opérationnel, pas un système comptable réglementaire.
- Gestion multi-établissement (l'application suppose un seul
  restaurant/rooftop).
