<?php namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ConsultationActe extends Model {
    protected $table = 'consultation_actes';
    protected $fillable = ['consultation_id','catalogue_acte_id','quantite','prix_unitaire','dent','notes'];
    protected $casts = ['prix_unitaire' => 'decimal:2'];
    public function consultation() { return $this->belongsTo(Consultation::class); }
    public function catalogueActe() { return $this->belongsTo(CatalogueActe::class, 'catalogue_acte_id'); }
}
