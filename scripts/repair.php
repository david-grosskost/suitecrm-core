<?php

// Quick Repair and Rebuild, as Administration > Repair does it: rebuilds the
// extensions (custom/Extension/… merged into custom/modules/…/Ext/), clears
// the caches, and applies the database changes the extensions ask for (new
// fields). Run by suitecrm-extensions when the active extensions change, and
// by suitecrm-install after the installation.
//
// @usage php /usr/local/share/suitecrm/repair.php

if (!defined('sugarEntry')) {
    define('sugarEntry', true);
}
chdir('/var/www/html/public/legacy');
require_once 'include/entryPoint.php';
require_once 'modules/Administration/QuickRepairAndRebuild.php';

// Repairing needs an administrator (permissions, cached menus).
global $current_user, $mod_strings;
$current_user = BeanFactory::getBean('Users', '1');
if (!$current_user instanceof User || empty($current_user->id)) {
    fwrite(STDERR, "repair : administrateur introuvable\n");
    exit(1);
}

// The repair recognises "all modules" by its label, in the current language.
$mod_strings = return_module_language('en_us', 'Administration');
$allModules = is_string($mod_strings['LBL_ALL_MODULES'] ?? null) ? $mod_strings['LBL_ALL_MODULES'] : 'All Modules';

$repair = new RepairAndClear();
// clearAll: every action of the repair screen; true: run the database
// changes (otherwise only shown); false: no HTML output.
$repair->repairAndClearAll(['clearAll'], [$allModules], true, false);

echo "  Réparation rapide faite (extensions reconstruites, caches vidés).\n";
