<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('apellido');
            $table->string('cargo');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->integer("estado")->length(1);
            $table->timestamps();
            $table->string("cedula",22)->nullable(); //14 sin guiones
            $table->unsignedBigInteger("celular")->length(13)->nullable();
            $table->string('domicilio',180)->nullable();
            // $table->string("direccion_negocio",180)->nullable();
            $table->timestamp('fecha_nacimiento')->nullable();
            $table->timestamp('fecha_ingreso')->nullable();
            $table->foreignId('zona_id')->nullable()->constrained('zonas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
