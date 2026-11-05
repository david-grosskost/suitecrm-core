<?php

declare(strict_types=1);

namespace SuiteCrmCore\Seed;

/**
 * A set of demo data, e.g. the accounts, or the contacts of these accounts.
 *
 * Contract (see database/README.md):
 * - run() may be called again on data already seeded: it updates the records
 *   instead of duplicating them (SeedContext::upsert(), stable ids).
 * - Records of another seeder are reached by their id (SeedContext::id()),
 *   that seeder being listed in dependsOn(): it runs first.
 */
interface Seeder
{
    /** Unique name, "<package>/<data>", e.g. "core/accounts". */
    public function name(): string;

    /**
     * Seeders whose records this one links to, run before it.
     *
     * @return list<string>
     */
    public function dependsOn(): array;

    public function run(SeedContext $seed): void;
}
