<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanTraitement extends Model
{
    protected $table = 'plans_traitement';

    protected $fillable = [
        'patient_id',
        'dentiste_id',
        'titre',
        'description',
        'statut',
        'date_debut',
        'date_fin_prevue',
        'cout_total_estime',
    ];

    protected $casts = [
        'date_debut'        => 'date',
        'date_fin_prevue'   => 'date',
        'cout_total_estime' => 'decimal:2',
    ];

    public function patient()  { return $this->belongsTo(Patient::class); }
    public function dentiste() { return $this->belongsTo(User::class, 'dentiste_id'); }
    public function seances()  { return $this->hasMany(SeanceTraitement::class, 'plan_traitement_id'); }
}