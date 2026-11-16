<?php

// Extensions of the instance, through the Module Loader of SuiteCRM
// (Administration → Chargeur de module), the only place they are installed
// from: an administrator sees there everything the instance has, with its
// version, and installs or uninstalls it himself.
//
// The image carries the Module Loader package of each extension of its
// catalogue (scripts/extensions-catalog.php). This script:
//
// - deposits those packages in the list of available packages
//   (public/legacy/upload/upgrades/module/), and takes out the older ones it
//   had deposited, so that the list never piles up versions. A package an
//   instance has installed is never taken out: SuiteCRM needs its zip to
//   uninstall it;
// - updates what is installed when the image brings a newer version: the same
//   steps as the Install button of the Module Loader, without uninstalling
//   first (so an extension keeps its data, and its install script does not run
//   again — see suitecrm-devkit, .make/scripts/package/build.php);
// - installs or uninstalls the extensions it is given: the extensions of a new
//   instance (suitecrm-install), and later the choice of the demo-manager.
//
// Nothing is ever installed or uninstalled on its own at a start.
//
// Run as www-data inside the app container.
//
// @usage php packages.php sync                      dépose et met à jour
//        php packages.php install <slug>...         installe (--required : celles d'office)
//        php packages.php uninstall <slug>...       désinstalle
//        php packages.php list                      état des extensions

declare(strict_types=1);

const CATALOG = '/usr/local/share/suitecrm/extensions';
const APP = '/var/www/html';
const LEGACY = APP . '/public/legacy';
// Where the Module Loader looks for the packages it can install.
const STAGE = LEGACY . '/upload/upgrades/module';

/**
 * Stops with a message in French.
 */
function fail(string $message): never
{
    fwrite(STDERR, "  packages : $message\n");
    exit(1);
}

/**
 * The catalogue of the image: one entry per extension, by slug.
 *
 * @return array<string, array{slug: string, name: string, package: string, version: string, file: string, manifest: string, required: bool}>
 */
function catalogue(): array
{
    $index = json_decode((string) @file_get_contents(CATALOG . '/index.json'), true);
    $entries = [];
    foreach (is_array($index) ? $index : [] as $entry) {
        if (is_array($entry) && is_string($entry['slug'] ?? null)) {
            /** @var array{slug: string, name: string, package: string, version: string, file: string, manifest: string, required: bool} $entry */
            $entries[$entry['slug']] = $entry;
        }
    }

    return $entries;
}

/**
 * Loads SuiteCRM, as an administrator, when the instance is installed.
 */
function suitecrm(): bool
{
    static $loaded = null;
    if (is_bool($loaded)) {
        return $loaded;
    }
    if (!is_file(LEGACY . '/config.php')) {
        $loaded = false;

        return false;
    }

    if (!defined('sugarEntry')) {
        define('sugarEntry', true);
    }
    chdir(LEGACY);
    require_once 'include/entryPoint.php';
    require_once 'include/utils/php_zip_utils.php';
    require_once 'ModuleInstall/ModuleInstaller.php';
    require_once 'modules/Administration/UpgradeHistory.php';

    // Installing needs an administrator: permissions, Studio, cached menus.
    global $current_user, $mod_strings;
    $admin = BeanFactory::getBean('Users', '1');
    if (!$admin instanceof User || empty($admin->id)) {
        fail('administrateur introuvable');
    }
    $current_user = $admin;
    $mod_strings = return_module_language('en_us', 'Administration');
    $loaded = true;

    return true;
}

/**
 * What the instance has installed, by slug (id_name of the package).
 *
 * @return array<string, array{id: string, version: string, filename: string}>
 */
function installed(): array
{
    $db = DBManagerFactory::getInstance();
    $result = $db->query('SELECT id, id_name, version, filename FROM upgrade_history ORDER BY date_entered');
    $rows = [];
    while (is_array($row = $db->fetchByAssoc($result, false))) {
        if (($row['id_name'] ?? '') !== '') {
            $rows[$row['id_name']] = [
                'id' => $row['id'] ?? '',
                'version' => $row['version'] ?? '',
                'filename' => $row['filename'] ?? '',
            ];
        }
    }

    return $rows;
}

/**
 * Unpacks a package into a temporary directory of SuiteCRM.
 */
function unpackPackage(string $zip): string
{
    // The directory SuiteCRM unpacks into, which a new instance does not have
    // yet (its Module Loader creates it at the first upload).
    $temp = LEGACY . '/upload/upgrades/temp';
    if (!is_dir($temp) && !mkdir($temp, 0o775, true)) {
        fail("dossier $temp impossible à créer");
    }

    $dir = mk_temp_dir($temp);
    if (!is_string($dir) || $dir === '' || !unzip($zip, $dir)) {
        fail("paquet $zip impossible à ouvrir");
    }

    return $dir;
}

/**
 * Runs what SuiteCRM writes while it installs, without its noise: it prints
 * in HTML, and warns about the tables of the modules it has no metadata for.
 *
 * @param callable(): void $work
 */
function quietly(callable $work): void
{
    ob_start();
    try {
        $work();
    } finally {
        $output = (string) preg_replace('#<br\s*/?>#i', "\n", (string) ob_get_clean());
    }
    $lines = [];
    foreach (explode("\n", $output) as $line) {
        $line = trim(strip_tags($line));
        if ($line !== '' && !str_starts_with($line, 'meta data absent for table')) {
            $lines[] = '  ' . $line;
        }
    }
    if ($lines !== []) {
        echo implode("\n", $lines) . "\n";
    }
}

/**
 * Installs or uninstalls one extension, in a process of its own: the scripts
 * of a package define functions (post_install…) that two packages cannot both
 * define in the same process.
 */
function child(string $command, string $slug): void
{
    $status = 0;
    passthru(sprintf('php %s %s %s', escapeshellarg(__FILE__), escapeshellarg($command), escapeshellarg($slug)), $status);
    if ($status !== 0) {
        exit($status);
    }
}

/**
 * Runs a script of a package (scripts/<name>.php), which defines the
 * function of the same name, as the Module Loader does it.
 */
function runScript(string $dir, string $name): void
{
    $file = "$dir/scripts/$name.php";
    if (!is_file($file)) {
        return;
    }
    require_once $file;
    if (is_callable($name)) {
        $name();
    }
}

/**
 * The variables a PHP file sets: how the Module Loader reads a manifest.
 *
 * @return array<string, mixed>
 */
function variablesOf(string $file): array
{
    require $file;
    unset($file);

    return get_defined_vars();
}

/**
 * Installs a package, or installs a newer one over it: the steps of the
 * Install button (modules/Administration/UpgradeWizard_commit.php).
 *
 * @param array{slug: string, name: string, version: string, file: string} $entry
 * @param array{id: string, version: string, filename: string}|null $previous What the instance has, when this is an update
 */
function install(array $entry, ?array $previous): void
{
    $zip = STAGE . '/' . $entry['file'];
    $dir = unpackPackage($zip);

    quietly(static function () use ($dir, $previous): void {
        runScript($dir, 'pre_install');
        $installer = new ModuleInstaller();
        $installer->silent = true;
        if ($previous === null) {
            $installer->install($dir);
        } else {
            $installer->install($dir, true, $previous['version']);
        }
        runScript($dir, 'post_install');
    });

    // One line in the history for one extension: the install script of the
    // package already takes out the others (SuiteCRM does not see that a 0.x
    // version updates another one).
    $db = DBManagerFactory::getInstance();
    $db->query('DELETE FROM upgrade_history WHERE id_name = ' . $db->quoted($entry['slug']));

    $variables = variablesOf(STAGE . '/' . substr($entry['file'], 0, -4) . '-manifest.php');
    $manifest = is_array($variables['manifest'] ?? null) ? $variables['manifest'] : [];
    $description = $manifest['description'] ?? null;

    $history = new UpgradeHistory();
    $history->filename = $zip;
    $history->md5sum = (string) md5_file($zip);
    $history->type = 'module';
    $history->version = $entry['version'];
    $history->status = 'installed';
    $history->name = $entry['name'];
    $history->description = is_string($description) ? $description : '';
    $history->id_name = $entry['slug'];
    $history->manifest = base64_encode(serialize([
        'manifest' => $manifest,
        'installdefs' => is_array($variables['installdefs'] ?? null) ? $variables['installdefs'] : [],
        'upgrade_manifest' => is_array($variables['upgrade_manifest'] ?? null) ? $variables['upgrade_manifest'] : [],
    ]));
    $history->save();

    printf("  %s %s : %s\n", $entry['slug'], $entry['version'], $previous === null ? 'installée' : "mise à jour depuis {$previous['version']}");
}

/**
 * Uninstalls a package, with its uninstall script: the steps of the
 * Uninstall button.
 *
 * @param array{id: string, version: string, filename: string} $current
 */
function uninstall(string $slug, array $current): void
{
    if (!is_file($current['filename'])) {
        fail("$slug : paquet {$current['filename']} introuvable, désinstallation impossible");
    }
    $dir = unpackPackage($current['filename']);

    quietly(static function () use ($dir): void {
        runScript($dir, 'pre_uninstall');
        // Fields and tables the extension added go with it.
        $GLOBALS['mi_remove_tables'] = true;
        $installer = new ModuleInstaller();
        $installer->silent = true;
        $installer->uninstall($dir);
        runScript($dir, 'post_uninstall');
    });

    $db = DBManagerFactory::getInstance();
    $db->query('DELETE FROM upgrade_history WHERE id_name = ' . $db->quoted($slug));

    printf("  %s %s : désinstallée\n", $slug, $current['version']);
}

/**
 * Puts the packages of the image in the list of available packages, and takes
 * out the older ones deposited before. Keeps every package an instance has
 * installed: SuiteCRM opens its zip again to uninstall it.
 *
 * @param array<string, array{slug: string, file: string, manifest: string}> $catalogue
 * @param array<string, array{id: string, version: string, filename: string}> $installed
 */
function deposit(array $catalogue, array $installed): void
{
    if (!is_dir(STAGE) && !mkdir(STAGE, 0o775, true)) {
        fail('dossier des paquets ' . STAGE . ' impossible à créer');
    }

    $ours = [];
    foreach ($catalogue as $entry) {
        foreach ([$entry['file'], $entry['manifest']] as $file) {
            $ours[$file] = true;
            $source = CATALOG . "/$file";
            $target = STAGE . "/$file";
            if (!is_file($target) || md5_file($target) !== md5_file($source)) {
                copy($source, $target);
            }
        }
    }

    $kept = [];
    foreach ($installed as $current) {
        $kept[basename($current['filename'])] = true;
    }

    // Only the packages this script deposited: <slug>-<version>.zip of a slug
    // of the catalogue. One sent by the administrator keeps its own name.
    foreach ($catalogue as $slug => $entry) {
        foreach (glob(STAGE . "/$slug-*") ?: [] as $file) {
            $name = basename($file);
            if (isset($ours[$name]) || isset($kept[$name]) || isset($kept[substr($name, 0, -13) . '.zip'])) {
                continue;
            }
            if (preg_match('/^' . preg_quote($slug, '/') . '-[0-9][0-9a-zA-Z.\-+]*(\.zip|-manifest\.php)$/', $name) === 1) {
                unlink($file);
            }
        }
    }
}

$command = (string) ($argv[1] ?? '');
$slugs = array_slice($argv, 2);
$catalogue = catalogue();

// One extension, in its own process (child above).
if ($command === 'one-install' || $command === 'one-uninstall') {
    $slug = (string) ($slugs[0] ?? '');
    if (!suitecrm()) {
        fail('SuiteCRM n’est pas encore installé');
    }
    $current = installed()[$slug] ?? null;
    if ($command === 'one-install') {
        if (!isset($catalogue[$slug])) {
            fail("extension « $slug » absente du catalogue");
        }
        install($catalogue[$slug], $current);
    } elseif ($current !== null) {
        uninstall($slug, $current);
    }
    exit(0);
}

if ($command === 'sync') {
    if (!suitecrm()) {
        // Before the installation of SuiteCRM: nothing is installed yet, the
        // packages are only put where the Module Loader will list them.
        deposit($catalogue, []);
        exit(0);
    }
    $installed = installed();
    deposit($catalogue, $installed);

    foreach ($catalogue as $slug => $entry) {
        $current = $installed[$slug] ?? null;
        if ($current !== null && version_compare($current['version'], $entry['version'], '<')) {
            child('one-install', $slug);
        }
    }
    exit(0);
}

if ($command === 'list') {
    $installed = suitecrm() ? installed() : [];
    foreach ($catalogue as $slug => $entry) {
        $current = $installed[$slug] ?? null;
        printf(
            "  %-28s %-10s %s\n",
            $slug,
            $entry['version'],
            $current === null ? 'disponible' : ($current['version'] === $entry['version'] ? 'installée' : "installée en {$current['version']}"),
        );
    }
    foreach ($installed as $slug => $current) {
        if (!isset($catalogue[$slug])) {
            printf("  %-28s %-10s installée, absente de l'image\n", $slug, $current['version']);
        }
    }
    exit(0);
}

if ($command !== 'install' && $command !== 'uninstall') {
    fail("commande inconnue « $command » (sync, install, uninstall, list)");
}

if ($slugs === ['--required']) {
    $slugs = [];
    foreach ($catalogue as $slug => $entry) {
        if ($entry['required']) {
            $slugs[] = $slug;
        }
    }
    if ($slugs === []) {
        exit(0);
    }
}
if ($slugs === []) {
    fail("$command : aucune extension donnée");
}
if (!suitecrm()) {
    fail('SuiteCRM n’est pas encore installé');
}

$installed = installed();
foreach ($slugs as $slug) {
    if (!isset($catalogue[$slug]) && !isset($installed[$slug])) {
        fail("extension « $slug » inconnue (catalogue : " . (implode(', ', array_keys($catalogue)) ?: 'vide') . ')');
    }
}

deposit($catalogue, $installed);

foreach ($slugs as $slug) {
    $current = $installed[$slug] ?? null;
    if ($command === 'install') {
        if ($current !== null && $current['version'] === ($catalogue[$slug]['version'] ?? '')) {
            printf("  %s %s : déjà installée\n", $slug, $current['version']);

            continue;
        }
        child('one-install', $slug);

        continue;
    }
    if ($current === null) {
        printf("  %s : pas installée\n", $slug);

        continue;
    }
    child('one-uninstall', $slug);
}
