<?php
// Shared base configuration of every SuiteCRM instance: French by default.
// Appended once to public/legacy/config_override.php by suitecrm-install;
// afterwards the administrator owns these settings. Keep the first line alone:
// only the lines after it are appended.

// Language
$sugar_config['languages'] = [
    'en_us' => 'English (US)',
    'fr_FR' => 'Français (France)',
];
$sugar_config['default_language'] = 'fr_FR';

// Dates and times: 31/12/2026 23:59
$sugar_config['datef'] = 'd/m/Y';
$sugar_config['timef'] = 'H:i';

// Currency and numbers: 1 234,56 €
$sugar_config['default_currency_iso4217'] = 'EUR';
$sugar_config['default_currency_name'] = 'Euro';
$sugar_config['default_currency_symbol'] = '€';
$sugar_config['default_currency_significant_digits'] = '2';
$sugar_config['default_decimal_seperator'] = ',';
$sugar_config['default_number_grouping_seperator'] = ' ';

// CSV exports open directly in a French Excel
$sugar_config['export_delimiter'] = ';';
