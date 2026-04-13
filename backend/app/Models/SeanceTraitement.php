<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeanceTraitement extends Model
{
    protected $table = 'seances_traitement';

    protected $fillable = [
        'plan_traitement_id',
        'consultation_id',
        'numero_seance',
        'objectif',
        'statut',
        'notes',
    ];

    protected $casts = [
        'numero_seance' => 'integer',
    ];

    public function planTraitement() { return $this->belongsTo(PlanTraitement::class, 'plan_traitement_id'); }
    public function consultation()   { return $this->belongsTo(Consultation::class); }
}