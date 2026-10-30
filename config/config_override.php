<?php
// Shared base configuration of every SuiteCRM instance: French by default.
// Appended once to public/legacy/config_override.php by suitecrm-install;
// afterwards the administrator owns these settings. Keep the first line alone:
// only the lines after it are appended.

$sugar_config['languages'] = [
    'en_us' => 'English (US)',
    'fr_FR' => 'Français (France)',
];
$sugar_config['default_language'] = 'fr_FR';
