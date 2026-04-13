<?php

namespace App\Providers;

use App\Models\DossierMedical;
use App\Models\Facture;
use App\Models\Ordonnance;
use App\Models\RendezVous;
use App\Policies\DossierMedicalPolicy;
use App\Policies\FacturePolicy;
use App\Policies\OrdonnancePolicy;
use App\Policies\RendezVousPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        RendezVous::class     => RendezVousPolicy::class,
        DossierMedical::class => DossierMedicalPolicy::class,
        Ordonnance::class     => OrdonnancePolicy::class,
        Facture::class        => FacturePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}