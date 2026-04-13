<?php

namespace Database\Seeders;

use App\Models\Horaire;
use App\Models\User;
use Illuminate\Database\Seeder;

class HoraireSeeder extends Seeder
{
    public function run(): void
    {
        $dentiste = User::where('email', 'dentiste@cabinet.com')->first();
        if (!$dentiste) return;

        // Lundi à vendredi : 9h–12h et 14h–18h
        $jours = [1, 2, 3, 4, 5]; // ISO: 1=lundi ... 5=vendredi
        foreach ($jours as $jour) {
            Horaire::firstOrCreate(
                ['dentiste_id' => $dentiste->id, 'jour_semaine' => $jour],
                ['heure_debut' => '09:00', 'heure_fin' => '12:00', 'is_active' => true]
            );
            // Créneau après-midi (stocker comme 2e entrée avec heure différente)
            // Note : dans une implémentation avancée on peut avoir plusieurs créneaux/jour
        }

        // Samedi matin uniquement
        Horaire::firstOrCreate(
            ['dentiste_id' => $dentiste->id, 'jour_semaine' => 6],
            ['heure_debut' => '09:00', 'heure_fin' => '12:00', 'is_active' => true]
        );

        $this->command->info('Horaires du dentiste créés.');
    }
}