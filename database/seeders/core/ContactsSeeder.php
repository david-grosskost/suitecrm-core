<?php

declare(strict_types=1);

namespace SuiteCrmCore\Seed\Core;

use SuiteCrmCore\Seed\Fr;
use SuiteCrmCore\Seed\SeedContext;
use SuiteCrmCore\Seed\Seeder;

/**
 * Two contacts per account: usually the manager and an operational contact.
 */
final class ContactsSeeder implements Seeder
{
    /**
     * Account key => contacts: salutation (salutation_dom), first name, last name, job title.
     * The first contact of each account is the decision maker of its opportunities.
     *
     * @var array<string, list<array{string, string, string, string}>>
     */
    public const CONTACTS = [
        'durand-btp' => [['Mr.', 'Thierry', 'Durand', 'Gérant'], ['Ms.', 'Sandrine', 'Muller', 'Responsable administrative']],
        'atelier-vosgien' => [['Mr.', 'Olivier', 'Thomas', 'Directeur'], ['Ms.', 'Julie', 'Claudel', 'Responsable des achats']],
        'cabinet-lemoine' => [['Ms.', 'Catherine', 'Lemoine', 'Expert-comptable associée'], ['Mr.', 'Karim', 'Benali', 'Collaborateur comptable']],
        'transports-garnier' => [['Mr.', 'Philippe', 'Garnier', 'Président'], ['Ms.', 'Aurélie', 'Faure', 'Directrice des opérations']],
        'clinique-bel-air' => [['Dr.', 'Marion', 'Leroux', 'Vétérinaire associée'], ['Mr.', 'Lucas', 'Bernard', 'Assistant de direction']],
        'domaine-roches' => [['Mr.', 'Bertrand', 'Morel', 'Vigneron, propriétaire'], ['Ms.', 'Claire', 'Morel', 'Responsable commerciale']],
        'numeris-conseil' => [['Ms.', 'Émilie', 'Girard', 'Associée fondatrice'], ['Mr.', 'Antoine', 'Roussel', 'Consultant CRM']],
        'hotel-cap-azur' => [['Mr.', 'Stéphane', 'Rossi', 'Directeur général'], ['Ms.', 'Laura', 'Fabre', 'Responsable réservations']],
        'imprimerie-artois' => [['Mr.', 'Didier', 'Lefebvre', 'Gérant'], ['Ms.', 'Nathalie', 'Duhamel', 'Chargée de clientèle']],
        'energies-sillon' => [['Ms.', 'Gwenaëlle', 'Le Goff', 'Directrice commerciale'], ['Mr.', 'Yann', 'Kerjean', 'Chef de projet']],
        'jardins-partages' => [['Ms.', 'Fatima', 'Haddad', 'Présidente'], ['Mr.', 'Hugo', 'Delattre', 'Trésorier']],
        'laboratoires-rhenans' => [['Dr.', 'Jean-Marc', 'Schneider', 'Directeur scientifique'], ['Ms.', 'Céline', 'Koch', 'Responsable des systèmes d\'information']],
        'garage-moreau' => [['Mr.', 'Patrick', 'Moreau', 'Gérant'], ['Mr.', 'Kevin', 'Petit', 'Chef d\'atelier']],
        'lingua-formation' => [['Ms.', 'Isabelle', 'Martin', 'Directrice pédagogique'], ['Mr.', 'Thomas', 'Vidal', 'Responsable des inscriptions']],
        'techsud' => [['Mr.', 'Julien', 'Mercier', 'Directeur'], ['Ms.', 'Sophie', 'Lambert', 'Responsable partenariats']],
        'mutuelle-atlantique' => [['Mr.', 'François', 'Guérin', 'Directeur du développement'], ['Ms.', 'Anne', 'Robin', 'Responsable relation adhérents']],
        'menuiserie-perrin' => [['Mr.', 'Alain', 'Perrin', 'Artisan menuisier'], ['Ms.', 'Valérie', 'Perrin', 'Secrétaire']],
        'reseaux-fibres-est' => [['Mr.', 'Mathieu', 'Weber', 'Directeur technique'], ['Ms.', 'Pauline', 'Hoffmann', 'Chargée d\'affaires']],
        'maison-roux' => [['Mr.', 'Christophe', 'Roux', 'Artisan boulanger'], ['Ms.', 'Élodie', 'Chabrier', 'Responsable des boutiques']],
        'studio-pixel' => [['Ms.', 'Léa', 'Nguyen', 'Productrice'], ['Mr.', 'Maxime', 'Dupont', 'Directeur artistique']],
    ];

    public function name(): string
    {
        return 'core/contacts';
    }

    public function dependsOn(): array
    {
        return ['core/accounts'];
    }

    public function run(SeedContext $seed): void
    {
        $number = 0;
        foreach (self::CONTACTS as $account => $contacts) {
            [$company] = AccountsSeeder::ACCOUNTS[$account];
            foreach ($contacts as $index => [$salutation, $firstName, $lastName, $title]) {
                ++$number;
                $seed->upsert('Contacts', self::key($account, $index), [
                    'salutation' => $salutation,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'title' => $title,
                    'phone_work' => Fr::phone($number),
                    'phone_mobile' => Fr::phone($number, true),
                    'email1' => Fr::email($firstName, $lastName, $company),
                    'account_id' => SeedContext::id('Accounts', $account),
                ]);
            }
        }
    }

    /** Key of a contact: its account and its rank ("durand-btp-1"). */
    public static function key(string $account, int $index): string
    {
        return $account . '-' . ($index + 1);
    }
}
