<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gasto extends Model
{
    use HasFactory;

    protected $fillable = [
        "tipo",
        "numero",
        "conceptualizacion",
        "monto",
        "fecha_comprobante",
        "estado",
    ];

    public $tipos = [
        "Empresa",
        "MaRo",
        "Adicional",
    ];

    public function typeToString()
    {
        $this->tipo = $this->tipos[$this->tipo];
    }

    public function typeValueString()
    {
        $this->tipo_desc = $this->tipos[$this->tipo];
    }
}
