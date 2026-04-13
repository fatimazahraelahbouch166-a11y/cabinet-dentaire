<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conge extends Model
{
    protected $fillable = [
        'dentiste_id',
        'date_debut',
        'date_fin',
        'motif',
        'type',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin'   => 'date',
    ];

    public function dentiste() { return $this->belongsTo(User::class, 'dentiste_id'); }
}