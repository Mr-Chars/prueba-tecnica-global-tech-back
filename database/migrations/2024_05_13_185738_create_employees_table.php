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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('lastname', 20);
            $table->string('second_lastname', 20);
            $table->string('first_name', 20);
            $table->string('other_names', 50)->nullable();
            $table->set('country_employment', ['COL', 'EEUU']);
            $table->set('type_identification', ['cedula_ciudadania', 'cedula_extranjeria', 'pasaporte', 'permiso_especial']);
            $table->string('code_identification', 20)->unique();
            $table->string('email', 300)->unique();
            $table->string('date_admission', 20);
            $table->set('area', ['administracion', 'financiera', 'compras', 'infraestructura', 'operacion', 'talento_humano', 'servicios_varios']);
            $table->boolean('state')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
