<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seances_traitement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_traitement_id')->constrained('plans_traitement')->onDelete('cascade');
            $table->foreignId('consultation_id')->nullable()->constrained('consultations')->onDelete('set null');
            $table->integer('numero_seance');
            $table->string('objectif');
            $table->enum('statut', ['planifiee', 'realisee', 'annulee'])->default('planifiee');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seances_traitement');
    }
};