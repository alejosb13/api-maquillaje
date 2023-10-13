<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndicesDashboard extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "cartera_total",
        "ventas_meta_porcentaje",
        "ventas_meta_monto",
        "ventas_meta_total",
        "recuperacionmensual_porcentaje",
        "recuperacionmensual_total",
        "recuperacionmensual_abonos",
        "recuperacion_total",
        "mora30_60",
        "mora60_90",
        "clientes_nuevos",
        "incentivos",
        "incentivos_supervisor",
        "clientes_inactivos",
        "clientes_reactivados",
        "productos_vendidos",
        "ventas_mes_total",
        "ventas_mes_meta",
        "ventas_mes_porcentaje",
    ];
}
