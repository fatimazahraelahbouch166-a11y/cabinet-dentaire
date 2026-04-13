<?php

namespace Database\Seeders;

use App\Models\CatalogueActe;
use Illuminate\Database\Seeder;

class CatalogueActeSeeder extends Seeder
{
    public function run(): void
    {
        $actes = [
            // Consultations
            ['code' => 'C01',  'libelle' => 'Consultation et examen',            'categorie' => 'Consultation',    'tarif' => 25.00,  'remboursable' => true],
            ['code' => 'C02',  'libelle' => 'Consultation d\'urgence',            'categorie' => 'Consultation',    'tarif' => 35.00,  'remboursable' => true],
            ['code' => 'C03',  'libelle' => 'Bilan bucco-dentaire',               'categorie' => 'Consultation',    'tarif' => 45.00,  'remboursable' => true],

            // Radiologie
            ['code' => 'R01',  'libelle' => 'Radiographie rétro-alvéolaire',      'categorie' => 'Radiologie',      'tarif' => 15.00,  'remboursable' => true],
            ['code' => 'R02',  'libelle' => 'Panoramique dentaire',               'categorie' => 'Radiologie',      'tarif' => 40.00,  'remboursable' => true],
            ['code' => 'R03',  'libelle' => 'Céphalométrie',                      'categorie' => 'Radiologie',      'tarif' => 50.00,  'remboursable' => false],

            // Soins conservateurs
            ['code' => 'S01',  'libelle' => 'Obturation composite 1 face',        'categorie' => 'Soins conservateurs', 'tarif' => 40.00,  'remboursable' => true],
            ['code' => 'S02',  'libelle' => 'Obturation composite 2 faces',       'categorie' => 'Soins conservateurs', 'tarif' => 55.00,  'remboursable' => true],
            ['code' => 'S03',  'libelle' => 'Obturation composite 3 faces',       'categorie' => 'Soins conservateurs', 'tarif' => 70.00,  'remboursable' => true],
            ['code' => 'S04',  'libelle' => 'Traitement carie profonde',          'categorie' => 'Soins conservateurs', 'tarif' => 80.00,  'remboursable' => true],

            // Endodontie
            ['code' => 'E01',  'libelle' => 'Traitement endodontique monoradiculé', 'categorie' => 'Endodontie',    'tarif' => 120.00, 'remboursable' => true],
            ['code' => 'E02',  'libelle' => 'Traitement endodontique pluriradiculé','categorie' => 'Endodontie',    'tarif' => 170.00, 'remboursable' => true],
            ['code' => 'E03',  'libelle' => 'Retraitement endodontique',           'categorie' => 'Endodontie',    'tarif' => 200.00, 'remboursable' => false],

            // Chirurgie
            ['code' => 'CH01', 'libelle' => 'Extraction dentaire simple',         'categorie' => 'Chirurgie',       'tarif' => 45.00,  'remboursable' => true],
            ['code' => 'CH02', 'libelle' => 'Extraction dent de sagesse',         'categorie' => 'Chirurgie',       'tarif' => 120.00, 'remboursable' => true],
            ['code' => 'CH03', 'libelle' => 'Alvéolite — traitement',             'categorie' => 'Chirurgie',       'tarif' => 30.00,  'remboursable' => true],

            // Prothèse
            ['code' => 'P01',  'libelle' => 'Couronne céramo-métallique',         'categorie' => 'Prothèse',        'tarif' => 450.00, 'remboursable' => true],
            ['code' => 'P02',  'libelle' => 'Couronne tout céramique',            'categorie' => 'Prothèse',        'tarif' => 600.00, 'remboursable' => false],
            ['code' => 'P03',  'libelle' => 'Inlay-onlay',                        'categorie' => 'Prothèse',        'tarif' => 350.00, 'remboursable' => false],
            ['code' => 'P04',  'libelle' => 'Prothèse amovible complète',         'categorie' => 'Prothèse',        'tarif' => 900.00, 'remboursable' => true],
            ['code' => 'P05',  'libelle' => 'Prothèse partielle amovible',        'categorie' => 'Prothèse',        'tarif' => 650.00, 'remboursable' => true],

            // Implantologie
            ['code' => 'I01',  'libelle' => 'Implant dentaire (pose)',            'categorie' => 'Implantologie',   'tarif' => 1200.00,'remboursable' => false],
            ['code' => 'I02',  'libelle' => 'Couronne sur implant',               'categorie' => 'Implantologie',   'tarif' => 700.00, 'remboursable' => false],

            // Parodontologie
            ['code' => 'PA01', 'libelle' => 'Détartrage supragingival complet',   'categorie' => 'Parodontologie',  'tarif' => 65.00,  'remboursable' => true],
            ['code' => 'PA02', 'libelle' => 'Surfaçage radiculaire par sextant',  'categorie' => 'Parodontologie',  'tarif' => 80.00,  'remboursable' => true],

            // Esthétique
            ['code' => 'ES01', 'libelle' => 'Blanchiment dentaire (gouttières)',  'categorie' => 'Esthétique',      'tarif' => 250.00, 'remboursable' => false],
            ['code' => 'ES02', 'libelle' => 'Facette céramique',                  'categorie' => 'Esthétique',      'tarif' => 500.00, 'remboursable' => false],
        ];

        foreach ($actes as $acte) {
            CatalogueActe::firstOrCreate(['code' => $acte['code']], array_merge($acte, ['is_active' => true]));
        }

        $this->command->info('Catalogue : ' . count($actes) . ' actes insérés.');
    }
}