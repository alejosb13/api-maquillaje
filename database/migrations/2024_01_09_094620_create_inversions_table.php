<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInversionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inversions', function (Blueprint $table) {
            $table->id();


            // users
            $table->unsignedBigInteger("user_id");
            $table->foreign("user_id")->references("id")->on("users");

            $table->double("cantidad_total", 16, 2);
            $table->double("costo", 16, 2);
            $table->double("peso_porcentual_total", 16, 2);
            $table->double("costo_total", 16, 2);
            $table->double("precio_venta", 16, 2);
            $table->double("venta_total", 16, 2);
            $table->double("costo_real_total", 16, 2);
            $table->double("ganancia_bruta_total", 16, 2);
            $table->double("comision_vendedor_total", 16, 2);
            $table->double("ganancia_total", 16, 2);

            $table->double("envio", 16, 2);
            $table->double("porcentaje_comision_vendedor", 16, 2);

            $table->integer("estatus_cierre")->length(1)->default(1);

            $table->integer("estado")->length(1)->default(1);

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
        Schema::dropIfExists('inversions');
    }
}
