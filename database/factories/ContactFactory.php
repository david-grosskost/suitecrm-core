<?php

declare(strict_types=1);

namespace SuiteCrmCore\Seed\Factories;

use SuiteCrmCore\Seed\Fr;

/**
 * A generated contact of a company: French name from Faker, job title,
 * fictional phones, e-mail on the company's .example domain.
 */
final class ContactFactory extends Factory
{
    /** Job titles, the first ones being the usual decision makers. */
    private const TITLES = [
        'Gérant', 'Directeur général', 'Directrice commerciale', 'Responsable administratif', 'Responsable des achats',
        'Chef de projet', 'Assistante de direction', 'Responsable technique', 'Comptable', 'Chargé de clientèle',
    ];

    /**
     * Fields of the contact number $number of the company $company.
     *
     * @return array{salutation: string, first_name: string, last_name: string, title: string, phone_work: string,
     *               phone_mobile: string, email1: string}
     */
    public function make(int $number, string $company): array
    {
        $female = $this->faker->numberBetween(0, 1) === 1;
        $firstName = $this->faker->firstName($female ? 'female' : 'male');
        $lastName = $this->faker->lastName();

        return [
            'salutation' => $female ? 'Ms.' : 'Mr.',
            'first_name' => $firstName,
            'last_name' => $lastName,
            'title' => $this->pick(self::TITLES),
            'phone_work' => Fr::phone($number),
            'phone_mobile' => Fr::phone($number, true),
            'email1' => Fr::email($firstName, $lastName, $company),
        ];
    }
}
