<?php

namespace App\Models;

use App\Http\Controllers\FacturaDetallesController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cliente_id',
        'monto',
        'nruc',
        'fecha_vencimiento',
        'iva',
        // 'tcambio',
        // 'monto_cambio',
        'tipo_venta',
        'status_pagado',
        'saldo_restante',
        'status',
        "status_pagado_at"
    ];



    // one to many inversa
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    // one to many inversa
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // one to many
    public function factura_detalle()
    {
        return $this->hasMany(Factura_Detalle::class);
    }    // one to many

    public function factura_historial()
    {
        return $this->hasMany(FacturaHistorial::class);
    }

    // one to many
    public function recibo_historial_contado()
    {
        return $this->hasOne(ReciboHistorialContado::class);
    }

    // one to many
    public function devolucion_factura()
    {
        return $this->hasMany(DevolucionFactura::class);
    }

    public function devolucionesProductos()
    {
        return $this->hasManyThrough(DevolucionProducto::class, FacturaDetallesController::class, 'factura_id', 'factura_detalle_id');
    }

}

