<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_active'         => 'boolean',
    ];

    // ── Helpers de rôle ──────────────────────────────────────────────────────
    public function isPatient(): bool    { return $this->role === 'patient'; }
    public function isSecretaire(): bool { return $this->role === 'secretaire'; }
    public function isDentiste(): bool   { return $this->role === 'dentiste'; }

    // ── Relations ────────────────────────────────────────────────────────────
    public function patient()
    {
        return $this->hasOne(Patient::class);
    }

    public function rendezVousCommeDentiste()
    {
        return $this->hasMany(RendezVous::class, 'dentiste_id');
    }

    public function horaires()
    {
        return $this->hasMany(Horaire::class, 'dentiste_id');
    }

    public function conges()
    {
        return $this->hasMany(Conge::class, 'dentiste_id');
    }

    public function ordonnances()
    {
        return $this->hasMany(Ordonnance::class, 'dentiste_id');
    }

    public function consultations()
    {
        return $this->hasMany(Consultation::class, 'dentiste_id');
    }

    public function facturesCrees()
    {
        return $this->hasMany(Facture::class, 'secretaire_id');
    }
}