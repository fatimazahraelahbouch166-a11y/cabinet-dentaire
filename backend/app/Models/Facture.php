<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Facture extends Model
{
    protected $fillable = [
        'patient_id',
        'secretaire_id',
        'consultation_id',
        'numero_facture',
        'date_facture',
        'montant_total',
        'montant_paye',
        'montant_mutuelle',
        'statut',
        'notes',
    ];

    protected $casts = [
        'date_facture'     => 'date',
        'montant_total'    => 'decimal:2',
        'montant_paye'     => 'decimal:2',
        'montant_mutuelle' => 'decimal:2',
    ];

    public function patient()     { return $this->belongsTo(Patient::class); }
    public function secretaire()  { return $this->belongsTo(User::class, 'secretaire_id'); }
    public function consultation(){ return $this->belongsTo(Consultation::class); }
    public function lignes()      { return $this->hasMany(LigneFacture::class); }
    public function paiements()   { return $this->hasMany(Paiement::class); }

    public function getMontantRestantAttribute(): float
    {
        return (float) $this->montant_total - (float) $this->montant_paye - (float) $this->montant_mutuelle;
    }

    public function recalculerStatut(): void
    {
        $restant = $this->montant_restant;
        if ($restant <= 0) {
            $this->statut = 'paye';
        } elseif ((float) $this->montant_paye > 0 || (float) $this->montant_mutuelle > 0) {
            $this->statut = 'partiellement_paye';
        } else {
            $this->statut = 'en_attente';
        }
        $this->save();
    }

    public static function genererNumero(): string
    {
        $annee  = now()->format('Y');
        $mois   = now()->format('m');
        $dernier = static::whereYear('date_facture', $annee)
            ->whereMonth('date_facture', $mois)
            ->count();
        return sprintf('FAC-%s%s-%04d', $annee, $mois, $dernier + 1);
    }
}