<?php

declare(strict_types=1);

namespace SuiteCrmCore\Seed\Factories;

use Faker\Generator;

/**
 * Makes the fields of one generated record, French (Faker fr_FR, see
 * database/README.md). The same Faker seed gives the same records: generated
 * data is as stable as the hand-written one.
 */
abstract class Factory
{
    public function __construct(protected readonly Generator $faker) {}

    /** French Faker, seeded: the same seed always generates the same data. */
    public static function faker(int $seed = 2026): Generator
    {
        $faker = \Faker\Factory::create('fr_FR');
        $faker->seed($seed);

        return $faker;
    }

    /**
     * One element of a list, drawn by the seeded Faker.
     *
     * @template T
     *
     * @param non-empty-list<T> $list
     *
     * @return T
     */
    protected function pick(array $list): mixed
    {
        return $list[$this->faker->numberBetween(0, count($list) - 1)];
    }
}
