<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Año lectivo en que el estudiante está matriculado. Los estudiantes ya registrados
     * se toman como matriculados en el año actual.
     */
    public function up(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->unsignedSmallInteger('anio_matricula')->nullable()->after('activo')->index();
        });

        DB::table('estudiantes')->update(['anio_matricula' => now()->year]);
    }

    public function down(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->dropIndex(['anio_matricula']);
            $table->dropColumn('anio_matricula');
        });
    }
};
