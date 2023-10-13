<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIndicesDashboardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('indices_dashboards', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger("user_id");
            $table->foreign("user_id")->references("id")->on("users");
            
            $table->double('cartera_total', 9, 2);
            $table->double('ventas_meta_porcentaje', 5, 2);
            $table->double('ventas_meta_monto', 9, 2);
            $table->double('ventas_meta_total', 9, 2);
            $table->double('recuperacionmensual_porcentaje', 5, 2);
            $table->double('recuperacionmensual_total', 9, 2);
            $table->double('recuperacionmensual_abonos', 9, 2);
            $table->double('recuperacion_total', 9, 2);
            $table->double('mora30_60', 9, 2);
            $table->double('mora60_90', 9, 2);
            $table->integer("clientes_nuevos");
            $table->double('incentivos', 9, 2);
            $table->double('incentivos_supervisor', 9, 2);
            $table->integer("clientes_inactivos");
            $table->integer("clientes_reactivados");
            $table->integer("productos_vendidos");
            $table->double('ventas_mes_total', 5, 2);
            $table->double('ventas_mes_meta', 9, 2);
            $table->double('ventas_mes_porcentaje', 9, 2);
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
        Schema::dropIfExists('indices_dashboards');
    }
}
