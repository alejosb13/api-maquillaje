<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGastosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gastos', function (Blueprint $table) {
            $table->id();

            $table->integer("tipo")->length(3);
            $table->string("numero",80)->nullable();
            $table->string("conceptualizacion",160)->nullable();
            $table->integer("tipo_pago")->length(3);
            $table->string("pago_desc",160)->nullable();
            $table->double('monto', 16, 2);
            $table->timestamp("fecha_comprobante")->nullable();
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
        Schema::dropIfExists('gastos');
    }
}
