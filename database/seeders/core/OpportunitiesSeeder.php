<?php

declare(strict_types=1);

namespace SuiteCrmCore\Seed\Core;

use SuiteCrmCore\Seed\Fr;
use SuiteCrmCore\Seed\SeedContext;
use SuiteCrmCore\Seed\Seeder;

/**
 * A sales pipeline at every stage, won and lost deals included, with closing
 * dates relative to today: the demo pipeline always looks current.
 */
final class OpportunitiesSeeder implements Seeder
{
    /**
     * Key => account key, name, amount (€), sales stage (sales_stage_dom),
     * closing date in days from today, type (opportunity_type_dom), lead source (lead_source_dom).
     *
     * @var array<string, array{string, string, int, string, int, string, string}>
     */
    public const OPPORTUNITIES = [
        'durand-crm' => ['durand-btp', 'Déploiement CRM chantiers', 18500, 'Negotiation/Review', 12, 'New Business', 'Trade Show'],
        'durand-maintenance' => ['durand-btp', 'Contrat de maintenance annuel', 4200, 'Closed Won', -40, 'Existing Business', 'Existing Customer'],
        'vosgien-catalogue' => ['atelier-vosgien', 'Catalogue produits et devis en ligne', 9800, 'Qualification', 45, 'New Business', 'Web Site'],
        'lemoine-portail' => ['cabinet-lemoine', 'Portail clients du cabinet', 12000, 'Proposal/Price Quote', 20, 'Existing Business', 'Existing Customer'],
        'garnier-flotte' => ['transports-garnier', 'Suivi de flotte et relation transporteurs', 36000, 'Id. Decision Makers', 60, 'New Business', 'Partner'],
        'garnier-formation' => ['transports-garnier', 'Formation des exploitants', 3500, 'Closed Won', -15, 'Existing Business', 'Existing Customer'],
        'bel-air-rdv' => ['clinique-bel-air', 'Prise de rendez-vous et rappels', 5400, 'Needs Analysis', 30, 'New Business', 'Word of mouth'],
        'roches-export' => ['domaine-roches', 'Gestion des importateurs export', 7600, 'Value Proposition', 35, 'Existing Business', 'Existing Customer'],
        'numeris-revente' => ['numeris-conseil', 'Accord de revente 2027', 25000, 'Perception Analysis', 75, 'New Business', 'Partner'],
        'cap-azur-groupes' => ['hotel-cap-azur', 'Gestion des séminaires et groupes', 14500, 'Prospecting', 90, 'New Business', 'Cold Call'],
        'artois-devis' => ['imprimerie-artois', 'Automatisation des devis', 6900, 'Closed Lost', -25, 'Existing Business', 'Existing Customer'],
        'sillon-installateurs' => ['energies-sillon', 'Réseau d\'installateurs partenaires', 42000, 'Qualification', 80, 'New Business', 'Conference'],
        'jardins-adherents' => ['jardins-partages', 'Gestion des adhérents et dons', 2400, 'Closed Won', -60, 'New Business', 'Email'],
        'rhenans-essais' => ['laboratoires-rhenans', 'Suivi des partenaires d\'essais', 58000, 'Needs Analysis', 120, 'New Business', 'Conference'],
        'moreau-atelier' => ['garage-moreau', 'Relances entretien clients', 3900, 'Proposal/Price Quote', 10, 'Existing Business', 'Existing Customer'],
        'lingua-inscriptions' => ['lingua-formation', 'Inscriptions et suivi des stagiaires', 8700, 'Negotiation/Review', 8, 'Existing Business', 'Existing Customer'],
        'techsud-licences' => ['techsud', 'Lot de licences revendeur', 21000, 'Closed Won', -5, 'Existing Business', 'Partner'],
        'atlantique-adherents' => ['mutuelle-atlantique', 'Espace adhérents et réclamations', 64000, 'Prospecting', 150, 'New Business', 'Public Relations'],
        'perrin-devis' => ['menuiserie-perrin', 'Devis et planning de pose', 2900, 'Value Proposition', 25, 'New Business', 'Word of mouth'],
        'fibres-chantiers' => ['reseaux-fibres-est', 'Pilotage des chantiers de raccordement', 31000, 'Closed Lost', -90, 'New Business', 'Partner'],
        'roux-boutiques' => ['maison-roux', 'Fidélité clients des boutiques', 6200, 'Qualification', 40, 'Existing Business', 'Existing Customer'],
        'pixel-production' => ['studio-pixel', 'Suivi des productions et clients', 11500, 'Prospecting', 70, 'New Business', 'Web Site'],
    ];

    public function name(): string
    {
        return 'core/opportunities';
    }

    public function dependsOn(): array
    {
        return ['core/accounts', 'core/contacts'];
    }

    public function run(SeedContext $seed): void
    {
        foreach (self::OPPORTUNITIES as $key => [$account, $name, $amount, $stage, $closeIn, $type, $source]) {
            $seed->upsert('Opportunities', $key, [
                'name' => $name,
                'amount' => $amount,
                'currency_id' => '-99',
                'sales_stage' => $stage,
                'probability' => SeedContext::probability($stage),
                'date_closed' => Fr::date($closeIn),
                'opportunity_type' => $type,
                'lead_source' => $source,
                'account_id' => SeedContext::id('Accounts', $account),
                'contact_id' => SeedContext::id('Contacts', ContactsSeeder::key($account, 0)),
            ]);
        }
    }
}
