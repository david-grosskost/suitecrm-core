<?php

declare(strict_types=1);

namespace SuiteCrmCore\Seed;

/**
 * Finds the seeders and puts them in an order where each one runs after
 * those it depends on.
 */
final class Runner
{
    /**
     * Seeders declared by every package: each <package>/seeders.php under one
     * of the $directories returns a list of Seeder.
     *
     * @param list<string> $directories
     *
     * @return array<string, Seeder> By name
     */
    public static function discover(array $directories): array
    {
        $files = [];
        foreach ($directories as $directory) {
            array_push($files, ...(glob($directory . '/*/seeders.php') ?: []));
        }

        $seeders = [];
        foreach ($files as $file) {
            $declared = require $file;
            if (!is_array($declared)) {
                throw new \RuntimeException("seed : {$file} doit renvoyer une liste de seeders");
            }
            foreach ($declared as $seeder) {
                if (!$seeder instanceof Seeder) {
                    throw new \RuntimeException("seed : {$file} renvoie autre chose qu'un Seeder");
                }
                if (isset($seeders[$seeder->name()])) {
                    throw new \RuntimeException("seed : deux seeders s'appellent « {$seeder->name()} »");
                }
                $seeders[$seeder->name()] = $seeder;
            }
        }

        return $seeders;
    }

    /**
     * The seeders to run, dependencies first. Without $only, all of them;
     * with names, those and what they depend on.
     *
     * @param array<string, Seeder> $seeders By name
     * @param list<string>          $only
     *
     * @return list<Seeder>
     */
    public static function order(array $seeders, array $only = []): array
    {
        $ordered = [];
        $state = [];
        $visit = static function (string $name, string $from) use (&$visit, &$ordered, &$state, $seeders): void {
            if (!isset($seeders[$name])) {
                throw new \RuntimeException("seed : seeder inconnu « {$name} »" . ($from !== '' ? " (demandé par {$from})" : ''));
            }
            if (($state[$name] ?? '') === 'done') {
                return;
            }
            if (($state[$name] ?? '') === 'visiting') {
                throw new \RuntimeException("seed : dépendance circulaire autour de « {$name} »");
            }
            $state[$name] = 'visiting';
            foreach ($seeders[$name]->dependsOn() as $dependency) {
                $visit($dependency, $name);
            }
            $state[$name] = 'done';
            $ordered[] = $seeders[$name];
        };

        $names = $only !== [] ? $only : array_keys($seeders);
        sort($names);
        foreach ($names as $name) {
            $visit($name, '');
        }

        return $ordered;
    }
}
