# Compléments de traduction du socle

Le pack de langue français de SuiteCRM laisse des libellés en anglais. L'essentiel de ces
compléments vit maintenant dans l'extension
[french-locale](https://github.com/david-grosskost/suitecrm-ext-french-locale), installée
d'office sur toute nouvelle instance.

Ne restent ici que les libellés que le mécanisme d'extension de SuiteCRM ne peut pas
atteindre :

| Dossier | Pourquoi il reste |
|---|---|
| `install/` | les écrans de l'installateur s'affichent avant qu'une extension existe |
| `include/SugarObjects/` | les gabarits dont héritent les modules ; SuiteCRM n'assemble pas de `custom/Extension/include/SugarObjects/` |

## Fonctionnement

Un fichier par fichier de langue, au **même chemin** que dans `public/legacy/` :

```
lang/install/language/fr_FR.lang.php
lang/include/SugarObjects/templates/person/language/fr_FR.lang.php
```

Chaque fichier ne contient que des affectations, une par libellé :

```php
<?php

$mod_strings['LBL_TYPE'] = 'Type :';
```

À la construction de l'image, chaque fichier est ajouté à la fin du fichier du pack au même
chemin (sa ligne `<?php` en moins) : ses libellés remplacent ceux du pack. Si le pack n'a pas
ce fichier, il est copié tel quel. Au déploiement, l'entrypoint vide les caches de langue de
SuiteCRM.

## Trouver ce qui manque

```bash
make app@update        # reconstruit l'image avec les compléments actuels
make i18n@missing      # liste ce qui reste en anglais, dans .tools/i18n/fr_FR/
```

Chaque fichier produit est prêt à traduire. Un libellé de module ou d'application se corrige
**dans l'extension french-locale**, pas ici : seuls un libellé de l'installateur ou d'un
gabarit `SugarObjects` se corrige dans ce dossier.

## Règles de traduction

- Vouvoiement, typographie française : espace avant `:`, `?`, `!`, `;`.
- Garder tels quels les `%s`, `{0}`, balises HTML et noms de variables.
- Les apostrophes s'échappent dans les chaînes PHP : `'l\'opportunité'`.
- Libellé repris dans du JavaScript (grilles du Chargeur de module…) : apostrophe
  typographique `’`, SuiteCRM l'y insère sans l'échapper.
