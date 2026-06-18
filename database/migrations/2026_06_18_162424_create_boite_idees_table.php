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
        Schema::create('boite_idees', function (Blueprint $table) {
    $table->id();

    $table->foreignId('apprenant_id')
          ->constrained()
          ->cascadeOnDelete();

    $table->enum('type_message', [
        'Suggestion',
        'Reclamation',
        'Question',
        'Demande'
    ]);

    $table->string('objet');

    $table->text('message');

    $table->boolean('is_read')->default(false);

    $table->enum('statut', [
        'Nouveau',
        'En cours',
        'Traite'
    ])->default('Nouveau');

    $table->text('reponse')->nullable();

    $table->timestamp('date_reponse')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boite_idees');
    }
};
