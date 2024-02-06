<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Importacion extends Model
{
    use HasFactory;

    protected $fillable = [
        "inversion_id",
        "fecha_inversion",
        "numero_recibo",
        "numero_inversion",
        "monto_compra",
        "conceptualizacion",
        "precio_envio",
        "estado",
    ];
     
    // one to many
    public function inversion()
    {
        return $this->belongsTo(Inversion::class);
    }
}
