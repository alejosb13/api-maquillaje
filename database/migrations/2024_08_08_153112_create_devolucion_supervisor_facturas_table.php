<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDevolucionSupervisorFacturasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('devolucion_supervisor_facturas', function (Blueprint $table) {
            $table->id();

            // Facturas
            $table->unsignedBigInteger("factura_id");
            $table->foreign("factura_id")->references("id")->on("facturas");

            $table->double('monto', 7, 2);
            $table->double("saldo_restante", 7, 2);

            $table->string("origen",4)->nullable();
            $table->double("monto_devueltos", 7, 2);

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
        Schema::dropIfExists('devolucion_supervisor_facturas');
    }
}
