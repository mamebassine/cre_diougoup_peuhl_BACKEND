
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

            // Relation avec l'apprenant
            $table->foreignId('apprenant_id')
                  ->constrained('apprenants')
                  ->cascadeOnDelete();

            // Type de document demandé
            $table->enum('type_document', [
                'Diplome',
                'Attestation'
            ]);

            // Informations personnelles de l'apprenant
            $table->string('prenom');
            $table->string('nom');
            $table->date('date_naissance');
            $table->string('lieu_naissance');
            $table->string('telephone', 20);
            $table->string('adresse');
            $table->string('fonction')->nullable();

            // Informations sur la formation
            $table->string('formation')->nullable();
            $table->string('duree_formation')->nullable();
            $table->string('periode_formation')->nullable();
            $table->string('module_suivi')->nullable();
            $table->string('annee_academique')->nullable();

            // Informations sur la demande
            $table->string('lieu_demande')->nullable();
            $table->date('date_demande');

            // Lettre de demande générée automatiquement en PDF
            $table->string('fichier_demande')->nullable();

            // Numéro du document officiel
            $table->string('numero_document')->unique()->nullable();

            // Date de validation et de délivrance
            $table->date('date_validation')->nullable();
            $table->date('date_delivrance')->nullable();

            // PDF officiel généré ou téléversé par l'administration
            $table->string('fichier_pdf')->nullable();

            // Suivi de la demande
            $table->enum('statut', [
                'en_attente',
                'valide',
                'refuse',
                'retire'
            ])->default('en_attente');

            // Motif du refus éventuel
            $table->text('motif_refus')->nullable();

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
