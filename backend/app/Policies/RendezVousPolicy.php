<?php

namespace App\Policies;

use App\Models\RendezVous;
use App\Models\User;

class RendezVousPolicy
{
    /**
     * Un patient ne peut voir que ses propres RDV.
     */
    public function view(User $user, RendezVous $rdv): bool
    {
        if ($user->isPatient()) {
            return $user->patient?->id === $rdv->patient_id;
        }
        // Secrétaire et dentiste voient tous les RDV
        return in_array($user->role, ['secretaire', 'dentiste']);
    }

    /**
     * Un patient peut créer un RDV pour lui-même.
     * Secrétaire peut créer pour tout patient.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['patient', 'secretaire']);
    }

    /**
     * La secrétaire peut modifier n'importe quel RDV.
     * Le patient peut annuler seulement le sien.
     */
    public function update(User $user, RendezVous $rdv): bool
    {
        if ($user->isSecretaire()) return true;
        if ($user->isPatient()) {
            return $user->patient?->id === $rdv->patient_id;
        }
        return false;
    }

    /**
     * Secrétaire peut annuler n'importe quel RDV.
     * Patient peut annuler le sien (règle 24h vérifiée dans le controller).
     */
    public function delete(User $user, RendezVous $rdv): bool
    {
        if ($user->isSecretaire()) return true;
        if ($user->isPatient()) {
            return $user->patient?->id === $rdv->patient_id;
        }
        return false;
    }
}