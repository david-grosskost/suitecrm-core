<?php
// Aligns the administrator created by the installer with the base configuration.
// The installer saves the admin's preferences before config_override.php is
// extended, so they still hold the US formats (12/23/2010, "." decimals, "," CSV).
// Runs once, at install, as www-data, after the base configuration is appended.
//
// @usage php /usr/local/share/suitecrm/post-install.php

if (!defined('sugarEntry')) {
    define('sugarEntry', true);
}
chdir('/var/www/html/public/legacy');
require_once 'include/entryPoint.php';

global $sugar_config;

$admin = BeanFactory::getBean('Users', '1');
if (empty($admin->id)) {
    fwrite(STDERR, "post-install : administrateur introuvable\n");
    exit(1);
}

$preferences = [
    'datef' => $sugar_config['datef'],
    'timef' => $sugar_config['timef'],
    'dec_sep' => $sugar_config['default_decimal_seperator'],
    'num_grp_sep' => $sugar_config['default_number_grouping_seperator'],
    'export_delimiter' => $sugar_config['export_delimiter'],
    'currency' => '-99', // -99 = the system currency (EUR)
];

foreach ($preferences as $name => $value) {
    $admin->setPreference($name, $value);
}
$admin->savePreferencesToDB();

echo "  Préférences de l'administrateur alignées sur la configuration de base.\n";
