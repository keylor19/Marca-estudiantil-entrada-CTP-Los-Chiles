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
        Schema::table('configuraciones', function (Blueprint $table) {
            $table->boolean('avisar_por_whatsapp')->default(false)->after('segundos_pantalla');
            $table->boolean('avisar_por_correo')->default(false)->after('avisar_por_whatsapp');
            $table->boolean('avisar_entrada')->default(true)->after('avisar_por_correo');
            $table->boolean('avisar_salida')->default(true)->after('avisar_entrada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuraciones', function (Blueprint $table) {
            $table->dropColumn(['avisar_por_whatsapp', 'avisar_por_correo', 'avisar_entrada', 'avisar_salida']);
        });
    }
};
