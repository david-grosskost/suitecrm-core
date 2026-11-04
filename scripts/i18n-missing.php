<?php

/**
 * Lists the labels of SuiteCRM's English language files that a language
 * (fr_FR by default) does not translate, and writes them as complement files
 * ready to translate: one per language file, mirroring its path under
 * public/legacy, each line an assignment with the English text to replace.
 *
 * Run on the reference code of the image, complements of lang/ included:
 * what it lists is what is still missing (make i18n@missing).
 *
 * @usage php scripts/i18n-missing.php <public/legacy> <output dir> [fr_FR]
 */

declare(strict_types=1);

if ($argc < 3) {
    fwrite(STDERR, "usage : php i18n-missing.php <public/legacy> <dossier de sortie> [fr_FR]\n");
    exit(1);
}

$legacy = realpath($argv[1]);
$output = rtrim($argv[2], '/');
$lang = $argv[3] ?? 'fr_FR';
if ($legacy === false) {
    fwrite(STDERR, "i18n-missing : dossier introuvable : {$argv[1]}\n");
    exit(1);
}

// SuiteCRM language files refuse to load outside SuiteCRM without this.
define('sugarEntry', true);

// A few labels call SuiteCRM classes (theme images...): any unknown class
// becomes a stub whose methods return an empty string.
spl_autoload_register(static function (string $class): void {
    eval('class ' . $class . ' {
        public static function __callStatic($n, $a) { return new self(); }
        public function __call($n, $a) { return ""; }
        public function __toString(): string { return ""; }
    }');
});

/**
 * Label arrays a language file defines, loaded in an isolated scope.
 *
 * @return array<string, array<mixed>>
 */
function i18n_load(string $file): array
{
    // The file sets $mod_strings, $app_strings... in the closure's scope.
    $loader = static function (string $file): array {
        include $file;

        return get_defined_vars();
    };

    $strings = [];
    foreach ($loader($file) as $name => $value) {
        if (in_array($name, ['mod_strings', 'app_strings', 'app_list_strings', 'mod_list_strings'], true)
            && is_array($value) && $value !== []) {
            $strings[$name] = $value;
        }
    }

    return $strings;
}

/**
 * Texts of $en missing from $target, keyed by their path.
 *
 * @param array<mixed>     $en
 * @param list<int|string> $path
 *
 * @return list<array{path: list<int|string>, text: string}>
 */
function i18n_missing(array $en, mixed $target, array $path = []): array
{
    $out = [];
    foreach ($en as $key => $value) {
        $here = [...$path, $key];
        $exists = is_array($target) && array_key_exists($key, $target);
        if (is_array($value)) {
            array_push($out, ...i18n_missing($value, $exists ? $target[$key] : null, $here));
        } elseif (is_string($value) && trim(strip_tags($value)) !== '' && !$exists) {
            $out[] = ['path' => $here, 'text' => $value];
        }
    }

    return $out;
}

/** PHP literal of a key or a text. */
function i18n_literal(int|string $value): string
{
    return is_int($value) ? (string) $value : var_export($value, true);
}

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($legacy, FilesystemIterator::SKIP_DOTS));
$report = [];
foreach ($files as $file) {
    $path = (string) $file;
    if (!preg_match('#/language/en_us(\.[^/]*)?\.php$#', $path)) {
        continue;
    }
    $relative = str_replace('en_us', $lang, substr($path, strlen($legacy) + 1));
    $target = $legacy . '/' . $relative;
    $translated = is_file($target) ? i18n_load($target) : [];

    $lines = [];
    foreach (i18n_load($path) as $var => $strings) {
        foreach (i18n_missing($strings, $translated[$var] ?? null) as $item) {
            $keys = implode('', array_map(static fn($key): string => '[' . i18n_literal($key) . ']', $item['path']));
            $lines[] = '$' . $var . $keys . ' = ' . i18n_literal($item['text']) . ';';
        }
    }
    if ($lines === []) {
        continue;
    }

    $out = $output . '/' . $relative;
    if (!is_dir(dirname($out))) {
        mkdir(dirname($out), 0o755, true);
    }
    file_put_contents($out, "<?php\n\n// " . count($lines) . " labels missing from {$relative}\n" . implode("\n", $lines) . "\n");
    $report[$relative] = count($lines);
}

arsort($report);
foreach ($report as $file => $count) {
    printf("%6d  %s\n", $count, $file);
}
printf("%6d  libellés non traduits (%s) dans %d fichiers → %s\n", array_sum($report), $lang, count($report), $output);
