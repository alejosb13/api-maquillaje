<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecibosRangosSinTerminar extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "rango",
        "recibos_faltantes",
        "habilitado",
        "estado",
    ];
}
