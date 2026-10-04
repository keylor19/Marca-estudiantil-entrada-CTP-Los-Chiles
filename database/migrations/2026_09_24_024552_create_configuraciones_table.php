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
        Schema::create('configuraciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_colegio')->default('CTP Los Chiles');
            $table->string('logo')->nullable();
            $table->time('hora_entrada')->default('07:00:00');
            $table->unsignedSmallInteger('minutos_tolerancia')->default(10);
            $table->boolean('registrar_salida')->default(false);
            $table->json('dias_lectivos');
            $table->unsignedTinyInteger('segundos_pantalla')->default(4);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuraciones');
    }
};
