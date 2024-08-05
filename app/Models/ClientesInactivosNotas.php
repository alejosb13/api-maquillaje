<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientesInactivosNotas extends Model
{
    use HasFactory;
    protected $fillable = [
        "cliente_id",
        "tipos",
        "estado",
    ];

    public $tiposNotas = [
        0 => "Sin seleccionar",
        1 => "Fuera del país",
        2 => "Inconformidad",
        3 => "Cambio de dirección",
        4 => "Código duplicado",
        5 => "Cierre de negocio",
    ];

    public function notaValueString()
    {
        $this->tiposNotaText = $this->tiposNotas[$this->tipos];
    }

    // one to many
    public function clientes()
    {
        return $this->hasMany(Cliente::class);
    }
}
