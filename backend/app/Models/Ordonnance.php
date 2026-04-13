<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ordonnance extends Model
{
    protected $fillable = [
        'patient_id',
        'dentiste_id',
        'consultation_id',
        'date_ordonnance',
        'contenu',
        'notes',
        'is_archived',
    ];

    protected $casts = [
        'date_ordonnance' => 'date',
        'is_archived'     => 'boolean',
    ];

    public function patient()     { return $this->belongsTo(Patient::class); }
    public function dentiste()    { return $this->belongsTo(User::class, 'dentiste_id'); }
    public function consultation(){ return $this->belongsTo(Consultation::class); }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }
}