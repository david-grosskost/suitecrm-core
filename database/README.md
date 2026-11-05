# Base de données

Ce qui touche à la base de l'instance, rangé comme dans Laravel ; les commandes sont dans
le domaine `db@` (`make` les liste).

| Dossier | Contenu |
|---|---|
| `seeders/` | les données de démo en français, chargées par `make db@seed` |
| `factories/` | les fabriques de données générées (Faker `fr_FR`), pour le volume |

## Seeders : les données de démo

Les seeders remplissent une instance avec des données de démo crédibles : des entreprises
françaises fictives, leurs contacts et un pipeline commercial à toutes les étapes.

```bash
make db@seed                              # toutes les données
make db@seed SEEDERS="core/contacts"      # un seeder (et ceux dont il dépend)
```

Jamais en prod : la commande refuse de tourner avec `APP_ENV=prod`.

### Sur une démo

Une démo déployée démarre vide : ses données se chargent **à la demande**, jamais
automatiquement. Sur GitHub, **Actions → Seed → Run workflow**, puis choisir :

| Champ | Exemple | Effet |
|---|---|---|
| Seeders | vide | tout `core` (20 comptes, 40 contacts, 22 opportunités) |
| Seeders | `core/contacts` | les contacts, et les comptes dont ils dépendent |
| Count | `200` | 200 comptes générés en plus (`core/volume`), de 0 à 1 500 |

Le serveur est sauvegardé juste avant (étiquette `pre-seed`) : le résumé du workflow donne
la commande pour revenir en arrière. Refusé pour la prod, sur GitHub comme sur le serveur.
Plus tard, l'interface commerciale (demo-manager) lancera ce même workflow.

### Ce que contient `core`

| Seeder | Données |
|---|---|
| `core/accounts` | 20 comptes : clients, prospects, partenaires, dans de vraies villes |
| `core/contacts` | 40 contacts, 2 par compte (le décideur et un contact opérationnel) |
| `core/opportunities` | 22 opportunités, de la prospection au gain ou à la perte |
| `core/volume` | du volume généré, en plus : `COUNT` comptes (aucun par défaut), avec 1 à 3 contacts et 0 à 2 opportunités chacun |

```bash
make db@seed SEEDERS="core/volume" COUNT=200   # 200 comptes générés, en plus des 20
```

Le volume est généré par les factories de `database/factories/`, avec Faker en français
(`fr_FR`) : noms de sociétés et de personnes, villes réelles avec leur code postal. Faker est
initialisé avec une graine fixe : le même `COUNT` donne toujours les mêmes données, et
relancer les met à jour sans doublon. Un `COUNT` plus grand ajoute les comptes manquants ;
au plus 1 500.

Tout est fictif : numéros de téléphone réservés à la fiction par l'ARCEP (`01 99 00 …`,
`06 39 98 …`), adresses e-mail et sites en `.example` (domaine réservé, aucun e-mail ne peut
y partir). Les dates de clôture sont relatives au jour du seed : le pipeline paraît toujours
à jour.

### Le contrat d'un seeder

Un seeder implémente `SuiteCrmCore\Seed\Seeder` :

- `name()` : un nom unique, `<paquet>/<données>` (ex. `core/accounts`) ;
- `dependsOn()` : les seeders dont il utilise les enregistrements, lancés avant lui ;
- `run(SeedContext $seed)` : écrit ses enregistrements avec `$seed->upsert()`.

Deux règles :

1. **Rejouable.** `upsert(module, clé, champs)` crée l'enregistrement de cette clé, ou le met
   à jour s'il existe déjà : relancer un seed ne crée jamais de doublon.
2. **Ids stables.** L'id d'un enregistrement se calcule depuis son module et sa clé
   (`SeedContext::id('Accounts', 'durand-btp')`), toujours le même. Un seeder relie ainsi ses
   enregistrements à ceux d'un autre sans les chercher (ex. `account_id` d'un contact).

Les champs s'écrivent au format de la base : dates `AAAA-MM-JJ` (`Fr::date(30)` = dans
30 jours), montants sans séparateur, clés des listes déroulantes (`Customer`, pas « Client »).

`SuiteCrmCore\Seed\Fr` regroupe les aides françaises : téléphones et e-mails fictifs,
`Fr::siret()` (un SIRET valide, clé de Luhn, mais fictif), dates relatives.

### Ajouter des données (une extension)

Chaque paquet a son dossier dans `database/seeders/`, avec un `seeders.php` qui renvoie la liste
de ses seeders : `database/seeders/core/seeders.php` en est l'exemple. Le lanceur (`seed.php`) les découvre
tous, puis les ordonne selon leurs dépendances. Une extension pourra ainsi ajouter, par
exemple, le SIRET et la forme juridique aux comptes de `core/accounts`.
