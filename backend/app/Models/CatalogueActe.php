<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogueActe extends Model
{
    protected $table = 'catalogue_actes';

    protected $fillable = [
        'code',
        'libelle',
        'categorie',
        'tarif',
        'remboursable',
        'description',
        'is_active',
    ];

    protected $casts = [
        'tarif'        => 'decimal:2',
        'remboursable' => 'boolean',
        'is_active'    => 'boolean',
    ];

    public function consultationActes()
    {
        return $this->hasMany(ConsultationActe::class, 'catalogue_acte_id');
    }

    public function lignesFacture()
    {
        return $this->hasMany(LigneFacture::class, 'catalogue_acte_id');
    }

    public function scopeActifs($query)
    {
        return $query->where('is_active', true);
    }
}