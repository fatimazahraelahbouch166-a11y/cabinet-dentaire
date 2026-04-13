<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rendez_vous', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('dentiste_id')->constrained('users')->onDelete('cascade');
            $table->dateTime('date_heure');
            $table->integer('duree_minutes')->default(30);
            $table->string('motif');
            $table->enum('statut', [
                'en_attente',
                'confirme',
                'arrive',
                'en_cours',
                'termine',
                'annule',
                'absent'
            ])->default('en_attente');
            $table->boolean('is_urgence')->default(false);
            $table->text('notes')->nullable();
            $table->string('annule_par')->nullable(); // 'patient' | 'secretaire' | 'dentiste'
            $table->timestamp('annule_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rendez_vous');
    }
};