<?php
namespace App\Http\Controllers\Traits;
use Illuminate\Support\Facades\DB;

trait CommonValidations
{
    public function validNumberRange($min, $max, $id)
    {
        if ($id) {
            $minimo = DB::table('recibos')->where([
                ['id', "!=", $id],
                ['estado', "=", 1],
            ])->whereBetween('min', [$min, $max])->get();

            $maximo = DB::table('recibos')->where([
                ['id', "!=", $id],
                ['estado', "=", 1],
            ])->whereBetween('max', [$min, $max])->get();
        } else {
            $minimo = DB::table('recibos')->where([
                ['estado', "=", 1],
            ])->whereBetween('min', [$min, $max])->get();

            $maximo = DB::table('recibos')->where([
                ['estado', "=", 1],
            ])->whereBetween('max', [$min, $max])->get();
        }

        // print_r (json_encode($minimo));
        // print_r (json_encode($maximo));
        if (count($minimo) == 0 && count($maximo) == 0) {
            return true;
        }

        return false;
    }

    public function validNumberRangeTalonarios($min, $max, $id)
    {
        if ($id) {
            $minimo = DB::table('talonarios')->where([
                ['id', "!=", $id],
                ['estado', "=", 1],
            ])->whereBetween('min', [$min, $max])->get();

            $maximo = DB::table('talonarios')->where([
                ['id', "!=", $id],
                ['estado', "=", 1],
            ])->whereBetween('max', [$min, $max])->get();
        } else {
            $minimo = DB::table('talonarios')->where([
                ['estado', "=", 1],
            ])->whereBetween('min', [$min, $max])->get();

            $maximo = DB::table('talonarios')->where([
                ['estado', "=", 1],
            ])->whereBetween('max', [$min, $max])->get();
        }

        if (count($minimo) == 0 && count($maximo) == 0) {
            return true;
        }

        return false;
    }
}