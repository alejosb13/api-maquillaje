<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inversion extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "numero_serial",
        "envio",
        "porcentaje_comision_vendedor",
        "cantidad_total",
        "costo",
        "peso_porcentual_total",
        "costo_total",
        "precio_venta",
        "venta_total",
        "costo_real_total",
        "ganancia_bruta_total",
        "comision_vendedor_total",

        "estado",
    ];

        // one to many inversa
        public function user()
        {
            return $this->belongsTo(User::class);
        }
    
        // one to many
        public function inversion_detalle()
        {
            return $this->hasMany(InversionDetail::class);
        }
        public function inversion_detalle_delete()
        {
            // delete all related photos 
            $this->inversion_detalle()->delete();
            // as suggested by Dirk in comment,
            // it's an uglier alternative, but faster
            // Photo::where("user_id", $this->id)->delete()
    
            // delete the user
    
        }
    
}
