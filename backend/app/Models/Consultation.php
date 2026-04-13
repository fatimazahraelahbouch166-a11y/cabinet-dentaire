<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    protected $fillable = [
        'dossier_medical_id',
        'rendez_vous_id',
        'dentiste_id',
        'date_consultation',
        'motif',
        'diagnostic',
        'notes',
        'observations',
    ];

    protected $casts = [
        'date_consultation' => 'date',
    ];

    public function dossierMedical()  { return $this->belongsTo(DossierMedical::class, 'dossier_medical_id'); }
    public function rendezVous()      { return $this->belongsTo(RendezVous::class, 'rendez_vous_id'); }
    public function dentiste()        { return $this->belongsTo(User::class, 'dentiste_id'); }
    public function actes()           { return $this->hasMany(ConsultationActe::class); }
    public function ordonnances()     { return $this->hasMany(Ordonnance::class); }
    public function factures()        { return $this->hasMany(Facture::class); }
}