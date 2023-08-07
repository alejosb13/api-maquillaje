<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Factura;
use App\Models\MetaRecuperacion;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScriptController extends Controller
{
    public function AsignarPrecioPorUnidadGlobal()
    {
        $response = [];
        $status = 200;

        DB::beginTransaction();
        try {
            AsignarPrecioPorUnidadGlobal();
            // DB::rollback();
            DB::commit();
            return response()->json([
                'mensaje' => "Exito [AsignarPrecioPorUnidadGlobal]",
            ], 200);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(["mensaje" => "Error en el Script  [AsignarPrecioPorUnidadGlobal]"], 400);
        }

        return response()->json($response, $status);
    }

    public function validarStatusPagadoGlobal()
    {
        $response = [];
        $status = 200;

        DB::beginTransaction();
        try {

            $clientes = Cliente::select("*")
                ->where('estado', 1)
                ->get();
            if (count($clientes) > 0) {
                // validarStatusPagadoGlobal(1); //cliente perfecto
                foreach ($clientes as $key => $cliente) {
                    // print_r($cliente->id." "."\n");
                    validarStatusPagadoGlobal($cliente->id);
                }
                // DB::rollback();

                DB::commit();
                return response()->json(['mensaje' => "Exito [validarStatusPagadoGlobal]",], 200);
            }

            return response()->json(['mensaje' => "No hay clientes [validarStatusPagadoGlobal]",], 400);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(["mensaje" => "Error en el Script  [validarStatusPagadoGlobal]"], 400);
        }

        return response()->json($response, $status);
    }

    public function ActualizarPrecioFactura($id)
    {
        $response = [];
        $status = 200;

        DB::beginTransaction();
        try {
            ActualizarPrecioFactura($id); //cliente perfecto
            // DB::rollback();
            DB::commit();
            return response()->json([
                'mensaje' => "Exito [ActualizarPrecioFactura]",
            ], 200);
        } catch (Exception $e) {
            DB::rollback();
            // print_r(json_encode($e));
            return response()->json(["mensaje" => "Error en el Script  [ActualizarPrecioFactura]"], 400);
        }

        return response()->json($response, $status);
    }

    public function validarMetaRecuperacion()
    {
        // La meta de recuperacion no es lo mismo que META. Es solo para la seccion de recuperacion
        $inicioMesActual =  Carbon::now()->firstOfMonth()->toDateString();

        // DB::enableQueryLog();

        // $users = User::where([
        //     ["estado", "=", 1]
        // ])->get();

        // foreach ($users as $user) {
        $total = 0;

        $facturas = Factura::select("*")
            ->where('tipo_venta',  1) // credito 
            ->where('status_pagado', 0)
            ->where('created_at', "<", $inicioMesActual . " 00:00:00")
            ->where('user_id', 30)
            ->where('status', 1)
            ->get();

        if (count($facturas) > 0) {
            $fechaActual = Carbon::now();
            // $fechaActual = Carbon::parse("2023-08-27 00:00:00");
            foreach ($facturas as $factura) {
                $factura->user;

                // Seteo las fechas sin hora para que calcule correctamente los dias
                $fecha_vencimiento = Carbon::parse(Carbon::parse($factura->fecha_vencimiento)->toDateString());
                $fecha_creacion = Carbon::parse(Carbon::parse($factura->created_at)->toDateString());

                $factura->dias_vencimiento = $fecha_creacion->copy()->diffInDays($fecha_vencimiento); // 45
                $factura->dias_transcurrido = $fecha_creacion->copy()->diffInDays($fechaActual);

                // $factura->meses_de_pago = Carbon::parse($fecha_vencimiento)->floatDiffInMonths($created_at); // 1.123233
                // $factura->meses_transcurrido = Carbon::parse($fechaActual)->floatDiffInMonths("2023-06-27");

                // Alternativa si los dias son inexactos
                // $query = "SELECT *, ABS(f.dias - $factura->dias_vencimient) as X FROM frecuencias_facturas f ORDER BY X LIMIT 1";

                // $dias_frecuencias = DB::table(DB::raw('frecuencias_facturas f'))
                //     ->select(DB::raw("*, ABS(f.dias - $factura->dias_vencimient) as X"))
                //     ->orderBy("X")
                //     ->take(1)
                //     ->first();
                // $factura->dias_vencimiento_correcto = $dias_frecuencias->dias;

                // 1 - obtengo lo abonado por el usuario
                $saldo = $factura->monto - $factura->saldo_restante;

                // $diaMetaFin = $fecha_creacion->copy()->lastOfMonth(); // obtengo el ultimo dia del mes en el que se creo la factura
                // $factura->diasSumaMeta = $fecha_creacion->copy()->diffInDays($diaMetaFin); // calculo cantidad de dias entre fecha de creacion y final de mes
                
                // 2 - genero una copia de la fecha de creacion para controlar el while
                $fechaCreacionBendera = $fecha_creacion->copy();
                
                // 3 - acumulador de dias transcurridos en el while
                $diasCobroMeta = 0;

                // 4- declaro ultimo dia del mes actual para saber cuando detenerme en caso que falten mas cuotas
                $ultimoDiaMesActual = $fechaActual->copy()->lastOfMonth();

                while ($fechaCreacionBendera < $fecha_vencimiento) {

                    // if( $fechaCreacionBendera->month == $ultimoDiaMesActual->month && $ultimoDiaMesActual )
                    if ($fechaCreacionBendera == $ultimoDiaMesActual) {
                        break;
                    }

                    $fechaCreacionBendera->addDay();
                    ++$diasCobroMeta;

                    // $factura->ale = $fechaCreacionBendera->toDateString();

                }

                $factura->diasCobroMeta = $diasCobroMeta;
                if ($factura->dias_vencimiento == 0) {
                    $factura->precioPorDia = 0;
                } else {
                    $factura->precioPorDia = $factura->monto / $factura->dias_vencimiento;
                }
                $factura->monto_pagado =  $saldo;

                $precioDiasMeta = $diasCobroMeta * $factura->precioPorDia;
                $factura->precioDiasMeta = $precioDiasMeta - $saldo;

                $total += decimal($factura->precioDiasMeta);





                // "6 Días"   >= 0 == 1 mes
                // "15 Días",
                // "30 Días", <= 30 == 1 mes
                // "45 Días", >= 30 == 2 mes
                // "60 Días", <= 60 == 2 mes
                // "90 Días", >= 60 == 3 mes
            }
        }

        // $resultado = $total  * 0.85;
        $resultado = $total  * 1;

        return response()->json([
            'resultado' => $resultado,
            'facturas' => $facturas,
        ], 200);

    }
}
