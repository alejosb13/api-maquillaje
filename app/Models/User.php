<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'apellido',
        'cargo',
        'email',
        'password',
        'cedula',
        'celular',
        'domicilio',
        'direccion_negocio',
        'fecha_nacimiento',
        'fecha_ingreso',
        'estado',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function authorizeRoles($roles){
        // abort_unless($this->hasAnyRole($roles), 401);
        if($this->hasAnyRole($roles)){
            return true;
        }

        return false;
    }

    public function hasAnyRole($roles){
        if (is_array($roles)) {
            foreach ($roles as $role) {
                if ($this->hasRole($role)) {
                    return true;
                }
            }
        } else {
            if ($this->hasRole($roles)) {
                 return true;
            }
        }
        return false;
    }

    public function hasRole($role){
        if ($this->roles()->where('name', $role)->first()) {
            return true;
        }
        return false;
    }

    // one to many
    public function factura()
    {
        return $this->hasMany(Factura::class);
    }

    // one to many
    public function clientes()
    {
        return $this->hasMany(Cliente::class);
    }

    // one to one
    public function recibo()
    {
        return $this->hasOne(Recibo::class);
    }

    // one to one
    public function meta()
    {
        return $this->hasOne(Meta::class);
    }
}
