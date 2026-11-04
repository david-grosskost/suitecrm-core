<?php

// Compléments du pack de langue français : libellés laissés en anglais.
$mod_strings['LBL_LIST_FORM_TITLE'] = 'Liste des migrations';
$mod_strings['LBL_HOMEPAGE_TITLE'] = 'Mes migrations';
$mod_strings['LNK_NEW_RECORD'] = 'Créer une migration';
$mod_strings['LNK_LIST'] = 'Voir les migrations';
$mod_strings['LBL_SEARCH_FORM_TITLE'] = 'Rechercher des migrations';
$mod_strings['LBL_NEW_FORM_TITLE'] = 'Nouvelle migration';
$mod_strings['LBL_RUN_MIGRATION'] = 'Lancer la migration';
$mod_strings['LBL_RUN_MIGRATION_CONFIRMATION'] = 'Voulez-vous vraiment lancer cette migration ?';
$mod_strings['LBL_RERUN_CONFIRMATION'] = 'Voulez-vous vraiment relancer cette migration depuis le début ? Tous les éléments existants seront supprimés et la migration redémarrera.';
$mod_strings['LBL_RERUN_SUCCESS'] = 'La migration a été remise en file d\'attente et repartira du début.';
$mod_strings['LBL_DISMISS_CONFIRMATION'] = 'Voulez-vous vraiment ignorer cette migration ? Elle sera supprimée avec toutes ses données.';
$mod_strings['LBL_DISMISS_SUCCESS'] = 'Migration ignorée.';
$mod_strings['LBL_TYPE_BACKGROUND_HELP'] = 'Cette migration tourne en arrière-plan via la file de messages. Elle traite les éléments par lots et ne bloque pas l\'interface.';
$mod_strings['LBL_TYPE_IMMEDIATE_HELP'] = 'Cette migration s\'exécute dès son déclenchement. Elle traite tous les éléments en une seule requête, ce qui peut être long pour de gros volumes.';
$mod_strings['LBL_MESSENGER_SETUP'] = 'Configuration des tâches de fond';
$mod_strings['LBL_MESSENGER_SETUP_DESC1'] = 'Les migrations sont traitées en arrière-plan.';
$mod_strings['LBL_MESSENGER_SETUP_DESC2'] = 'Pour qu\'elles aboutissent, vérifiez qu\'un worker Symfony Messenger est en cours d\'exécution.';
$mod_strings['LBL_MESSENGER_SETUP_DESC3'] = 'Sans worker actif, les tâches restent au statut « En attente ».';
$mod_strings['LBL_MESSENGER_SETUP_DOC_LINK'] = 'Voir le guide de configuration (Supervisor, systemd, Cron)';
