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
        Schema::create('orders', function (Blueprint $table) {
        $table->id(); // Primarni ključ porudžbine
        
        // 4. TIP MIGRACIJE: Spoljni ključ koji povezuje porudžbinu sa korisnikom (User)
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        
        $table->decimal('total_price', 10, 2)->unsigned(); // Ukupna cena porudžbine
        $table->string('status')->default('pending'); // Status (pending, shipped, delivered, cancelled)
        $table->string('delivery_address'); // Adresa za isporuku odeće/obuće
        
        $table->timestamps(); // created_at i updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
