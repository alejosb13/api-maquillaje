<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();

            // $table->string("categoria_id");
            // $table->string("vendedor_id",31);
            // $table->string("frecuencia_id",38);
            $table->unsignedBigInteger("categoria_id");
            $table->foreign("categoria_id")->references("id")->on("categorias");

            $table->unsignedBigInteger("frecuencia_id")->nullable();
            $table->foreign("frecuencia_id")->references("id")->on("frecuencias");

            $table->unsignedBigInteger("user_id")->nullable();
            $table->foreign("user_id")->references("id")->on("users");

            $table->string("nombreCompleto", 80);
            $table->string("nombreEmpresa", 80);
            $table->unsignedBigInteger("celular")->length(13);
            $table->unsignedBigInteger("telefono")->length(13)->nullable();
            $table->string("direccion_casa", 180);
            $table->string("direccion_negocio", 180)->nullable();
            $table->string("cedula", 22); //14 sin guiones
            $table->string("dias_cobro", 120);
            // $table->timestamp('fecha_vencimiento');
            $table->integer("estado")->length(1);

            $table->foreignId('zona_id')->nullable()->constrained('zonas')->onDelete('set null');
            $table->foreignId('departamento_id')->nullable()->constrained('departamentos')->onDelete('set null');
            $table->foreignId('municipio_id')->nullable()->constrained('municipios')->onDelete('set null');

            $table->timestamps();
        });
    }

    // nombre del negocio propietartio celular zona geografica o de partamento direccion del  o domicilia vendedor identificacion frecuencia de pago = semanal quiencenal o mes - dias de cobros 
    // nombre
    // propietario
    // celular
    // Contacto
    // Email
    // Telefono
    // Dir Casa
    // Dir Negocio
    // Categoria Cliente
    // Vendedor
    // Cedula
    // Frecuencia con que el cliente paga (otra tabla relacionada con esta)
    //     Semanal
    //     Quincenal
    //     Mensual
    //     Al Vencimiento
    // Dia de cobro (string ???)
    //     (Lunes a Sabado)

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('clientes');
    }
}
