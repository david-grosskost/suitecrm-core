<?php

// Runs the install or uninstall script of extensions, in SuiteCRM, as the
// administrator: what an extension does once, beyond its files (e.g. put its
// panel in a Studio layout), or undoes when it is turned off.
//
// - install: database/install.php of each extension given, from the catalogue
//   of the image, once: at its first activation (suitecrm-extensions), or
//   after the first installation of SuiteCRM (suitecrm-install). Done once
//   it has succeeded: .extensions/<slug>.installed. A later version of the
//   extension does not run it again.
// - uninstall: database/uninstall.php of each extension given, from the copy
//   kept at its activation (.extensions/<slug>.extension/): the image may no
//   longer have the extension. Run by suitecrm-extensions before its files
//   are taken out, so that SuiteCRM still knows its fields. Then the install
//   counts as not done: activated again, the extension installs again.
//
// An extension without the script is skipped. A script that fails (exception)
// stops the start: not marked done, it is tried again at the next start.
//
// @usage php /usr/local/share/suitecrm/extension-script.php <install|uninstall> <slug> [<slug> ...]

declare(strict_types=1);

$action = (string) ($argv[1] ?? '');
$slugs = array_slice($argv, 2);
if (!in_array($action, ['install', 'uninstall'], true)) {
    fwrite(STDERR, "usage : php extension-script.php <install|uninstall> <slug> [<slug> ...]\n");
    exit(2);
}

const STATE = '/var/www/html/.extensions';

$scripts = [];
foreach ($slugs as $slug) {
    $done = STATE . "/{$slug}.installed";
    $file = $action === 'install'
        ? "/usr/local/share/suitecrm/extensions/{$slug}/database/install.php"
        : STATE . "/{$slug}.extension/database/uninstall.php";
    if ($action === 'install' && is_file($done)) {
        continue;
    }
    if ($action === 'uninstall') {
        @unlink($done);
    }
    if (is_file($file)) {
        $scripts[$slug] = $file;
    }
}
if ($scripts === []) {
    exit(0);
}

if (!defined('sugarEntry')) {
    define('sugarEntry', true);
}
chdir('/var/www/html/public/legacy');
require_once 'include/entryPoint.php';

global $current_user;
$current_user = BeanFactory::getBean('Users', '1');
if (!$current_user instanceof User || empty($current_user->id)) {
    fwrite(STDERR, "extension-script : administrateur introuvable\n");
    exit(1);
}

foreach ($scripts as $slug => $file) {
    try {
        // Own scope: the script sees only what it declares global. It returns
        // its message rather than printing it (echo is refused by the scanner
        // of the Module Loader, where the same script runs too).
        $message = (static function (string $file): mixed {
            return require $file;
        })($file);
        if (is_string($message) && $message !== '') {
            echo "  {$message}\n";
        }
    } catch (Throwable $exception) {
        fwrite(STDERR, "  {$slug} : script {$action} en échec : {$exception->getMessage()}\n");
        exit(1);
    }
    if ($action === 'install') {
        touch(STATE . "/{$slug}.installed");
    }
    echo "  {$slug} : script {$action} fait.\n";
}
