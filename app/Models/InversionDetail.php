<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InversionDetail extends Model
{
    use HasFactory;
    protected $fillable = [
        "inversion_id",
        "codigo",
        "producto",
        "marca",
        "cantidad",
        "precio_unitario",
        "porcentaje_ganancia",
        "costo",
        "peso_porcentual",
        "peso_absoluto",
        "c_u_distribuido",
        "costo_total",
        "subida_ganancia",
        "precio_venta",
        "venta",
        "venta_total",
        "costo_real",
        "ganancia_bruta",
        "comision_vendedor",
        "estado",
    ];

}
