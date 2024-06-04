<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Factura;
use App\Models\Factura_Detalle;
use App\Models\FacturaHistorial;
use App\Models\MetaHistorial;
use App\Models\Producto;
use App\Models\Recibo;
use App\Models\ReciboHistorial;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListadosPaginasController extends Controller
{
    public function facturasList(Request $request)
    {
        $response = [];
        $status = 200;
        // $facturaEstado = 1; // Activo
        $parametros = [];

        // if ($request['roleName'] == "vendedor") { // si es vendedor solo devuelvo sus facturas
        //     // vendedor
        //     // supervisor
        //     // administrador

        //     $parametros[] = ["user_id", $request['userId']];
        // }

        if (!is_null($request['estado'])) $parametros[] = ["status", $request['estado']];
        if (!is_null($request['tipo_venta'])) $parametros[] = ["tipo_venta", $request['tipo_venta']];
        if (!is_null($request['status_pagado'])) $parametros[] = ["status_pagado", $request['status_pagado']];
        if (!is_null($request['status_entrega'])) $parametros[] = ["entregado", $request['status_entrega']];
        if (!is_null($request['despachado'])) $parametros[] = ["despachado", $request['despachado']];
        if (!is_null($request['userId']) && $request['userId'] != 0) $parametros[] = ["user_id", $request['userId']];
        if (!is_null($request['created_at'])) {
            $created_at = Carbon::parse($request['created_at']);
            $parametros[] = ["created_at", '>=', $created_at . " 00:00:00"];
        }

        // DB::enableQueryLog();
        $facturas =  Factura::where($parametros);

        // ** Filtrado por cliente
        $facturas->when($request['filter'] && !is_numeric($request['filter']), function ($q) use ($request) {
            $clientesId = [];

            $clientes = Cliente::select('id')
                ->orWhere('nombreCompleto', 'LIKE', '%' . $request['filter'] . '%')
                ->orWhere('nombreEmpresa', 'LIKE', '%' . $request['filter'] . '%')
                ->get();

            if (count($clientes) > 0) {
                foreach ($clientes as $cliente) {
                    $clientesId[] = $cliente->id;
                }
            }

            return $q->wherein('cliente_id', $clientesId);
        }); // Fin Filtrado por cliente

        // ** Filtrado por Factura
        $facturas->when($request['filter'] && is_numeric($request['filter']), function ($q) use ($request) {
            return $q->where('id', 'LIKE', '%' . $request['filter'] . '%');
        }); // Fin Filtrado por Factura

        // dd(json_encode($facturas));
        $facturas = $facturas->paginate(15);
        // dd(DB::getQueryLog());

        if (count($facturas) > 0) {
            foreach ($facturas as $factura) {
                $factura->user;
                $factura->cliente->factura_historial;
                $factura->factura_detalle = $factura->factura_detalle()->where([
                    ['estado', '=', 1],
                ])->get();
            }
        }

        $response = $facturas;


        return response()->json($response, $status);
    }

    public function metasHistoricoList(Request $request)
    {
        $response = [];
        $status = 200;
        // $facturaEstado = 1; // Activo
        $parametros = [["estado", 1]];


        // if ($request['estado']) $parametros[] = ["estado", $request['estado']];
        if ($request['userId'] && $request['userId'] != 0) $parametros[] = ["user_id", $request['userId']];

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
        $metas =  MetaHistorial::where($parametros);

        // ** Filtrado por rango de fechas Meta 
        $metas->when($request->allDates && $request->allDates == "false", function ($q) use ($dateIni, $dateFin) {
            return $q->whereBetween('fecha_asignacion', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
        });

        // ** Filtrado por Meta
        $metas->when($request['filter'] && is_numeric($request['filter']), function ($q) use ($request) {
            return $q->where('id', 'LIKE', '%' . $request['filter'] . '%');
        });

        // dd(json_encode($facturas));
        $metas = $metas->orderBy('fecha_asignacion', 'desc')->paginate(15);
        // dd(DB::getQueryLog());

        if (count($metas) > 0) {
            foreach ($metas as $meta) {
                $meta->user;
            }
        }

        $response = $metas;


        return response()->json($response, $status);
    }

    public function recibosCreditosList(Request $request)
    {
        $response = [];
        $status = 200;
        // $facturaEstado = 1; // Activo
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

        $recibos =  ReciboHistorial::where($parametros);

        // ** Filtrado por rango de fechas 
        $recibos->when($request->allDates && $request->allDates == "false", function ($q) use ($dateIni, $dateFin) {
            return $q->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
        });

        // ** Filtrado por userID
        $recibos->when($request->userId && $request->userId != 0, function ($q) use ($request) {
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

            $recibo = Recibo::select("*")
                ->where('estado', 1)
                ->where('user_id', $user->id)
                ->first();

            if (!$recibo) {
                return $query;
            } else {
                return $query->where('recibo_id', $recibo->id);
            }
        });

        // ** Filtrado por rango de fechas 
        $recibos->when($request->allDates && $request->allDates == "false", function ($q) use ($dateIni, $dateFin) {
            return $q->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
        });

        // ** Filtrado por numero de recibo
        $recibos->when($request->numeroRecibo, function ($q) use ($request) {
            return $q->where('numero', 'LIKE', '%' . $request->numeroRecibo . '%');
        });

        // filtrados para campos numericos
        $recibos->when($request->filter && is_numeric($request->filter), function ($q) use ($request) {
            $query = $q;
            // id de recibos 
            $filterSinNumeral = str_replace("#", "", $request->filter);
            $query = $query->where('id', 'LIKE', '%' . $filterSinNumeral . '%');

            // precio y id de abonos 
            $abonosId = [];
            $abonos = FacturaHistorial::select("*")
                ->where('estado', 1)
                ->where('id', 'LIKE', '%' . $filterSinNumeral . '%')
                ->where('precio', 'LIKE', '%' . $filterSinNumeral . '%')
                ->get();

            if (count($abonos) > 0) {
                foreach ($abonos as $abono) {
                    $abonosId[] = $abono->id;
                }

                $query = $query->wherein('factura_historial_id', $abonosId, "or");
            }

            return $query;
        }); // Fin Filtrado


        // ** Filtrado para string
        $recibos->when($request->filter && !is_numeric($request->filter), function ($q) use ($request) {
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

                $abonosId = [];
                $abonos = FacturaHistorial::select("*")
                    ->where('estado', 1)
                    ->wherein('cliente_id', $clientesId)
                    ->get();

                if (count($abonos) > 0) {
                    foreach ($abonos as $abono) {
                        $abonosId[] = $abono->id;
                    }

                    $query = $query->wherein('factura_historial_id', $abonosId);
                }
            }

            return $query;
        }); // Fin Filtrado por cliente


        // dd($condicionesNumericas);
        // ['factura_historial','cliente','nombreCompleto'],
        // ['recibo','user','name'],
        // ['recibo','user','apellido'],

        // dd(json_encode($facturas));

        $recibos = $recibos->orderBy('created_at', 'desc')->paginate(15);
        // dd(DB::getQueryLog());
        // dd(DB::getQueryLog());

        if (count($recibos) > 0) {
            foreach ($recibos as $recibo) {
                $recibo->recibo->user;
                $recibo->factura_historial->cliente;
            }
        }

        $response = $recibos;


        return response()->json($response, $status);
    }

    public function abonosCreditosList(Request $request)
    {
        $response = [];
        $status = 200;
        // $facturaEstado = 1; // Activo
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

        $abonos = $abonos->orderBy('created_at', 'desc')->paginate(15);

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

        $response = $abonos;


        return response()->json($response, $status);
    }

    public function FacturaDetailClientList(Request $request)
    {
        $response = [];
        $status = 200;
        // $facturaEstado = 1; // Activo
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

        $facturaDetalle =  Factura_Detalle::where($parametros);

        // ** Filtrado por rango de fechas 
        $facturaDetalle->when($request->allDates && $request->allDates == "false", function ($q) use ($dateIni, $dateFin) {
            return $q->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
        });

        //** facturas del cliente
        $facturaDetalle->when($request->clienteId, function ($q) use ($request) {
            $query = $q;

            // nombre cliente
            $facturaId = [];
            $facturas = Factura::select("*")
                ->where('status', 1)
                ->where('cliente_id', $request->clienteId)
                ->get();

            if (count($facturas) > 0) {
                foreach ($facturas as $factura) {
                    $facturaId[] = $factura->id;
                }
            }

            return $query->wherein('factura_id', $facturaId);
        }); // Fin Filtrado por cliente

        $facturaDetalle = $facturaDetalle->orderBy('created_at', 'desc')->paginate(15);

        if (count($facturaDetalle) > 0) {
            foreach ($facturaDetalle as $productoVendido) {
                $productoVendido->factura->cliente;
                $productoVendido->producto;
            }
        }

        $response = $facturaDetalle;


        return response()->json($response, $status);
    }

    public function clientesList(Request $request)
    {
        $response = [];
        $status = 200;
        // $facturaEstado = 1; // Activo
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

        $clientes =  Cliente::query();

        // ** Filtrado por Estado 
        $clientes->when(isset($request->estado) && $request->estado != 2, function ($q) use ($request) {
            return $q->where('estado', $request->estado);
        });

        // ** Filtrado por rango de fechas 
        $clientes->when($request->allDates && $request->allDates == "false", function ($q) use ($dateIni, $dateFin) {
            return $q->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
        });

        // ** Filtrado por userID
        $clientes->when($request->userId && $request->userId != 0, function ($q) use ($request) {
            $query = $q;
            // vendedor
            // supervisor
            // administrador

            $user = User::select("*")
                // ->where('estado', 1)
                ->where('id', $request->userId)
                ->first();

            // print_r( $request->userId);
            if (!$user) {
                return $query;
            }

            return $query->where('user_id', $user->id);
        });

        $clientes->when($request->diasCobros, function ($q) use ($request) {
            $query = $q;
            $dias = explode(",", $request->diasCobros);
            $condicionDiasCobro = [];
            foreach ($dias as $dia) {
                array_push($condicionDiasCobro, ['dias_cobro', 'LIKE', '%' . $dia . '%', "or"]);
            }
            return $query->where($condicionDiasCobro);
        });

        $clientes->when($request->categoriaId && $request->categoriaId != 0, function ($q) use ($request) {
            $query = $q;

            $categoria = Categoria::select("*")
                ->where('estado', 1)
                ->where('id', $request->categoriaId)
                ->first();

            if (!$categoria) {
                return $query;
            }

            return $query->where('categoria_id', $categoria->id);
        });

        // ** Filtrado por rango de fechas 
        // $clientes->when($request->allDates && $request->allDates == "false", function ($q) use ($dateIni, $dateFin) {
        //     return $q->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
        // });

        // filtrados para campos numericos
        $clientes->when($request->filter && is_numeric($request->filter), function ($q) use ($request) {
            $query = $q;
            // id de recibos 
            $filterSinNumeral = str_replace("#", "", $request->filter);

            $query = $query->where('id', 'LIKE', '%' . $filterSinNumeral . '%');

            return $query;
        }); // Fin Filtrado


        // ** Filtrado para string
        $clientes->when($request->filter && !is_numeric($request->filter), function ($q) use ($request) {
            $query = $q;

            // nombre cliente y empresa
            // $query = $query->where('nombreCompleto', 'LIKE', '%' . $request->filter . '%',"or")
            //     ->where('nombreEmpresa', 'LIKE', '%' . $request->filter . '%',"or")
            //     ->where('direccion_casa', 'LIKE', '%' . $request->filter . '%',"or");
            $query = $query->where(
                [
                    ['nombreCompleto', 'LIKE', '%' . $request->filter . '%', "or"],
                    ['nombreEmpresa', 'LIKE', '%' . $request->filter . '%', "or"],
                    ['direccion_casa', 'LIKE', '%' . $request->filter . '%', "or"],
                ]
            );
            //     ->where('nombreEmpresa', 'LIKE', '%' . $request->filter . '%',"or")
            //     ->where('direccion_casa', 'LIKE', '%' . $request->filter . '%',"or");


            return $query;
        }); // Fin Filtrado por cliente

        if ($request->disablePaginate == 0) {
            $clientes = $clientes->orderBy('created_at', 'desc')->paginate(15);
        } else {
            $clientes = $clientes->get();
        }

        // dd(DB::getQueryLog());

        if (count($clientes) > 0) {
            foreach ($clientes as $cliente) {
                // dd($cliente->frecuencias);
                // validarStatusPagadoGlobal($cliente->id);
                $clientes->frecuencia = $cliente->frecuencia;
                $clientes->categoria = $cliente->categoria;
                $clientes->facturas = $cliente->facturas;
                $clientes->usuario = $cliente->usuario;

                $saldoCliente = calcularDeudaFacturasGlobal($cliente->id);

                if ($saldoCliente > 0) {
                    $cliente->saldo = number_format(-(float) $saldoCliente, 2);
                }

                if ($saldoCliente == 0) {
                    $cliente->saldo = $saldoCliente;
                }

                if ($saldoCliente < 0) {
                    // $cliente->saldo = number_format((float) str_replace("-", "", $saldoCliente), 2);
                    $saldo_sin_guion = str_replace("-", "", $saldoCliente);
                    $cliente->saldo = decimal(filter_var($saldo_sin_guion, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
                }
                // dd($cliente->saldo);
            }

            $response[] = $clientes;
        }

        $response = $clientes;


        return response()->json($response, $status);
    }

    public function ProductosList(Request $request)
    {
        // dd($request->all());
        $response = [];
        $status = 200;

        // DB::enableQueryLog();

        $Productos =  Producto::query();

        $Productos->when($request->estado, function ($q) use ($request) {
            return $q->where('estado', $request->estado);
        });

        // ** Filtrado para string
        $Productos->when($request->filter && !is_numeric($request->filter), function ($q) use ($request) {
            $query = $q;
            $query = $query->where(
                [
                    ['descripcion', 'LIKE', '%' . $request->filter . '%', "or"],
                ]
            );


            return $query;
        }); // Fin Filtrado por cliente

        // ** Filtrado para string
        $Productos->when($request->filter && is_numeric($request->filter), function ($q) use ($request) {
            $query = $q;

            $query = $query->where(
                [
                    ['id', 'LIKE', '%' . $request->filter, "or"],
                ]
            );
            return $query;
        }); // Fin Filtrado por cliente


        if ($request->disablePaginate == 0) {
            $Productos = $Productos->orderBy('created_at', 'desc')->paginate(15);
        } else {
            $Productos = $Productos->orderBy('created_at', 'desc')->get();
        }

        // dd(DB::getQueryLog());

        if (count($Productos) > 0) {
            $response = $Productos;
        }




        return response()->json($response, $status);
    }
}
