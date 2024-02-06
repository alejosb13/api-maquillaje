<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImportacionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('importacions', function (Blueprint $table) {
            $table->id();

            // inversions
            $table->unsignedBigInteger("inversion_id");
            $table->foreign("inversion_id")->references("id")->on("inversions");

            $table->timestamp("fecha_inversion")->nullable();
            $table->string("numero_recibo",80)->nullable();
            $table->string("numero_inversion",80)->nullable();
            $table->double('monto_compra', 16, 2);
            $table->string("conceptualizacion",120)->nullable();
            $table->double('precio_envio', 16, 2);

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
        Schema::dropIfExists('importacions');
    }
}
