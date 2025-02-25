<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCostosVentasDetalleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('costos_ventas_detalle', function (Blueprint $table) {
            $table->id();

            // productos
            $table->unsignedBigInteger("costos_ventas_id");
            $table->foreign("costos_ventas_id")->references("id")->on("costos_ventas");

            $table->decimal('costo', 10, 2);
            $table->date('fecha'); // Almacena el mes y año en formato YYYY-MM-DD

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
        Schema::dropIfExists('costos_ventas_detalle');
    }
}
