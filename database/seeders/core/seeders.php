<?php

// Seeders of suitecrm-core: the base demo data, in French.

declare(strict_types=1);

use SuiteCrmCore\Seed\Core\AccountsSeeder;
use SuiteCrmCore\Seed\Core\ContactsSeeder;
use SuiteCrmCore\Seed\Core\OpportunitiesSeeder;

require_once __DIR__ . '/AccountsSeeder.php';
require_once __DIR__ . '/ContactsSeeder.php';
require_once __DIR__ . '/OpportunitiesSeeder.php';

return [
    new AccountsSeeder(),
    new ContactsSeeder(),
    new OpportunitiesSeeder(),
];
