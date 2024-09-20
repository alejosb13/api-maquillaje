<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zona extends Model
{
    use HasFactory;

    protected $table = 'zonas';
    protected $fillable = ['nombre', "estado"];

    public function departamentos()
    {
        return $this->hasMany(Departamento::class);
    }
}
