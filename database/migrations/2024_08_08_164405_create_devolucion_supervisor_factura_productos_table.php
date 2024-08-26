<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDevolucionSupervisorFacturaProductosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('devolucion_supervisor_factura_productos', function (Blueprint $table) {
            $table->id();

            // Relación con devolucion_supervisor_facturas
            $table->unsignedBigInteger('devolucion_supervisor_factura_id');
            $table->foreign('devolucion_supervisor_factura_id', 'dsf_id_foreign') // Nombre personalizado
                  ->references('id')->on('devolucion_supervisor_facturas')
                  ->onDelete('cascade');

            $table->unsignedBigInteger("factura_detalle_id");
            $table->foreign('factura_detalle_id', 'fd_id_foreign') // Nombre personalizado
            ->references('id')->on('factura_detalles')
            ->onDelete('cascade');

            $table->integer("cantidad")->length(5);
            $table->double('monto', 7, 2);
            $table->double('monto_unidad', 7, 2);

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
        Schema::dropIfExists('devolucion_supervisor_factura_productos');
    }
}
