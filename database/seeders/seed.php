<?php

// Loads the French demo data into the instance: every seeder of every
// package (<package>/seeders.php), those of the core and those of the active
// extensions (database/seeders/ of each), dependencies first. Safe to run
// again: records are updated, never duplicated (see database/README.md).
// Runs inside the app container, as www-data, once SuiteCRM is installed.
//
// @usage php /usr/local/share/suitecrm/database/seeders/seed.php [<seeder> ...]

declare(strict_types=1);

use SuiteCrmCore\Seed\Runner;
use SuiteCrmCore\Seed\SeedContext;

if (!defined('sugarEntry')) {
    define('sugarEntry', true);
}

$seedersDir = __DIR__;
chdir('/var/www/html/public/legacy');
require_once 'include/entryPoint.php';

require_once $seedersDir . '/src/Seeder.php';
require_once $seedersDir . '/src/SeedContext.php';
require_once $seedersDir . '/src/Fr.php';
require_once $seedersDir . '/src/Runner.php';

// Faker and the factories (database/factories), for the generated volume.
// Faker is installed in the image next to them (database/vendor, see the Dockerfile).
require_once $seedersDir . '/../vendor/autoload.php';
foreach (['Factory', 'AccountFactory', 'ContactFactory', 'OpportunityFactory'] as $factory) {
    require_once $seedersDir . '/../factories/' . $factory . '.php';
}

// The records are created by the administrator, with his French formats.
global $current_user;
$current_user = BeanFactory::getBean('Users', '1');
if (!$current_user instanceof User || empty($current_user->id)) {
    fwrite(STDERR, "seed : administrateur introuvable, SuiteCRM est-il installé ?\n");
    exit(1);
}

$only = array_slice($argv, 1);

try {
    // The extensions this instance has installed (Chargeur de module), with
    // the demo data the image carries for them (scripts/extensions-catalog.php).
    $directories = [$seedersDir];
    $db = DBManagerFactory::getInstance();
    $result = $db->query('SELECT id_name FROM upgrade_history');
    while (is_array($row = $db->fetchByAssoc($result, false))) {
        $slug = is_string($row['id_name'] ?? null) ? $row['id_name'] : '';
        if ($slug !== '' && is_dir("/usr/local/share/suitecrm/extensions/seeders/{$slug}")) {
            $directories[] = "/usr/local/share/suitecrm/extensions/seeders/{$slug}";
        }
    }
    $seeders = Runner::order(Runner::discover($directories), $only);
    $seed = new SeedContext();
    foreach ($seeders as $seeder) {
        $before = $seed->saved();
        $seeder->run($seed);
        printf("  %-34s %d enregistrement(s)\n", $seeder->name(), $seed->saved() - $before);
    }
    printf("  Données de démo : %d enregistrement(s) écrits.\n", $seed->saved());
} catch (RuntimeException $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}
