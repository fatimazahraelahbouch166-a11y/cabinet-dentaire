<?php

namespace App\Policies;

use App\Models\Facture;
use App\Models\User;

class FacturePolicy
{
    public function view(User $user, Facture $facture): bool
    {
        if ($user->isPatient()) {
            return $user->patient?->id === $facture->patient_id;
        }
        return in_array($user->role, ['secretaire', 'dentiste']);
    }

    public function create(User $user): bool
    {
        return $user->isSecretaire();
    }

    public function update(User $user, Facture $facture): bool
    {
        return $user->isSecretaire();
    }
}