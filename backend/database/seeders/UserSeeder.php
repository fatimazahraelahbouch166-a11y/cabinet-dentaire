<?php

namespace Database\Seeders;

use App\Models\DossierMedical;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ── Dentiste ──────────────────────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'dentiste@cabinet.com'],
            [
                'name'      => 'Dr. Martin Lefebvre',
                'password'  => Hash::make('dentiste123'),
                'role'      => 'dentiste',
                'is_active' => true,
            ]
        );

        // ── Secrétaire ────────────────────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'secretaire@cabinet.com'],
            [
                'name'      => 'Sophie Renard',
                'password'  => Hash::make('secretaire123'),
                'role'      => 'secretaire',
                'is_active' => true,
            ]
        );

        // ── Patient ───────────────────────────────────────────────────────────
        $patientUser = User::firstOrCreate(
            ['email' => 'jean@test.com'],
            [
                'name'      => 'Jean Dupont',
                'password'  => Hash::make('password123'),
                'role'      => 'patient',
                'is_active' => true,
            ]
        );

        $patient = Patient::firstOrCreate(
            ['user_id' => $patientUser->id],
            [
                'telephone'      => '0612345678',
                'date_naissance' => '1985-03-15',
                'sexe'           => 'M',
                'adresse'        => '12 rue de la Paix',
                'ville'          => 'Paris',
                'code_postal'    => '75001',
                'mutuelle'       => 'MGEN',
                'numero_mutuelle'=> 'MG123456',
            ]
        );

        DossierMedical::firstOrCreate(
            ['patient_id' => $patient->id],
            [
                'groupe_sanguin'        => 'A+',
                'antecedents_medicaux'  => 'Hypertension légère traitée depuis 2018.',
                'antecedents_dentaires' => 'Extraction molaire en 2020. Couronne sur 36.',
                'allergies'             => 'Pénicilline (allergie documentée).',
                'medicaments_en_cours'  => 'Ramipril 5mg/jour.',
                'notes_generales'       => 'Patient anxieux. Prévoir anesthésie topique.',
            ]
        );

        // Patient supplémentaire pour les tests
        $patientUser2 = User::firstOrCreate(
            ['email' => 'marie@test.com'],
            [
                'name'      => 'Marie Dubois',
                'password'  => Hash::make('password123'),
                'role'      => 'patient',
                'is_active' => true,
            ]
        );

        $patient2 = Patient::firstOrCreate(
            ['user_id' => $patientUser2->id],
            [
                'telephone'      => '0698765432',
                'date_naissance' => '1992-07-22',
                'sexe'           => 'F',
                'ville'          => 'Lyon',
                'code_postal'    => '69001',
            ]
        );

        DossierMedical::firstOrCreate(
            ['patient_id' => $patient2->id],
            [
                'groupe_sanguin' => 'O-',
                'allergies'      => 'Aucune allergie connue.',
            ]
        );
    }
}