<?php

declare(strict_types=1);

namespace SuiteCrmCore\Seed\Factories;

use SuiteCrmCore\Seed\Fr;

/**
 * A generated French company: a name built from a French surname (Faker),
 * a trade and sometimes a legal form, a real town, fictional phone and
 * website (see Fr).
 */
final class AccountFactory extends Factory
{
    /** Account types (account_type_dom), most of them customers and prospects. */
    private const TYPES = ['Customer', 'Customer', 'Customer', 'Prospect', 'Prospect', 'Partner', 'Reseller'];

    /** Industries (industry_dom). */
    private const INDUSTRIES = [
        'Construction', 'Manufacturing', 'Retail', 'Consulting', 'Technology', 'Healthcare', 'Transportation',
        'Finance', 'Hospitality', 'Education', 'Energy', 'Media', 'Insurance', 'Engineering', 'Other',
    ];

    /** Trades in company names: "Martin Bâtiment", "Transports Leroy". */
    private const TRADES = [
        'Bâtiment', 'Conseil', 'Transports', 'Informatique', 'Menuiserie', 'Électricité', 'Immobilier',
        'Distribution', 'Ingénierie', 'Services', 'Industrie', 'Paysages', 'Logistique', 'Plomberie', 'Communication',
    ];

    /** Legal forms, sometimes added after the name. */
    private const FORMS = ['SARL', 'SAS', 'EURL', 'SA'];

    /** Street names, numbered by Faker. */
    private const STREETS = [
        'rue de la République', 'avenue Jean Jaurès', 'rue Victor Hugo', 'boulevard Gambetta', 'rue Pasteur',
        'place de la Mairie', 'rue du Général de Gaulle', 'avenue de la Gare', 'rue des Écoles', 'zone artisanale des Prés',
    ];

    /**
     * Fields of the account number $number (its phone depends on it).
     *
     * @return array{name: string, account_type: string, industry: string, employees: string, phone_office: string,
     *               website: string, billing_address_street: string, billing_address_postalcode: string,
     *               billing_address_city: string, billing_address_country: string}
     */
    public function make(int $number): array
    {
        $name = $this->name();
        [$postcode, $town] = $this->pick(Fr::TOWNS);

        return [
            'name' => $name,
            'account_type' => $this->pick(self::TYPES),
            'industry' => $this->pick(self::INDUSTRIES),
            'employees' => (string) $this->faker->numberBetween(2, 250),
            'phone_office' => Fr::phone($number),
            'website' => Fr::website($name),
            'billing_address_street' => $this->faker->numberBetween(1, 120) . ' ' . $this->pick(self::STREETS),
            'billing_address_postalcode' => $postcode,
            'billing_address_city' => $town,
            'billing_address_country' => 'France',
        ];
    }

    /** A credible French company name: never a bare surname. */
    private function name(): string
    {
        $lastName = $this->faker->lastName();
        $trade = $this->pick(self::TRADES);

        $name = match ($this->faker->numberBetween(1, 5)) {
            1 => $lastName . ' ' . $trade,
            2 => $trade . ' ' . $lastName,
            3 => $lastName . ' et Fils',
            4 => $lastName . ' & ' . $this->faker->lastName(),
            default => $lastName . ' ' . $trade,
        };

        return $this->faker->numberBetween(0, 2) === 0 ? $name : $name . ' ' . $this->pick(self::FORMS);
    }
}
