# suitecrm-core

Socle commun des projets SuiteCRM : une image Docker de **SuiteCRM 8** en français
(Apache, PHP 8.4, pack de langue fr_FR, formats français), avec MariaDB et le cron,
servie derrière le [reverse-proxy](https://github.com/david-grosskost/reverse-proxy).

Les projets clients partent de l'image publiée par ce dépôt :

```dockerfile
FROM ghcr.io/david-grosskost/suitecrm-core:0.1.0
```

## Prérequis

- Docker (Docker Desktop sous Windows et macOS)
- Git et make (Git Bash sous Windows)
- le [reverse-proxy](https://github.com/david-grosskost/reverse-proxy) installé et démarré

PHP et Composer ne sont pas nécessaires : ils tournent dans Docker.

## Installation

```bash
git clone https://github.com/david-grosskost/suitecrm-core.git
cd suitecrm-core
make app@install
```

Puis ouvrir https://suitecrm-core.dev.localhost. L'administrateur est `admin`, son
mot de passe est généré dans `.env.local`. Sur un serveur (demo, prod), ce mot de passe
ne sert qu'à la première connexion : SuiteCRM en demande aussitôt un nouveau.

## E-mails

En local et sur les démos, [Mailpit](https://mailpit.axllent.org) reçoit tous les
e-mails envoyés par SuiteCRM : aucun ne part vers une vraie boîte. À l'installation,
puis à chaque démarrage et avant le cron (`scripts/mail.php`) :

- tous les comptes sortants (système et utilisateurs) sont redirigés vers Mailpit ;
- toutes les boîtes de réception (IMAP) sont désactivées.

Une base copiée de la prod est donc neutralisée au démarrage. Pour l'importer,
arrêter d'abord l'application (`make app@stop`), puis redémarrer : le cron ne
tourne jamais sur une base non neutralisée.

| Environnement | Boîte de réception | Accès |
| --- | --- | --- |
| local | https://mail-suitecrm-core.dev.localhost | libre |
| demo | `https://mail-<APP_HOST>` | `demo` / `MAILPIT_PASSWORD` de `.env.local` |
| prod | pas de Mailpit | serveur SMTP réel, réglé par l'administrateur |

Chaque instance a son Mailpit et son mot de passe : une démo n'ouvre jamais la
boîte d'une autre.

## Extensions

Les extensions (modèle :
[suitecrm-extension-template](https://github.com/david-grosskost/suitecrm-extension-template))
sont des paquets Composer de type `suitecrm-extension`.

- **Catalogue** : celles que `composer.json` requiert sont mises dans l'image au
  build (`scripts/extensions-catalog.php`). Une extension non compatible avec la
  version de SuiteCRM de l'image (clé `suitecrm` de son `extension.json`) fait
  échouer le build.
- **Activation** : `EXTENSIONS` (`.env`, ou `.env.local` pour une instance)
  liste celles que l'instance fait tourner, séparées par des virgules. À chaque
  démarrage, `suitecrm-extensions` les installe dans l'application, retire
  celles qui ne sont plus listées, et lance une réparation rapide quand la
  liste ou leur contenu change.
- Une extension n'écrase jamais un fichier de `public/legacy/custom/` qui ne
  vient pas d'elle : le démarrage s'arrête avec le nom du fichier.
- **Chargeur de module ou `EXTENSIONS`, jamais les deux** : chaque extension
  publie aussi un paquet pour le Chargeur de module de SuiteCRM (joint à ses
  releases GitHub). Une extension installée par ce Chargeur a ses fichiers dans
  `custom/` : listée en plus dans `EXTENSIONS`, elle bloque le démarrage (règle
  ci-dessus). La désinstaller d'abord (Administration → Chargeur de module),
  puis l'ajouter à `EXTENSIONS`.
- **Front (Angular)** : le module Angular déjà compilé d'une extension
  (`public/`, déclaré par son `config/extension.php`) va dans
  `extensions/<Nom>/public/` ; SuiteCRM le recopie dans `public/extensions/<Nom>/`
  en reconstruisant son cache, et le charge au démarrage de l'interface. Le front
  de SuiteCRM n'est jamais recompilé.
- **Scripts d'installation** : `database/install.php` d'une extension est lancé
  une fois, après la réparation rapide de sa première activation ;
  `database/uninstall.php`, quand elle n'est plus listée, avant que ses fichiers
  soient retirés (réactivée, elle s'installe de nouveau). Ils tournent dans SuiteCRM, en
  administrateur (`scripts/extension-script.php`), par exemple pour écrire un
  panneau dans une mise en page de Studio, puis le retirer.
- **Données de démo** : `make db@seed` charge aussi les seeders des extensions
  actives (`database/seeders/<slug>/`, voir [database/README.md](database/README.md)).

Ajouter une extension au catalogue :

```bash
make composer@update PACKAGES=david-grosskost/suitecrm-ext-<slug>
```

après avoir déclaré son dépôt dans `repositories` et l'avoir mise dans
`require` de `composer.json`.

## Commandes

`make` seul liste toutes les commandes. Elles viennent de
[suitecrm-devkit](https://github.com/david-grosskost/suitecrm-devkit), installé par
Composer au premier lancement.

| Commande | Rôle |
| --- | --- |
| `make app@install` | Installation complète, relançable sans risque |
| `make app@start` / `app@stop` / `app@restart` | Démarrer, arrêter, redémarrer |
| `make app@status` / `app@logs` | État des conteneurs, logs (`SERVICE=cron`, `SERVICE=db`) |
| `make quality@lint` | Dockerfile, style PHP et scripts shell |
| `make quality@stan` | Analyse statique PHP |
| `make test@smoke` | SuiteCRM répond et l'administrateur peut se connecter |
| `make composer@update` | Mettre à jour les dépendances (`PACKAGES=…`) |

## Développer

- `main` est protégée : tout passe par une branche et une pull request.
- Branches : `feature/<id-ticket>/<description>` ou `fix/<id-ticket>/<description>`.
- Commits et titres de PR en [Conventional Commits](https://www.conventionalcommits.org/)
  (`feat: …`, `fix: …`), vérifiés par les hooks Git et la CI.
- Merge en **squash** : le titre de la PR devient le message du commit sur `main`.
- La CI (`make quality@lint`, `make quality@stan`) doit être verte pour merger.

## Releases

[release-please](https://github.com/googleapis/release-please) tient à jour une PR
de release (version et `CHANGELOG.md`) à partir des commits. La merger crée le tag,
la release GitHub et publie l'image `ghcr.io/david-grosskost/suitecrm-core:<version>`.
