<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DevolucionSupervisorFacturaProducto extends Model
{
    use HasFactory;

    protected $table = 'devolucion_supervisor_factura_productos';

    protected $fillable = [
        'devolucion_supervisor_factura_id',
        'factura_detalle_id',
        'cantidad',
        'monto',
        'monto_unidad',
        'estado',
    ];

    // Relación con DevolucionSupervisorFactura
    public function devolucionFactura()
    {
        return $this->belongsTo(DevolucionSupervisorFactura::class, 'devolucion_supervisor_factura_id');
    }

    // Relación con Producto
    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function devolucion_supervisor_factura()
    {
        return $this->belongsTo(DevolucionSupervisorFactura::class, 'devolucion_supervisor_factura_id');
    }

    public function factura_detalle()
    {
        return $this->belongsTo(Factura_Detalle::class, 'factura_detalle_id');
    }
}
