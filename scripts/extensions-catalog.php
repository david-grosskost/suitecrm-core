<?php

// Builds the catalogue of extensions of the image, at build time (Dockerfile,
// "vendor" stage): every Composer package of type suitecrm-extension that
// composer.json requires, checked, then built into a Module Loader package
// (suitecrm-devkit, .make/scripts/package/build.php) — the same zip the
// extension attaches to its own releases.
//
// The entrypoint puts those packages in the list of available packages of
// Administration → Module Loader (scripts/packages.php): the administrator
// installs and uninstalls them from there, and only from there.
//
// Written to <catalogue>/ : <slug>-<version>.zip, its <slug>-<version>-manifest.php
// (what the Module Loader reads to list a package), and index.json, which
// names the file of each slug and says which ones a new instance gets. The
// demo data of an extension (database/seeders/) goes to <catalogue>/seeders/<slug>/:
// it is not part of a package, make db@seed loads it (database/README.md).
//
// Fails the build when an extension is broken or does not declare the
// SuiteCRM version of the image (decision D8).
//
// @usage php extensions-catalog.php <suitecrm-version> <vendor-dir> <catalog-dir>

declare(strict_types=1);

use Composer\Semver\Semver;

const DEVKIT = 'david-grosskost/suitecrm-devkit';
const BUILD_SCRIPT = '/.make/scripts/package/build.php';

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

/**
 * Reads a JSON file as an array, or null.
 *
 * @return array<mixed>|null
 */
function readJson(string $file): ?array
{
    $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;

    return is_array($data) ? $data : null;
}

$installed = readJson($vendor . '/composer/installed.json');
$packages = is_array($installed['packages'] ?? null) ? $installed['packages'] : [];

// The build script of the devkit, a development dependency: the image build
// installs it, builds the packages, then prunes it (see .docker/Dockerfile).
$build = realpath($vendor . '/' . DEVKIT . BUILD_SCRIPT);
if ($build === false) {
    fwrite(STDERR, "\n  Catalogue des extensions : " . DEVKIT . " absent de $vendor\n"
        . "  (le build de l'image installe les dépendances de développement avant cette étape)\n\n");
    exit(1);
}

// Extensions a new instance gets without being asked (composer.json,
// extra.suitecrm.required-extensions): the fixes of SuiteCRM itself. An
// administrator may still uninstall them afterwards.
$root = readJson('composer.json') ?? [];
$extra = is_array($root['extra'] ?? null) ? $root['extra'] : [];
$core = is_array($extra['suitecrm'] ?? null) ? $extra['suitecrm'] : [];
$required = is_array($core['required-extensions'] ?? null) ? $core['required-extensions'] : [];

@mkdir($catalog, 0o755, true);
$errors = [];
$index = [];

foreach ($packages as $package) {
    if (!is_array($package) || ($package['type'] ?? null) !== 'suitecrm-extension') {
        continue;
    }
    $name = is_string($package['name'] ?? null) ? $package['name'] : '?';
    $dir = $vendor . '/' . $name;
    $manifest = readJson($dir . '/extension.json');
    if ($manifest === null || !is_string($manifest['slug'] ?? null) || !is_string($manifest['suitecrm'] ?? null)) {
        $errors[] = "$name : extension.json absent ou incomplet (make extension@validate dans l'extension)";

        continue;
    }

    $slug = $manifest['slug'];
    if (isset($index[$slug])) {
        $errors[] = "$name : slug « $slug » déjà pris par {$index[$slug]['package']}";

        continue;
    }

    if (!Semver::satisfies($suitecrm, $manifest['suitecrm'])) {
        $errors[] = "$name : compatible SuiteCRM « {$manifest['suitecrm']} », l'image est en $suitecrm";

        continue;
    }

    // The package is built where the extension is, as make package@build does
    // it: the script reads extension.json and .release-please-manifest.json
    // there, and writes build/<slug>-<version>.zip.
    $output = [];
    $status = 0;
    exec(sprintf(
        'cd %s && rm -rf build && php -d phar.readonly=0 %s %s 2>&1',
        escapeshellarg($dir),
        escapeshellarg($build),
        escapeshellarg($suitecrm),
    ), $output, $status);
    $zips = glob("$dir/build/*.zip") ?: [];
    if ($status !== 0 || count($zips) !== 1) {
        $errors[] = "$name : paquet du Chargeur de module non construit\n      " . implode("\n      ", $output);

        continue;
    }

    $zip = $zips[0];
    $file = basename($zip);
    $packageManifest = substr($file, 0, -4) . '-manifest.php';
    // The Module Loader lists a package from the manifest.php kept beside its
    // zip, not from the one inside it.
    if (!copy($zip, "$catalog/$file") || !copy("phar://$zip/manifest.php", "$catalog/$packageManifest")) {
        $errors[] = "$name : paquet impossible à copier dans le catalogue";

        continue;
    }

    // Demo data: outside the package, loaded by make db@seed.
    if (is_dir("$dir/database/seeders")) {
        copyTree("$dir/database/seeders", "$catalog/seeders/$slug");
    }

    $index[$slug] = [
        'slug' => $slug,
        'name' => is_string($manifest['name'] ?? null) ? $manifest['name'] : $slug,
        'package' => $name,
        // The version of the package, the one SuiteCRM keeps once installed:
        // release-please's, which names the file, not Composer's tag.
        'version' => substr($file, strlen($slug) + 1, -4),
        'file' => $file,
        'manifest' => $packageManifest,
        'required' => in_array($slug, $required, true),
    ];
    echo "  Extension $slug : $file" . ($index[$slug]['required'] ? ' (installée d’office)' : '') . "\n";
}

foreach ($required as $slug) {
    if (!is_string($slug) || !isset($index[$slug])) {
        $errors[] = 'extra.suitecrm.required-extensions : extension « '
            . (is_string($slug) ? $slug : '?') . ' » absente des dépendances';
    }
}

if ($errors !== []) {
    fwrite(STDERR, "\n  Catalogue des extensions : problèmes\n\n");
    foreach ($errors as $error) {
        fwrite(STDERR, "    ✘ $error\n");
    }
    exit(1);
}

ksort($index);
file_put_contents(
    "$catalog/index.json",
    json_encode(array_values($index), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n",
);

echo '  Catalogue : ' . ($index === [] ? 'aucune extension' : implode(', ', array_keys($index))) . ".\n";
