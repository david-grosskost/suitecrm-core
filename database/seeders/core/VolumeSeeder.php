<?php

declare(strict_types=1);

namespace SuiteCrmCore\Seed\Core;

use SuiteCrmCore\Seed\Factories\AccountFactory;
use SuiteCrmCore\Seed\Factories\ContactFactory;
use SuiteCrmCore\Seed\Factories\Factory;
use SuiteCrmCore\Seed\Factories\OpportunityFactory;
use SuiteCrmCore\Seed\SeedContext;
use SuiteCrmCore\Seed\Seeder;

/**
 * Generated volume, on top of the hand-written data, for well-filled lists or
 * performance tests: SEED_COUNT accounts (0 by default: writes nothing), each
 * with 1 to 3 contacts and 0 to 2 opportunities.
 *
 * Stable like the others: the same SEED_COUNT gives the same records (seeded
 * Faker), run again it updates them; a higher one adds the missing accounts.
 *
 * @usage make db@seed SEEDERS="core/volume" COUNT=200
 */
final class VolumeSeeder implements Seeder
{
    /**
     * Fictional phone numbers of the generated data, after those of the
     * hand-written data (up to 2000): accounts from 3001, contacts from 5001.
     * At most MAX accounts keeps both ranges apart and within 9999.
     */
    private const FIRST_ACCOUNT_NUMBER = 3000;
    private const FIRST_CONTACT_NUMBER = 5000;
    public const MAX = 1500;

    public function __construct(private readonly int $count)
    {
        if ($count < 0 || $count > self::MAX) {
            throw new \RuntimeException('seed : COUNT doit être entre 0 et ' . self::MAX . " (reçu {$count})");
        }
    }

    public function name(): string
    {
        return 'core/volume';
    }

    public function dependsOn(): array
    {
        return [];
    }

    public function run(SeedContext $seed): void
    {
        foreach ($this->plan() as $key => [$account, $contacts, $opportunities]) {
            $accountId = $seed->upsert('Accounts', $key, $account);

            $contactIds = [];
            foreach ($contacts as $rank => $contact) {
                $contactIds[] = $seed->upsert('Contacts', $key . '-' . ($rank + 1), $contact + ['account_id' => $accountId]);
            }
            foreach ($opportunities as $rank => $opportunity) {
                $seed->upsert(
                    'Opportunities',
                    $key . '-' . ($rank + 1),
                    $opportunity + ['account_id' => $accountId, 'contact_id' => $contactIds[0]],
                );
            }
        }
    }

    /**
     * Every generated record, drawn before anything is saved: SuiteCRM also
     * uses PHP's random generator when saving (new ids of e-mail addresses),
     * which would shift Faker's draws from one run to the next.
     *
     * @return array<string, array{array<string, string>, list<array<string, string>>, list<array<string, string|int>>}>
     */
    private function plan(): array
    {
        if ($this->count === 0) {
            return [];
        }

        $faker = Factory::faker();
        $accounts = new AccountFactory($faker);
        $contacts = new ContactFactory($faker);
        $opportunities = new OpportunityFactory($faker);

        $plan = [];
        for ($index = 1; $index <= $this->count; ++$index) {
            $account = $accounts->make(self::FIRST_ACCOUNT_NUMBER + $index);

            $accountContacts = [];
            $contactCount = $faker->numberBetween(1, 3);
            for ($rank = 1; $rank <= $contactCount; ++$rank) {
                $accountContacts[] = $contacts->make(self::FIRST_CONTACT_NUMBER + ($index - 1) * 3 + $rank, $account['name']);
            }

            $accountOpportunities = [];
            $opportunityCount = $faker->numberBetween(0, 2);
            for ($rank = 1; $rank <= $opportunityCount; ++$rank) {
                $accountOpportunities[] = $opportunities->make();
            }

            $plan['volume-' . $index] = [$account, $accountContacts, $accountOpportunities];
        }

        return $plan;
    }
}
