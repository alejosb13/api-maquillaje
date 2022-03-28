<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recibo extends Model
{
    use HasFactory;

    protected $fillable = [
        'min',
        'max',
        'user_id',
        'recibo_cerrado',
        'estado',
    ];


    // one to many inversa
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
