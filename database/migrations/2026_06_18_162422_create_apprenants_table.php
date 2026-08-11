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
        Schema::create('apprenants', function (Blueprint $table) {
    $table->id();

    $table->foreignId('user_id')
          ->unique()
          ->constrained()
          ->cascadeOnDelete();

    $table->foreignId('created_by')
      ->nullable()
      ->constrained('users')
      ->nullOnDelete();

    $table->string('matricule')->unique();

    $table->date('date_naissance');

    $table->enum('sexe', [
        'Masculin',
        'Feminin'
    ]);

    $table->enum('situation_matrimoniale', [
        'Celibataire',
        'Marie',
        'Divorce',
        'Veuf'
    ]);

    $table->text('adresse');

    $table->string('telephone');
    $table->string('email')->nullable();

    $table->string('niveau_etude');

    $table->string('fonction')->nullable();

    $table->enum('niveau_informatique', [
        'Debutant',
        'Intermediaire',
        'Avance'
    ]);

    $table->string('photo')->nullable();

    $table->longText('signature')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apprenants');
    }
};
