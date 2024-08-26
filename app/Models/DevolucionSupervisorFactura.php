<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DevolucionSupervisorFactura extends Model
{
    use HasFactory;

    protected $table = 'devolucion_supervisor_facturas';

    protected $fillable = [
        'factura_id',
        'monto',
        'saldo_restante',
        'origen',
        'monto_devueltos',
        'estado',
    ];

    // Relación con Factura
    public function factura()
    {
        return $this->belongsTo(Factura::class);
    }

    // Relación con Producto devuelto
    public function producto_deduccion()
    {
        return $this->hasMany(DevolucionSupervisorFacturaProducto::class);
    }
}