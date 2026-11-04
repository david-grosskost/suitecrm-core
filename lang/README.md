# Compléments de traduction

Le pack de langue français de SuiteCRM laisse des libellés en anglais. Ce dossier
les complète, sans toucher au pack.

## Fonctionnement

Un fichier par fichier de langue, au **même chemin** que dans `public/legacy/` :

```
lang/modules/Accounts/language/fr_FR.lang.php
lang/include/language/fr_FR.lang.php
```

Chaque fichier ne contient que des affectations, une par libellé :

```php
<?php

$mod_strings['LBL_TYPE'] = 'Type :';
$app_list_strings['account_type_dom']['Analyst'] = 'Analyste';
```

À la construction de l'image, chaque fichier est ajouté à la fin du fichier du pack
au même chemin (sa ligne `<?php` en moins) : ses libellés remplacent ceux du pack.
Si le pack n'a pas ce fichier, il est copié tel quel. Au déploiement, l'entrypoint
vide les caches de langue de SuiteCRM.

## Trouver ce qui manque

```bash
make app@update        # reconstruit l'image avec les compléments actuels
make i18n@missing      # liste ce qui reste en anglais, dans .tools/i18n/fr_FR/
```

Chaque fichier produit est prêt à traduire : remplacer les textes anglais, puis le
copier (ou fusionner) ici, au même chemin.

## Règles de traduction

- Vouvoiement, typographie française : espace avant `:`, `?`, `!`, `;`.
- Garder tels quels les `%s`, `{0}`, balises HTML et noms de variables.
- Les apostrophes s'échappent dans les chaînes PHP : `'l\'opportunité'`.
