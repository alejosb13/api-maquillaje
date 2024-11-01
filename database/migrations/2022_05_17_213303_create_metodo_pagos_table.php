<?php

use App\Models\Factura;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMetodoPagosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('metodo_pagos', function (Blueprint $table) {
            $table->id();

            // Facturas
            $table->unsignedBigInteger("factura_historial_id");
            $table->foreign("factura_historial_id")->references("id")->on("factura_historials");

            $table->integer("tipo")->length(2); // 1 = efectivo, 2 = transferencia, 3 = tarjeta, 4 = cheque
            $table->string("detalle",120)->nullable();
            $table->string("autorizacion", 20)->nullable();
            $table->integer("estado")->length(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('metodo_pagos');
    }
}
