<?php

declare(strict_types=1);

namespace SuiteCrmCore\Seed\Core;

use SuiteCrmCore\Seed\Fr;
use SuiteCrmCore\Seed\SeedContext;
use SuiteCrmCore\Seed\Seeder;

/**
 * Twenty fictional French companies, of every size and trade, in real towns:
 * customers, prospects and partners. Names, numbers and addresses are invented
 * (fictional phone numbers and .example domains, see Fr).
 */
final class AccountsSeeder implements Seeder
{
    /**
     * Key => name, type, industry, street, postcode, town, employees.
     * Type and industry are SuiteCRM dropdown keys (account_type_dom, industry_dom).
     *
     * @var array<string, array{string, string, string, string, string, string, int}>
     */
    public const ACCOUNTS = [
        'durand-btp' => ['Bâtiments Durand & Fils', 'Customer', 'Construction', '12 rue des Tanneurs', '67000', 'Strasbourg', 85],
        'atelier-vosgien' => ['Atelier Vosgien du Meuble', 'Prospect', 'Manufacturing', '4 chemin de la Scierie', '88400', 'Gérardmer', 32],
        'cabinet-lemoine' => ['Cabinet Lemoine Expertise', 'Customer', 'Finance', '27 cours Mirabeau', '13100', 'Aix-en-Provence', 18],
        'transports-garnier' => ['Transports Garnier Logistique', 'Customer', 'Transportation', 'ZA des Gravières', '69800', 'Saint-Priest', 140],
        'clinique-bel-air' => ['Clinique Vétérinaire Bel Air', 'Prospect', 'Healthcare', '8 avenue Jean Jaurès', '44000', 'Nantes', 12],
        'domaine-roches' => ['Domaine des Roches Blanches', 'Customer', 'Other', '3 route des Vignes', '21200', 'Beaune', 9],
        'numeris-conseil' => ['Numéris Conseil', 'Partner', 'Consulting', '15 rue Sainte-Catherine', '33000', 'Bordeaux', 24],
        'hotel-cap-azur' => ['Hôtel Cap Azur', 'Prospect', 'Hospitality', '112 promenade des Anglais', '06000', 'Nice', 45],
        'imprimerie-artois' => ["Imprimerie de l'Artois", 'Customer', 'Media', '6 rue des Poissonniers', '62000', 'Arras', 21],
        'energies-sillon' => ['Énergies du Sillon', 'Prospect', 'Energy', '30 boulevard de la Liberté', '35000', 'Rennes', 60],
        'jardins-partages' => ['Association Les Jardins Partagés', 'Customer', 'Not For Profit', '41 rue Gambetta', '59000', 'Lille', 6],
        'laboratoires-rhenans' => ['Laboratoires Rhénans', 'Prospect', 'Biotechnology', '2 rue du Parc Technologique', '67400', 'Illkirch-Graffenstaden', 210],
        'garage-moreau' => ['Garage Moreau Automobiles', 'Customer', 'Retail', '88 avenue de Grammont', '37000', 'Tours', 15],
        'lingua-formation' => ['Lingua Formation', 'Customer', 'Education', '9 rue de la Loge', '34000', 'Montpellier', 28],
        'techsud' => ['TechSud Informatique', 'Reseller', 'Technology', '54 allée Jean Jaurès', '31000', 'Toulouse', 52],
        'mutuelle-atlantique' => ['Mutuelle Atlantique Prévoyance', 'Prospect', 'Insurance', '17 quai Duperré', '17000', 'La Rochelle', 95],
        'menuiserie-perrin' => ['Menuiserie Perrin', 'Customer', 'Construction', '5 impasse des Artisans', '74000', 'Annecy', 11],
        'reseaux-fibres-est' => ["Réseaux & Fibres de l'Est", 'Partner', 'Telecommunications', '23 rue Serpenoise', '57000', 'Metz', 38],
        'maison-roux' => ['Maison Roux Boulangers', 'Customer', 'Retail', '14 place de Jaude', '63000', 'Clermont-Ferrand', 26],
        'studio-pixel' => ['Studio Pixel Production', 'Prospect', 'Entertainment', '37 rue Oberkampf', '75011', 'Paris', 14],
    ];

    public function name(): string
    {
        return 'core/accounts';
    }

    public function dependsOn(): array
    {
        return [];
    }

    public function run(SeedContext $seed): void
    {
        $number = 0;
        foreach (self::ACCOUNTS as $key => [$name, $type, $industry, $street, $postcode, $town, $employees]) {
            $seed->upsert('Accounts', $key, [
                'name' => $name,
                'account_type' => $type,
                'industry' => $industry,
                'employees' => (string) $employees,
                'phone_office' => Fr::phone(++$number * 100),
                'website' => Fr::website($name),
                'billing_address_street' => $street,
                'billing_address_postalcode' => $postcode,
                'billing_address_city' => $town,
                'billing_address_country' => 'France',
            ]);
        }
    }
}
