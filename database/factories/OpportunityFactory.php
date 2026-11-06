<?php

declare(strict_types=1);

namespace SuiteCrmCore\Seed\Factories;

use SuiteCrmCore\Seed\Fr;
use SuiteCrmCore\Seed\SeedContext;

/**
 * A generated sales opportunity: French project name, amount, stage, and a
 * closing date consistent with the stage (in the past once closed).
 */
final class OpportunityFactory extends Factory
{
    /** Projects a CRM integrator sells. */
    private const PROJECTS = [
        'Déploiement CRM', 'Contrat de maintenance', 'Formation des équipes', 'Migration des données',
        'Portail clients', 'Automatisation des devis', 'Connecteur comptabilité', 'Tableaux de bord commerciaux',
        'Application mobile terrain', 'Campagnes e-mailing', 'Gestion des contrats', 'Support premium',
    ];

    /** Sales stages (sales_stage_dom keys); their probability comes from SuiteCRM. */
    private const STAGES = [
        'Prospecting', 'Qualification', 'Needs Analysis', 'Value Proposition', 'Id. Decision Makers',
        'Perception Analysis', 'Proposal/Price Quote', 'Negotiation/Review', 'Closed Won', 'Closed Lost',
    ];

    /** Lead sources (lead_source_dom). */
    private const SOURCES = ['Cold Call', 'Existing Customer', 'Partner', 'Trade Show', 'Web Site', 'Word of mouth', 'Email', 'Conference'];

    /**
     * Fields of an opportunity (account and contact links are set by the seeder).
     *
     * @return array{name: string, amount: int, currency_id: string, sales_stage: string, probability: int,
     *               date_closed: string, opportunity_type: string, lead_source: string}
     */
    public function make(): array
    {
        $stage = $this->pick(self::STAGES);
        $closed = str_starts_with($stage, 'Closed');

        return [
            'name' => $this->pick(self::PROJECTS),
            'amount' => $this->faker->numberBetween(15, 800) * 100,
            'currency_id' => '-99',
            'sales_stage' => $stage,
            'probability' => SeedContext::probability($stage),
            'date_closed' => Fr::date($closed ? -$this->faker->numberBetween(1, 180) : $this->faker->numberBetween(1, 180)),
            'opportunity_type' => $this->pick(['New Business', 'Existing Business']),
            'lead_source' => $this->pick(self::SOURCES),
        ];
    }
}
