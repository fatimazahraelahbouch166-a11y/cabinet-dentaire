<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LigneFacture extends Model
{
    protected $table = 'lignes_facture';

    protected $fillable = [
        'facture_id',
        'catalogue_acte_id',
        'libelle',
        'quantite',
        'prix_unitaire',
        'total',
    ];

    protected $casts = [
        'prix_unitaire' => 'decimal:2',
        'total'         => 'decimal:2',
        'quantite'      => 'integer',
    ];

    public function facture()      { return $this->belongsTo(Facture::class); }
    public function catalogueActe(){ return $this->belongsTo(CatalogueActe::class, 'catalogue_acte_id'); }
}