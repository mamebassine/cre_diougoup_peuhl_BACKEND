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

    $table->enum('type_document', [
        'Diplome',
        'Attestation'
    ]);

    $table->string('numero_document')->unique();

    $table->date('date_delivrance');
    $table->string('annee_academique')->nullable();

    $table->string('fichier_pdf')->nullable();


    $table->enum('statut', [
        'Disponible',
        'Retire'
    ])->default('Disponible');

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
