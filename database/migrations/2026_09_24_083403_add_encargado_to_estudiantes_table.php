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
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->string('encargado_nombre')->nullable()->after('fecha_nacimiento');
            $table->string('encargado_parentesco', 50)->nullable()->after('encargado_nombre');
            $table->string('encargado_telefono', 30)->nullable()->after('encargado_parentesco');
            $table->string('encargado_correo')->nullable()->after('encargado_telefono');
            $table->boolean('avisar_encargado')->default(true)->after('encargado_correo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->dropColumn(['encargado_nombre', 'encargado_parentesco', 'encargado_telefono', 'encargado_correo', 'avisar_encargado']);
        });
    }
};
