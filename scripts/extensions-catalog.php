<?php

// Builds the catalogue of extensions of the image, at build time (Dockerfile,
// "vendor" stage): every Composer package of type suitecrm-extension that
// composer.json requires, checked and copied to catalog/<slug>/ (its
// extension.json, backend/, config/, public/, legacy/, database/). The entrypoint
// activates those listed in EXTENSIONS (scripts/extensions.sh).
// Fails the build when an extension is broken or does not declare the
// SuiteCRM version of the image (decision D8).
//
// @usage php extensions-catalog.php <suitecrm-version> <vendor-dir> <catalog-dir>

declare(strict_types=1);

use Composer\Semver\Semver;

$suitecrm = (string) ($argv[1] ?? '');
$vendor = (string) ($argv[2] ?? '');
$catalog = (string) ($argv[3] ?? '');
if ($suitecrm === '' || $vendor === '' || $catalog === '') {
    fwrite(STDERR, "usage : php extensions-catalog.php <version-suitecrm> <vendor> <catalogue>\n");
    exit(2);
}

require $vendor . '/autoload.php';

/**
 * Copies a directory, recursively.
 */
function copyTree(string $from, string $to): void
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST,
    );
    @mkdir($to, 0o755, true);
    foreach ($iterator as $item) {
        if (!$item instanceof SplFileInfo) {
            continue;
        }
        $target = $to . '/' . substr($item->getPathname(), strlen($from) + 1);
        if ($item->isDir()) {
            @mkdir($target, 0o755, true);
        } else {
            copy($item->getPathname(), $target);
        }
    }
}

$installed = json_decode((string) file_get_contents($vendor . '/composer/installed.json'), true);
$packages = is_array($installed) && is_array($installed['packages'] ?? null) ? $installed['packages'] : [];

@mkdir($catalog, 0o755, true);
$errors = [];
$slugs = [];

foreach ($packages as $package) {
    if (!is_array($package) || ($package['type'] ?? null) !== 'suitecrm-extension') {
        continue;
    }
    $name = is_string($package['name'] ?? null) ? $package['name'] : '?';
    $dir = $vendor . '/' . $name;
    $manifest = json_decode((string) @file_get_contents($dir . '/extension.json'), true);
    if (!is_array($manifest) || !is_string($manifest['slug'] ?? null) || !is_string($manifest['suitecrm'] ?? null)) {
        $errors[] = "$name : extension.json absent ou incomplet (make extension@validate dans l'extension)";

        continue;
    }

    $slug = $manifest['slug'];
    if (isset($slugs[$slug])) {
        $errors[] = "$name : slug « $slug » déjà pris par {$slugs[$slug]}";

        continue;
    }
    $slugs[$slug] = $name;

    if (!Semver::satisfies($suitecrm, $manifest['suitecrm'])) {
        $errors[] = "$name : compatible SuiteCRM « {$manifest['suitecrm']} », l'image est en $suitecrm";

        continue;
    }

    $manifest['package'] = $name;
    $manifest['version'] = is_string($package['version'] ?? null) ? $package['version'] : '';
    @mkdir("$catalog/$slug", 0o755, true);
    file_put_contents(
        "$catalog/$slug/extension.json",
        json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n",
    );
    foreach (['backend', 'config', 'public', 'legacy', 'database'] as $part) {
        if (is_dir("$dir/$part")) {
            copyTree("$dir/$part", "$catalog/$slug/$part");
        }
    }
    echo "  Extension $slug ({$manifest['version']}) ajoutée au catalogue.\n";
}

if ($errors !== []) {
    fwrite(STDERR, "\n  Catalogue des extensions : problèmes\n\n");
    foreach ($errors as $error) {
        fwrite(STDERR, "    ✘ $error\n");
    }
    exit(1);
}

echo '  Catalogue : ' . (count($slugs) === 0 ? 'aucune extension' : implode(', ', array_keys($slugs))) . ".\n";
