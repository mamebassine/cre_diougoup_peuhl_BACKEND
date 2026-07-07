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
        Schema::create('diplome_attestations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('apprenant_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // Type de document demandé
            $table->enum('type_document', [
                'Diplome',
                'Attestation'
            ]);

            // Généré uniquement après validation par l'administration
            $table->string('numero_document')->unique()->nullable();

            // Informations sur la formation
            $table->string('formation')->nullable();
            $table->string('module_suivi')->nullable();
            $table->string('annee_academique')->nullable();

            // Renseignée lors de la validation
            $table->date('date_delivrance')->nullable();

            // PDF généré ou téléversé par l'administration
            $table->string('fichier_pdf')->nullable();

            // Suivi de la demande
            $table->enum('statut', [
                'en_attente',
                'valide',
                'refuse',
                'retire'
            ])->default('en_attente');

            // Date de retrait par l'apprenant
            $table->date('date_retrait')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diplome_attestations');
    }
};