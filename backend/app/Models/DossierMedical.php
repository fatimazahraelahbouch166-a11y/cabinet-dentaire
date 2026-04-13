<?php
// ─── DossierMedical ──────────────────────────────────────────────────────────
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DossierMedical extends Model
{
    protected $table = 'dossiers_medicaux';

    protected $fillable = [
        'patient_id',
        'groupe_sanguin',
        'antecedents_medicaux',
        'antecedents_dentaires',
        'allergies',
        'medicaments_en_cours',
        'notes_generales',
    ];

    public function patient()    { return $this->belongsTo(Patient::class); }
    public function consultations() { return $this->hasMany(Consultation::class, 'dossier_medical_id'); }
}