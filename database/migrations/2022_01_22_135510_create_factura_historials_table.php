<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFacturaHistorialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('factura_historials', function (Blueprint $table) {
            $table->id();

            // Facturas
            // $table->unsignedBigInteger("factura_id");
            // $table->foreign("factura_id")->references("id")->on("facturas");


            $table->unsignedBigInteger("cliente_id");
            $table->foreign("cliente_id")->references("id")->on("clientes");

            $table->unsignedBigInteger("user_id");
            $table->foreign("user_id")->references("id")->on("users");

            $table->double('precio', 7, 2);
            $table->integer("debitado")->length(1)->default(0); // Su usa para saber si el abono fue consumido en una factura
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
        Schema::dropIfExists('factura_historials');
    }
}
