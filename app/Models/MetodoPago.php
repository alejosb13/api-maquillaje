<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetodoPago extends Model
{
    use HasFactory;

    // 1 = efectivo, 2 = transferencia, 3 = tarjeta, 4 = cheque
    public $tipos = [
        1 => 'Efectivo',
        2 => 'Transferencia',
        3 => 'Tarjeta',
        // 4 => 'Cheque',
    ];

    protected $fillable = [
        'factura_historial_id',
        'tipo',
        'detalle',
        'estado',
        'autorizacion',
    ];

    /**
     * Devuelve el tipo de pago en texto
     *
     * @param number $tipo id del tipo de pago
     * @return string
     **/

    public function getTipoPago(){
        return $this->tipos[$this->tipo];
    }
}
