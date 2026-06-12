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
        Schema::create('products', function (Blueprint $table) {
        $table->id(); // Primarni ključ za proizvod
        
        // 2. TIP MIGRACIJE: Spoljni ključ koji povezuje proizvod sa kategorijom
        $table->foreignId('category_id')->constrained()->onDelete('cascade');
        
        $table->string('name'); // Naziv artikla (npr. "Kožna jakna", "Letnja haljina")
        $table->text('description')->nullable(); // Detaljan opis proizvoda
        
        // 3. TIP MIGRACIJE: Dodatna ograničenja (unsigned i decimal)
        $table->decimal('price', 8, 2)->unsigned(); // Cena (maksimalno 999999.99)
        $table->integer('stock')->unsigned()->default(0); // Količina na stanju
        $table->string('size')->nullable(); // Veličina (S, M, L, XL...)
        
        $table->timestamps(); // created_at i updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
