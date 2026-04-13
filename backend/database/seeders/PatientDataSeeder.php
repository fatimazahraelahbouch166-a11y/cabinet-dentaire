<?php

namespace Database\Seeders;

use App\Models\CatalogueActe;
use App\Models\Consultation;
use App\Models\ConsultationActe;
use App\Models\DossierMedical;
use App\Models\Facture;
use App\Models\LigneFacture;
use App\Models\Ordonnance;
use App\Models\Patient;
use App\Models\RendezVous;
use App\Models\User;
use Illuminate\Database\Seeder;

class PatientDataSeeder extends Seeder
{
    public function run(): void
    {
        $dentiste   = User::where('email', 'dentiste@cabinet.com')->first();
        $secretaire = User::where('email', 'secretaire@cabinet.com')->first();
        $patient    = Patient::whereHas('user', fn($q) => $q->where('email', 'jean@test.com'))->first();

        if (!$dentiste || !$secretaire || !$patient) {
            $this->command->warn('Utilisateurs requis introuvables. Lancez UserSeeder d\'abord.');
            return;
        }

        $dossier = DossierMedical::where('patient_id', $patient->id)->first();

        // ── RDV passé terminé ─────────────────────────────────────────────────
        $rdvPasse = RendezVous::firstOrCreate(
            [
                'patient_id'  => $patient->id,
                'dentiste_id' => $dentiste->id,
                'date_heure'  => now()->subDays(10)->setHour(10)->setMinute(0),
            ],
            [
                'duree_minutes' => 45,
                'motif'         => 'Douleur molaire inférieure gauche',
                'statut'        => 'termine',
                'is_urgence'    => false,
            ]
        );

        // ── Consultation liée au RDV passé ────────────────────────────────────
        $consultation = Consultation::firstOrCreate(
            ['rendez_vous_id' => $rdvPasse->id],
            [
                'dossier_medical_id' => $dossier->id,
                'dentiste_id'        => $dentiste->id,
                'date_consultation'  => now()->subDays(10)->toDateString(),
                'motif'              => 'Douleur molaire inférieure gauche',
                'diagnostic'         => 'Carie profonde sur 36 avec pulpite réversible.',
                'notes'              => 'Obturation réalisée sous anesthésie locale. Contrôle à 3 mois.',
                'observations'       => 'Patient coopératif malgré l\'anxiété.',
            ]
        );

        // Acte lié à la consultation
        $acteObturation = CatalogueActe::where('code', 'S02')->first();
        $acteConsult    = CatalogueActe::where('code', 'C01')->first();

        if ($acteObturation && !$consultation->actes()->count()) {
            ConsultationActe::create([
                'consultation_id'   => $consultation->id,
                'catalogue_acte_id' => $acteConsult->id,
                'quantite'          => 1,
                'prix_unitaire'     => $acteConsult->tarif,
                'dent'              => null,
            ]);
            ConsultationActe::create([
                'consultation_id'   => $consultation->id,
                'catalogue_acte_id' => $acteObturation->id,
                'quantite'          => 1,
                'prix_unitaire'     => $acteObturation->tarif,
                'dent'              => '36',
                'notes'             => 'Obturation composite 2 faces DO',
            ]);
        }

        // ── Ordonnance liée à la consultation ────────────────────────────────
        Ordonnance::firstOrCreate(
            ['consultation_id' => $consultation->id],
            [
                'patient_id'      => $patient->id,
                'dentiste_id'     => $dentiste->id,
                'date_ordonnance' => now()->subDays(10)->toDateString(),
                'contenu'         => "Ibuprofène 400mg — 1 comprimé toutes les 8h pendant 3 jours (à prendre au cours des repas)\n\nAmoxicilline 1g — NE PAS UTILISER (allergie pénicilline) → Clindamycine 300mg — 1 gélule 3 fois/jour pendant 7 jours\n\nChlorhexidine solution buccale 0,12% — bains de bouche matin et soir pendant 10 jours",
                'notes'           => 'Éviter AINS si douleurs gastriques. Contrôle dans 3 mois.',
                'is_archived'     => false,
            ]
        );

        // ── Facture pour la consultation passée ──────────────────────────────
        $facture = Facture::firstOrCreate(
            ['consultation_id' => $consultation->id],
            [
                'patient_id'      => $patient->id,
                'secretaire_id'   => $secretaire->id,
                'numero_facture'  => Facture::genererNumero(),
                'date_facture'    => now()->subDays(10)->toDateString(),
                'montant_total'   => 80.00, // C01 + S02
                'montant_paye'    => 80.00,
                'montant_mutuelle'=> 0,
                'statut'          => 'paye',
                'notes'           => 'Payé par carte le jour même.',
            ]
        );

        if (!$facture->lignes()->count()) {
            LigneFacture::create([
                'facture_id'        => $facture->id,
                'catalogue_acte_id' => $acteConsult?->id,
                'libelle'           => 'Consultation et examen',
                'quantite'          => 1,
                'prix_unitaire'     => 25.00,
                'total'             => 25.00,
            ]);
            LigneFacture::create([
                'facture_id'        => $facture->id,
                'catalogue_acte_id' => $acteObturation?->id,
                'libelle'           => 'Obturation composite 2 faces — dent 36',
                'quantite'          => 1,
                'prix_unitaire'     => 55.00,
                'total'             => 55.00,
            ]);
        }

        // ── RDV à venir ───────────────────────────────────────────────────────
        RendezVous::firstOrCreate(
            [
                'patient_id'  => $patient->id,
                'dentiste_id' => $dentiste->id,
                'date_heure'  => now()->addDays(5)->setHour(14)->setMinute(30),
            ],
            [
                'duree_minutes' => 30,
                'motif'         => 'Contrôle post-obturation',
                'statut'        => 'confirme',
                'is_urgence'    => false,
                'notes'         => 'Contrôle de la 36 suite à obturation du ' . now()->subDays(10)->format('d/m/Y'),
            ]
        );

        // ── RDV d'aujourd'hui (pour tester le dashboard secrétaire) ───────────
        RendezVous::firstOrCreate(
            [
                'patient_id'  => $patient->id,
                'dentiste_id' => $dentiste->id,
                'date_heure'  => now()->setHour(9)->setMinute(0)->setSecond(0),
            ],
            [
                'duree_minutes' => 30,
                'motif'         => 'Détartrage annuel',
                'statut'        => 'confirme',
                'is_urgence'    => false,
            ]
        );

        $this->command->info('Données de test patient créées avec succès.');
    }
}