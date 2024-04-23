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
        "tipo_pago",
        "pago_desc",
        "monto",
        "fecha_comprobante",
        "estado",
    ];

    public $tipos = [
        "Empresa",
        "MaRo",
        "Adicional",
    ];

    public $tiposPagos = [
        "Efectivo",
        "Transferencia",
        "Otro",
    ];

    public function typeToString()
    {
        $this->tipo = $this->tipos[$this->tipo];
    }

    public function typeValueString()
    {
        $this->tipo_desc = $this->tipos[$this->tipo];
    }

    public function typePayToString()
    {
        $this->tipo_pago = $this->tiposPagos[$this->tipo_pago];
    }

    public function typePayValueString()
    {
        $this->tipo_pago_str = $this->tiposPagos[$this->tipo_pago];
    }
}
