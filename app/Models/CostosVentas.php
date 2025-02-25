<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CostosVentas extends Model
{
    use HasFactory;
    protected $fillable = [
        'producto_id',
        'costo',
        'estado',
    ];

    public function costo_ventas_detalles()
    {
        return $this->hasMany(CostosVentasDetalle::class, 'costos_ventas_id');
    }

}
