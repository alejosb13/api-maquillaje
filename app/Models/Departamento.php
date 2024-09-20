<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    use HasFactory;

    protected $table = 'departamentos';
    protected $fillable = ['nombre', 'zona_id',"estado"];

    public function zona()
    {
        return $this->belongsTo(Zona::class);
    }

    public function municipios()
    {
        return $this->hasMany(Municipio::class);
    }
}
