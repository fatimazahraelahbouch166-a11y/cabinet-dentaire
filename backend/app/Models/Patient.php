<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'telephone',
        'date_naissance',
        'sexe',
        'adresse',
        'ville',
        'code_postal',
        'numero_securite_sociale',
        'mutuelle',
        'numero_mutuelle',
        'contact_urgence_nom',
        'contact_urgence_tel',
        'is_archived',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'is_archived'    => 'boolean',
    ];

    // ── Relations ────────────────────────────────────────────────────────────
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rendezVous()
    {
        return $this->hasMany(RendezVous::class);
    }

    public function dossierMedical()
    {
        return $this->hasOne(DossierMedical::class);
    }

    public function factures()
    {
        return $this->hasMany(Facture::class);
    }

    public function ordonnances()
    {
        return $this->hasMany(Ordonnance::class);
    }

    public function plansTraitement()
    {
        return $this->hasMany(PlanTraitement::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────
    public function scopeActifs($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->whereHas('user', function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%");
        })->orWhere('telephone', 'like', "%{$term}%");
    }
}