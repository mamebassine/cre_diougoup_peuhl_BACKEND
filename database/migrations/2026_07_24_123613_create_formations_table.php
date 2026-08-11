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
        Schema::create('formations', function (Blueprint $table) {

            $table->id();

            // Informations générales
            $table->string('nom')->unique();

            $table->text('resume');

            $table->longText('description');

            // Informations affichées sur la page
            $table->string('duree');

            $table->string('diplome');

            $table->string('lieu');

            // Objectifs de la formation
            $table->json('objectifs')->nullable();

            // Emoji ou icône
            $table->string('icone')->nullable();
            
            $table->integer('capacite')->nullable();

            // Formation active ou non
            $table->boolean('is_active')->default(true);

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('formations');
    }
};