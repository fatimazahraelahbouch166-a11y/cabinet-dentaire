<?php

namespace App\Policies;

use App\Models\DossierMedical;
use App\Models\User;

class DossierMedicalPolicy
{
    /**
     * Lecture : patient (son propre dossier), dentiste, secrétaire.
     */
    public function view(User $user, DossierMedical $dossier): bool
    {
        if ($user->isPatient()) {
            return $user->patient?->id === $dossier->patient_id;
        }
        return in_array($user->role, ['dentiste', 'secretaire']);
    }

    /**
     * Modification : dentiste uniquement.
     */
    public function update(User $user, DossierMedical $dossier): bool
    {
        return $user->isDentiste();
    }

    /**
     * Création : dentiste et secrétaire.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['dentiste', 'secretaire']);
    }
}