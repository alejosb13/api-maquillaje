<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TareasCrons extends Model
{
    use HasFactory;
    public $tareas = [
        1 => 'resetCategory',
    ];

    protected $fillable = [
        'cron',
        'descripcion',
        'estado'
    ];

}
