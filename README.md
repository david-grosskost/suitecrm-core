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
mot de passe est généré dans `.env.local`.

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
