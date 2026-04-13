<?php

namespace App\Policies;

use App\Models\Ordonnance;
use App\Models\User;

class OrdonnancePolicy
{
    public function view(User $user, Ordonnance $ordonnance): bool
    {
        if ($user->isPatient()) {
            return $user->patient?->id === $ordonnance->patient_id;
        }
        return $user->isDentiste() || $user->isSecretaire();
    }

    public function create(User $user): bool
    {
        return $user->isDentiste();
    }

    public function update(User $user, Ordonnance $ordonnance): bool
    {
        return $user->isDentiste() && $user->id === $ordonnance->dentiste_id;
    }
}