<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bitácora de los avisos enviados a los encargados (para saber si llegaron o por qué fallaron).
     */
    public function up(): void
    {
        Schema::create('avisos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->foreignId('asistencia_id')->nullable()->constrained('asistencias')->nullOnDelete();
            $table->string('tipo', 20);    // entrada | salida
            $table->string('canal', 20);   // whatsapp | correo
            $table->string('destino');
            $table->string('estado', 20); // enviado | fallido
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['estudiante_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avisos');
    }
};
