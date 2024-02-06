<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInversionDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inversion_details', function (Blueprint $table) {
            $table->id();

            // inversions
            $table->unsignedBigInteger("inversion_id");
            $table->foreign("inversion_id")->references("id")->on("inversions");

            $table->string("codigo",80)->nullable();
            $table->string("producto",80)->nullable();
            $table->string("marca",80)->nullable();
            $table->integer("cantidad")->length(1);
            $table->double('precio_unitario', 16, 2);
            $table->double('porcentaje_ganancia', 16, 2);
            $table->double('costo', 16, 2);
            $table->double('peso_porcentual', 16, 2);
            $table->double('peso_absoluto', 16, 2);
            $table->double('c_u_distribuido', 16, 2);
            $table->double('costo_total', 16, 2);
            $table->double('subida_ganancia', 16, 2);
            $table->double('precio_venta', 16, 2);
            $table->double('venta', 16, 2);
            $table->double('venta_total', 16, 2);
            $table->double('costo_real', 16, 2);
            $table->double('ganancia_bruta', 16, 2);
            $table->double('comision_vendedor', 16, 2);
            $table->integer("producto_insertado")->length(1)->default(0);

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
        Schema::dropIfExists('inversion_details');
    }
}
