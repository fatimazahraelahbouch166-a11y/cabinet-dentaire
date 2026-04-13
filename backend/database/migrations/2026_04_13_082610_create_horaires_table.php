<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dentiste_id')->constrained('users')->onDelete('cascade');
            $table->tinyInteger('jour_semaine'); // 1=lundi ... 7=dimanche
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horaires');
    }
};