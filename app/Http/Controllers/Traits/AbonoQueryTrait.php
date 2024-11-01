<?php
namespace App\Http\Controllers\Traits;

use App\Models\Cliente;
use App\Models\FacturaHistorial;
use App\Models\MetodoPago;
use App\Models\ReciboHistorial;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

trait AbonoQueryTrait
{
 
    public function AbonoListQuery(Request $request)
    {
        $parametros = [["estado", 1]];

        if (empty($request->dateIni)) {
            $dateIni = Carbon::now();
        } else {
            $dateIni = Carbon::parse($request->dateIni);
        }

        if (empty($request->dateFin)) {
            $dateFin = Carbon::now();
        } else {
            $dateFin = Carbon::parse($request->dateFin);
        }

        // DB::enableQueryLog();

        $abonos =  FacturaHistorial::where($parametros);

        // // ** Filtrado por rango de fechas 
        // $abonos->when($request->allDates && $request->allDates == "false", function ($q) use ($dateIni, $dateFin) {
        //     return $q->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
        // });

        // ** Filtrado por userID
        $abonos->when($request->userId && $request->userId != 0, function ($q) use ($request) {
            $query = $q;
            // vendedor
            // supervisor
            // administrador

            $user = User::select("*")
                ->where('estado', 1)
                ->where('id', $request->userId)
                ->first();

            if (!$user) {
                return $query;
            }

            return $query->where('user_id', $user->id);
        });

        // ** Filtrado por rango de fechas 
        $abonos->when($request->allDates && $request->allDates == "false", function ($q) use ($dateIni, $dateFin) {
            return $q->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
        });

        $abonos->when(isset($request->autorizacion) && $request->autorizacion != "", function ($q) use ($request) {
            $MetodoPago = MetodoPago::where('autorizacion', $request->autorizacion)->where('estado', 1)->first();

            if ($MetodoPago) {
                $q->where('id',  $MetodoPago->factura_historial_id);
            }
            return $q;
        });

        // ** Filtrado por numero de recibo
        $abonos->when($request->numeroRecibo, function ($q) use ($request) {
            $query = $q;
            $fHistorial_ids = [];
            $recibos = ReciboHistorial::select("*")
                ->where('estado', 1)
                ->where('numero', $request->numeroRecibo)
                ->get();

            // dd(json_encode($recibos));
            if (count($recibos) > 0) {
                foreach ($recibos as $recibo) {
                    $fHistorial_ids[] = $recibo->factura_historial_id;
                }

                $query = $query->wherein('id', $fHistorial_ids);
            }

            return $query;
        });

        // filtrados para campos numericos
        $abonos->when($request->filter && is_numeric($request->filter), function ($q) use ($request) {
            $query = $q;
            // id de recibos 
            $filterSinNumeral = str_replace("#", "", $request->filter);

            $query = $query->where('id', 'LIKE', '%' . $filterSinNumeral . '%')
                ->where('precio', 'LIKE', '%' . $filterSinNumeral . '%', "or");


            $factura_historial_ids = [];
            $recibos = ReciboHistorial::select("*")
                ->where('estado', 1)
                ->where('numero', 'LIKE', '%' . $filterSinNumeral . '%')
                ->get();

            // dd(json_encode($recibos));
            if (count($recibos) > 0) {
                foreach ($recibos as $recibo) {
                    $factura_historial_ids[] = $recibo->factura_historial_id;
                }

                $query = $query->wherein('id', $factura_historial_ids, "or");
            }

            return $query;
        }); // Fin Filtrado


        // ** Filtrado para string
        $abonos->when($request->filter && !is_numeric($request->filter), function ($q) use ($request) {
            $query = $q;

            // nombre cliente
            $clientesId = [];
            $clientes = Cliente::select("*")
                ->where('estado', 1)
                ->where('nombreCompleto', 'LIKE', '%' . $request->filter . '%')
                ->get();

            if (count($clientes) > 0) {
                foreach ($clientes as $cliente) {
                    $clientesId[] = $cliente->id;
                }
            }

            return $query->wherein('cliente_id', $clientesId);
        }); // Fin Filtrado por cliente

        if ($request->disablePaginate == 0) {
            $abonos = $abonos->orderBy('created_at', 'desc')->paginate(15);
        } else {
            $abonos = $abonos->orderBy('created_at', 'desc')->get();
        }

        if (count($abonos) > 0) {
            foreach ($abonos as $abono) {
                $abono->factura;
                $abono->recibo_historial;
                $abono->cliente;
                $abono->usuario;

                $abono->metodo_pago;
                if ($abono->metodo_pago) {
                    $abono->metodo_pago->tipoPago = $abono->metodo_pago->getTipoPago();
                }

                // $cliente = Cliente::find($abono->cliente_id);
                // $abono->cliente = $cliente;

                // $usuario = User::find($abono->user_id);
                // $abono->usuario = $usuario;
            }
        }

        return $abonos;
    }
}