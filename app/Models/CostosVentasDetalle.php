<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CostosVentasDetalle extends Model
{
    use HasFactory;

    protected $table = 'costos_ventas_detalle';

    protected $fillable = [
        'costos_ventas_id',
        'costo',
        'fecha'
    ];

    protected $dates = ['fecha']; // Para que Laravel lo maneje como una fecha

    public function costosVentas()
    {
        return $this->belongsTo(CostosVentas::class, 'costos_ventas_id');
    }

    // Obtener solo el mes y año de la fecha
    public function getMesAnioAttribute()
    {
        return $this->fecha->format('Y-m');
    }
}
