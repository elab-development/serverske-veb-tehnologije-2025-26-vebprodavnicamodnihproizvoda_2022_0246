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
        Schema::create('categories', function (Blueprint $table) {
        $table->id(); // Automatski generisan primarni ključ (ID)
        $table->string('name')->unique(); // Naziv kategorije (npr. Muška kolekcija, Ženska odeća) - MORA biti jedinstven
        $table->string('description')->nullable(); // Opis kategorije - može biti prazan (nullable)
        $table->timestamps(); // Automatski kreira kolone created_at i updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
