<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RendezVous extends Model
{
    use HasFactory;

    protected $table = 'rendez_vous';

    protected $fillable = [
        'patient_id',
        'dentiste_id',
        'date_heure',
        'duree_minutes',
        'motif',
        'statut',
        'is_urgence',
        'notes',
        'annule_par',
        'annule_at',
    ];

    protected $casts = [
        'date_heure'  => 'datetime',
        'annule_at'   => 'datetime',
        'is_urgence'  => 'boolean',
        'duree_minutes' => 'integer',
    ];

    // ── Relations ────────────────────────────────────────────────────────────
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function dentiste()
    {
        return $this->belongsTo(User::class, 'dentiste_id');
    }

    public function consultation()
    {
        return $this->hasOne(Consultation::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    public function peutEtreAnnuleParPatient(): bool
    {
        if (!in_array($this->statut, ['en_attente', 'confirme'])) {
            return false;
        }
        return $this->date_heure->diffInHours(now()) >= 24;
    }

    // ── Scopes ───────────────────────────────────────────────────────────────
    public function scopeDuJour($query, $date = null)
    {
        $date = $date ?? today();
        return $query->whereDate('date_heure', $date);
    }

    public function scopeActifs($query)
    {
        return $query->whereNotIn('statut', ['annule', 'absent']);
    }
}