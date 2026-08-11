<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inscriptions', function (Blueprint $table) {

            $table->id();

            // L'apprenant concerné
            $table->foreignId('apprenant_id')
                ->constrained()
                ->cascadeOnDelete();

            // Formation choisie
            $table->foreignId('formation_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unique(['apprenant_id', 'formation_id']);    

            // Horaire choisi
            $table->string('horaire');

            // Date de la demande
            $table->date('date_inscription')->useCurrent();
            
            // Validation de la demande
            $table->enum('statut',[
                'En attente',
                'Valide',
                'Refuse'
            ])->default('En attente');

            // Suivi de la formation
            $table->enum('etat_formation',[
                'Non commencée',
                'En cours',
                'Terminée',
                'Abandonnée'
            ])->default('Non commencée');

            // Admin ou gestionnaire ayant validé
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscriptions');
    }
};