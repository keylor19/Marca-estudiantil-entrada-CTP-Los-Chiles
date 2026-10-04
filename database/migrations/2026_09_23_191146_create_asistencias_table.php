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
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes');
            $table->date('fecha')->index();
            $table->time('hora_entrada');
            $table->time('hora_salida')->nullable();
            $table->string('estado')->default('presente'); // presente | tarde
            $table->foreignId('usuario_id')->constrained('users');                   // guarda que registró la entrada
            $table->foreignId('usuario_salida_id')->nullable()->constrained('users'); // guarda que registró la salida
            $table->string('observacion')->nullable();
            $table->timestamps();

            // un solo registro por estudiante por día: el segundo escaneo se detecta como duplicado
            $table->unique(['estudiante_id', 'fecha']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
