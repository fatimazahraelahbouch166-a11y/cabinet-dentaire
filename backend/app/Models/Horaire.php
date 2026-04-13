<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Horaire extends Model
{
    protected $fillable = [
        'dentiste_id',
        'jour_semaine',
        'heure_debut',
        'heure_fin',
        'is_active',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'jour_semaine' => 'integer',
    ];

    public function dentiste() { return $this->belongsTo(User::class, 'dentiste_id'); }
}