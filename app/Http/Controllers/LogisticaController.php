<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\ClientesReactivados;
use App\Models\Factura;
use App\Models\Frecuencia;
use App\Models\Producto;
use App\Models\Recibo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogisticaController extends Controller
{
    function carteraDate(Request $request)
    {

        $response = carteraQuery($request);
        return response()->json($response, 200);
    }

    // recuperacion
    function reciboDate(Request $request)
    {
        $response = $this->RecuperacionRecibosMensualQuery($request);
        return response()->json($response, 200);
    }

    function Mora30A60(Request $request)
    {
        $response = $this->mora30_60Query($request);
        return response()->json($response, 200);
    }

    function Mora60A90(Request $request)
    {
        $response = $this->mora60_90Query($request);
        return response()->json($response, 200);
    }


    function clienteDate(Request $request)
    {
        $response = $this->clienteNuevo($request);
        return response()->json($response, 200);
    }

    function clienteInactivo(Request $request)
    {
        $response = $this->clientesInactivosQuery($request);
        return response()->json($response, 200);
    }


    // recuperacion
    function incentivo(Request $request)
    {
        $response = $this->incentivosQuery($request);
        return response()->json($response, 200);
    }

    // recuperacion
    function incentivoSupervisor(Request $request)
    {
        $response = $this->incentivoSupervisorQuery($request);
        return response()->json($response, 200);
    }

    private function CalcularIncentivo($request, $userId)
    {
        $response = [
            'recibo' => [],
            'total_contado' => 0,
            'total_credito' => 0,
            'porcentaje5' => 0,
            'total' => 0,
        ];

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
        $reciboStore = Recibo::select("*")
            ->where('estado', 1)
            ->where('user_id', $userId);

        $recibo = $reciboStore->first();

        if ($recibo) {

            $recibo->user;

            //temporal
            $reciboHistorial = $recibo->recibo_historial()->where([
                ['estado', '=', 1],
            ])
                ->orderBy('created_at', 'desc');
            // print(count($reciboHistorial->get()));

            if (!$request->allDates) {
                $reciboHistorial = $reciboHistorial->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
            }

            if (!$request->allNumber) {
                if ($request->numRecibo != 0) {
                    $reciboHistorial = $reciboHistorial->where('numero', '>=', $request->numRecibo);
                }
            }

            $recibo->recibo_historial = $reciboHistorial->get();

            if (count($recibo->recibo_historial) > 0) {
                foreach ($recibo->recibo_historial as $recibo_historial) {
                    // print_r(json_encode($recibo_historial));

                    $recibo_historial->factura_historial = $recibo_historial->factura_historial()->where([
                        ['estado', '=', 1],
                    ])->first(); // traigo los abonos de facturas de tipo credito

                    $recibo_historial->factura_historial->cliente;
                    $recibo_historial->factura_historial->metodo_pago;

                    if ($recibo_historial->factura_historial->metodo_pago) {
                        $recibo_historial->factura_historial->metodo_pago->tipoPago = $recibo_historial->factura_historial->metodo_pago->getTipoPago();
                    }

                    if ($recibo_historial->factura_historial) {
                        $response["total_contado"] += $recibo_historial->factura_historial->precio;
                    }
                }
            }

            ///////////////// Contado (factura) /////////////////////////////

            $recibo_historial_contado = $recibo->recibo_historial_contado()->where([
                ['estado', '=', 1],
            ]);

            if (!$request->allDates) {
                $recibo_historial_contado = $recibo_historial_contado->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
            }

            if (!$request->allNumber) {
                if ($request->numRecibo != 0) {
                    $recibo_historial_contado = $recibo_historial_contado->where('numero', '>=', $request->numRecibo);
                }
            }

            if (!$request->allNumber) {
                if ($request->numDesde != 0 && $request->numHasta != 0) {
                    $recibo_historial_contado = $recibo_historial_contado->whereBetween('numero', [$request->numDesde, $request->numHasta]);
                } else if ($request->numDesde != 0) {
                    $recibo_historial_contado = $recibo_historial_contado->where('numero', '=', $request->numDesde);
                }
            }

            $recibo->recibo_historial_contado = $recibo_historial_contado->get();

            if (count($recibo->recibo_historial_contado) > 0) {
                foreach ($recibo->recibo_historial_contado as  $recibo_historial_contado) {
                    $recibo_historial_contado->factura = $recibo_historial_contado->factura()->where([
                        ['status', '=', 1],
                    ])->first(); // traigo las facturas contado //monto

                    $recibo_historial_contado->factura->cliente;

                    if ($recibo_historial_contado->factura) {
                        $response["total_credito"] += $recibo_historial_contado->factura->monto;
                    }
                }
            }

            $response["total_credito"] = number_format($response["total_credito"], 2, ".", "");
            $response["total_contado"] = number_format($response["total_contado"], 2, ".", "");
            $response["total"]         = number_format($response["total_contado"] + $response["total_credito"], 2, ".", "");
            $response["porcentaje5"]  = number_format($response["total"] * 0.05, 2, ".", "");

            $response["recibo"]        = $recibo;
        }

        return $response;
    }

    function estadoCuenta(Request $request)
    {
        $response = queryEstadoCuenta($request->cliente_id);
        $response["cliente"] = Cliente::find($request->cliente_id);

        return response()->json($response, 200);
    }

    function incentivoSupervisorQuery(Request $request)
    {
        $response = ["dataVendedores" => []];
        $users = User::where([
            ["estado", "=", 1]
        ])->get();

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

        $sumaRecuperacion = 0;
        $sumaFactura = 0;
        foreach ($users as $user) {
            // 20 => "Alejandro"
            // 21 => "Rigoberto"
            // 23 => "Ronald"
            // 24 => "Marileth de los Angeles"
            // 25 => "Mari Laura"
            // 26 => "Mario josue"
            // 27 => "Danilo Marcelino"
            // 28 => "Ivan del Socorro"
            // 29 => "Alexander Julio"
            // 30 => "Dennis Octavio"
            // 31 => "José Heriberto"
            // 32 => "Kevin Francisco"

            if (!in_array($user->id, [20, 21, 23, 25, 32])) {
                $responsequery = $this->CalcularIncentivo($request, $user->id);
                $sumaRecuperacion += (float) $responsequery['porcentaje5'];

                $dataVendedor = [];
                $dataVendedor["nombreCompleto"]  = "$user->name $user->apellido";
                $dataVendedor["idUser"] = $user->id;
                $dataVendedor["porcentajeRecuperacion5"] = (float) $responsequery['porcentaje5'];
                $dataVendedor["totalRecuperacion"] = (float) $responsequery['total'];

                // array_push($response, $responsequery);

                $facturasStorage = Factura::select("*")
                    ->where('user_id', $user->id)
                    ->where('status', 1);

                if (!$request->allDates) {
                    $facturasStorage = $facturasStorage->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
                }

                $facturas = $facturasStorage->get();

                if (count($facturas) > 0) {
                    $totalFacturas = 0;
                    foreach ($facturas as $factura) {
                        $totalFacturas += (float) number_format((float) ($factura->monto), 2, ".", "");
                    }

                    $dataVendedor["totalFacturaVendedor"]  = (float) number_format($totalFacturas, 2, ".", "");
                    $sumaFactura += $dataVendedor["totalFacturaVendedor"];
                } else {
                    $dataVendedor["totalFacturaVendedor"]  = 0;
                }
                array_push($response["dataVendedores"], $dataVendedor);
            }
        }

        $response["totalRecuperacionVendedores"] = (float) number_format($sumaRecuperacion, 2, ".", "");
        $response["totalFacturaVendedores"] = (float) number_format($sumaFactura, 2, ".", "");
        $response["totalFacturaVendedores2Porciento"] = (float) number_format($response["totalFacturaVendedores"] * 0.02, 2, ".", "");

        // $response["totalFacturas"] = number_format($totalFacturas, 2, ".", "");
        // $response["totalFacturasX02"] = number_format($totalFacturas * 0.02, 2, ".", "");;
        // $response["factura"] = $facturas;

        return $response;
    }

    function productoLogistica(Request $request)
    {
        $response = [
            'productos' => 0,
            'monto_total' => 0,
        ];

        $productos =  Producto::where('estado', 1)->get();

        if (count($productos) > 0) {
            foreach ($productos as $producto) {
                $precio = number_format((float) ($producto->precio * $producto->stock), 2, ".", "");
                $response["productos"] += $producto->stock;
                $response["monto_total"] += $precio;
            }
        }

        return response()->json($response, 200);
    }

    function clientesReactivados(Request $request)
    {
        $response = $this->clientesReactivadosQuery($request);
        return response()->json($response, 200);
    }


    function ventasDate(Request $request)
    {

        $response = ventasMetaQuery($request);
        return response()->json($response, 200);
    }

    function recuperacion(Request $request)
    {
        $response = [];
        $users = User::where([
            ["estado", "=", 1]
        ])->whereNotIn('id', [32])->get();

        // dd([$request->dateIni,$request->dateFin]);
        foreach ($users as $user) {
            // $user->meta;
            // $responsequery = recuperacionQuery($user);
            $responsequery = newrecuperacionQuery($user, $request->dateIni, $request->dateFin);
            array_push($response, $responsequery);
        }
        return response()->json($response, 200);
    }

    function productosVendidos(Request $request)
    {
        $response = [];
        $users = User::where([
            ["estado", "=", 1]
        ])->get();
        // $users = Recibo::where([
        //     ["estado","=",1]
        // ])->get();

        foreach ($users as $user) {
            // dd($user->id);
            // $user->meta;
            // $responsequery = recuperacionQuery($user);
            $responsequery = productosVendidosPorUsuario($user, $request);

            array_push($response, $responsequery);
        }
        return response()->json($response, 200);
    }

    function clientesReactivadosQuery(Request $request)
    {
        $response = [];

        $userId = $request['userId'];

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


        $reactivosStorage = ClientesReactivados::select("*")->where('estado', 1);

        if ($userId != 0) {
            $reactivosStorage = $reactivosStorage->where('user_id', $userId);
        }

        if (!$request->allDates) {
            $reactivosStorage = $reactivosStorage->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
        }

        $reactivos = $reactivosStorage->get();



        if (count($reactivos) > 0) {
            foreach ($reactivos as $reactivo) {

                $reactivo->cliente;
                $reactivo->user;
                $reactivo->factura;
            }

            $response = $reactivos;
        }

        return $response;
    }

    function clientesInactivosQuery(Request $request)
    {
        $response = [];

        // if(empty($request->dateIni)){
        //     $dateIni = Carbon::now();
        // }else{
        //     $dateIni = Carbon::parse($request->dateIni);
        // }

        // if(empty($request->dateFin)){
        //     $dateFin = Carbon::now();
        // }else{
        //     $dateFin = Carbon::parse($request->dateFin);
        // }

        // DB::enableQueryLog();

        $query = "SELECT
            c.*,
            q.cantidad_factura,
            q.cantidad_finalizadas,
            q.last_date_finalizada,
            if(q.cantidad_factura = q.cantidad_finalizadas, 1, 0) AS cliente_inactivo
            FROM clientes c
            INNER JOIN (
                SELECT
                    c.id AS cliente_id,
                    c.user_id AS user_id,
                    COUNT(c.id) AS cantidad_factura,
                    MAX(f.status_pagado_at) AS last_date_finalizada,
                    SUM(if(f.status_pagado = 1, 1, 0)) AS cantidad_finalizadas
                FROM clientes c
                INNER JOIN facturas f ON c.id = f.cliente_id
                WHERE  f.`status` = 1
                GROUP BY c.id
                ORDER BY c.id ASC
            )q ON c.id = q.cliente_id
            WHERE
                q.cantidad_factura = q.cantidad_finalizadas AND
                TIMESTAMPDIFF(MONTH,last_date_finalizada, NOW()) >= 1
        ";

        if ($request->userId != 0) {
            $query = $query . " AND c.user_id = " . $request->userId;
        }

        $clientes = DB::select($query);

        if (count($clientes) > 0) {
            foreach ($clientes as $cliente) {
                $cliente->frecuencia = Frecuencia::find($cliente->frecuencia_id);
                $cliente->categoria = Categoria::find($cliente->categoria_id);
                $cliente->user = User::find($cliente->user_id);
            }

            $response = $clientes;
        }

        // print_r(count($cliente));
        return $response;
    }

    function incentivosQuery(Request $request)
    {
        $response = [
            'recibo' => [],
            'total_contado' => 0,
            'total_credito' => 0,
            'porcentaje20' => 0,
            'total' => 0,
        ];

        $userId = $request['userId'];
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
        $reciboStore = Recibo::select("*")
            ->where('estado', 1)
            ->where('user_id', $userId);

        $recibo = $reciboStore->first();

        // $query = DB::getQueryLog();
        // print_r(json_encode($recibos));

        if ($recibo) {

            $recibo->user;

            //temporal
            $reciboHistorial = $recibo->recibo_historial()->where([
                ['estado', '=', 1],
            ])
                ->orderBy('created_at', 'desc');
            // print(count($reciboHistorial->get()));

            if (!$request->allDates) {
                $reciboHistorial = $reciboHistorial->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
            }

            if (!$request->allNumber) {
                if ($request->numRecibo != 0) {
                    $reciboHistorial = $reciboHistorial->where('numero', '>=', $request->numRecibo);
                }
            }

            $recibo->recibo_historial = $reciboHistorial->get();

            if (count($recibo->recibo_historial) > 0) {
                foreach ($recibo->recibo_historial as $recibo_historial) {
                    // print_r(json_encode($recibo_historial));

                    $recibo_historial->factura_historial = $recibo_historial->factura_historial()->where([
                        ['estado', '=', 1],
                    ])->first(); // traigo los abonos de facturas de tipo credito

                    $recibo_historial->factura_historial->cliente;
                    $recibo_historial->factura_historial->metodo_pago;

                    if ($recibo_historial->factura_historial->metodo_pago) {
                        $recibo_historial->factura_historial->metodo_pago->tipoPago = $recibo_historial->factura_historial->metodo_pago->getTipoPago();
                    }

                    if ($recibo_historial->factura_historial) {
                        $response["total_contado"] += $recibo_historial->factura_historial->precio;
                    }
                }
            }

            ///////////////// Contado (factura) /////////////////////////////

            $recibo_historial_contado = $recibo->recibo_historial_contado()->where([
                ['estado', '=', 1],
            ]);

            if (!$request->allDates) {
                $recibo_historial_contado = $recibo_historial_contado->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
            }

            if (!$request->allNumber) {
                if ($request->numRecibo != 0) {
                    $recibo_historial_contado = $recibo_historial_contado->where('numero', '>=', $request->numRecibo);
                }
            }

            if (!$request->allNumber) {
                if ($request->numDesde != 0 && $request->numHasta != 0) {
                    $recibo_historial_contado = $recibo_historial_contado->whereBetween('numero', [$request->numDesde, $request->numHasta]);
                } else if ($request->numDesde != 0) {
                    $recibo_historial_contado = $recibo_historial_contado->where('numero', '=', $request->numDesde);
                }
            }

            $recibo->recibo_historial_contado = $recibo_historial_contado->get();

            if (count($recibo->recibo_historial_contado) > 0) {
                foreach ($recibo->recibo_historial_contado as  $recibo_historial_contado) {
                    $recibo_historial_contado->factura = $recibo_historial_contado->factura()->where([
                        ['status', '=', 1],
                    ])->first(); // traigo las facturas contado //monto

                    $recibo_historial_contado->factura->cliente;

                    if ($recibo_historial_contado->factura) {
                        $response["total_credito"] += $recibo_historial_contado->factura->monto;
                    }
                }
            }



            $response["total_credito"] = number_format($response["total_credito"], 2, ".", "");
            $response["total_contado"] = number_format($response["total_contado"], 2, ".", "");
            $response["total"]         = number_format($response["total_contado"] + $response["total_credito"], 2, ".", "");
            $response["porcentaje20"]  = number_format($response["total"] * 0.20, 2, ".", "");

            $response["recibo"]        = $recibo;
        }
        return $response;
    }

    function clienteNuevo(Request $request)
    {
        $response = [];

        // $userId = $request['userId'];
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
        $clienteStore = Cliente::select("*")->where('estado', 1);

        if (!$request->allDates) {
            $clienteStore = $clienteStore->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
        }

        if ($request->userId != 0) {
            $clienteStore = $clienteStore->where('user_id', $request->userId);
        }

        $clientes_id = [];
        $facturasQuery = Factura::where([
            ['status', '=', 1],
        ])->groupBy('cliente_id')->select("cliente_id")->get();

        if (count($facturasQuery) > 0) {
            foreach ($facturasQuery as $factura) {
                $clientes_id[] = $factura->cliente_id;
            }
        }

        // print_r($clientes_id);

        $clientes = $clienteStore->wherein('id', $clientes_id)->get();

        // $query = DB::getQueryLog();
        // print_r(json_encode($query));
        if (count($clientes) > 0) {
            foreach ($clientes as $cliente) {
                $cliente->frecuencia = $cliente->frecuencia;
                $cliente->categoria = $cliente->categoria;
                $cliente->facturas = $cliente->facturas;
            }

            $response = $clientes;
        }


        return $response;
    }

    function mora60_90Query(Request $request)
    {
        $response = [
            'factura' => [],
        ];

        $userId = $request['userId'];
        $fechaActual = Carbon::now();

        if ($request->allUsers) {

            $facturas = Factura::select("*")
                ->where('status_pagado', 0)
                ->where('status', 1)
                ->get();
        } else {
            $facturas = Factura::select("*")
                ->where('status_pagado', 0)
                ->where('user_id', $userId)
                ->where('status', 1)
                ->get();
        }

        // $query = DB::getQueryLog();
        // dd($query);

        if (count($facturas) > 0) {

            foreach ($facturas as $factura) {

                // $fechaPasado60DiasVencimiento = Carbon::parse($factura->fecha_vencimiento)->addDays(60)->toDateTimeString();
                // $fechaPasado90DiasVencimiento = Carbon::parse($factura->fecha_vencimiento)->addDays(90)->toDateTimeString();
                $fechaPasado60DiasVencimiento = Carbon::parse($factura->created_at)->addDays(60)->toDateTimeString();
                $fechaPasado90DiasVencimiento = Carbon::parse($factura->created_at)->addDays(90)->toDateTimeString();

                // if ($fechaActual->gte($fechaPasado60DiasVencimiento) && $fechaActual->lte($fechaPasado90DiasVencimiento)) {
                if (Carbon::parse($fechaPasado60DiasVencimiento)->diffInDays($fechaActual) >= 60) {
                    $factura->user;
                    $factura->cliente;
                    $factura->vencimiento60  = $fechaPasado60DiasVencimiento;
                    $factura->vencimiento90  = $fechaPasado90DiasVencimiento;

                    // $factura->diferenciaDias = Carbon::parse($factura->fecha_vencimiento)->diffInDays($fechaActual);
                    $factura->diferenciaDias = Carbon::parse($factura->created_at)->diffInDays($fechaActual);

                    array_push($response["factura"], $factura);
                }
            }
        }

        return $response;
    }

    function mora30_60Query(Request $request)
    {
        $response = [
            'factura' => [],
        ];
        $userId = $request['userId'];
        $fechaActual = Carbon::now();

        if ($request->allUsers) {

            $facturas = Factura::select("*")
                ->where('status_pagado', 0)
                ->where('status', 1)
                ->get();
        } else {
            $facturas = Factura::select("*")
                ->where('status_pagado', 0)
                ->where('user_id', $userId)
                ->where('status', 1)
                ->get();
        }

        // $query = DB::getQueryLog();
        // dd($query);

        if (count($facturas) > 0) {

            foreach ($facturas as $factura) {
                // $fechaPasado30DiasVencimiento = Carbon::parse($factura->fecha_vencimiento)->addDays(30)->toDateTimeString();
                // $fechaPasado60DiasVencimiento = Carbon::parse($factura->fecha_vencimiento)->addDays(60)->toDateTimeString();
                $fechaPasado30DiasVencimiento = Carbon::parse($factura->created_at)->addDays(30)->toDateTimeString();
                $fechaPasado60DiasVencimiento = Carbon::parse($factura->created_at)->addDays(60)->toDateTimeString();

                if ($fechaActual->gte($fechaPasado30DiasVencimiento) && $fechaActual->lte($fechaPasado60DiasVencimiento)) {
                    $factura->user;
                    $factura->cliente;
                    $factura->vencimiento30 = $fechaPasado30DiasVencimiento;
                    $factura->vencimiento60 = $fechaPasado60DiasVencimiento;

                    // $factura->diferenciaDias = Carbon::parse($factura->fecha_vencimiento)->diffInDays($fechaActual);
                    $factura->diferenciaDias = Carbon::parse($factura->created_at)->diffInDays($fechaActual);

                    array_push($response["factura"], $factura);
                }
            }
        }

        return $response;
    }

    function RecuperacionRecibosMensualQuery($request)
    { // Esta estaba en dashboard como recuperacion

        $response = [
            'recibo' => [],
            'total_contado' => 0,
            'total_credito' => 0,
            'total' => 0,
        ];

        $userId = $request['userId'];
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
        $reciboStore = Recibo::select("*")
            ->where('estado', 1)
            ->where('user_id', $userId);

        $recibo = $reciboStore->first();

        // $query = DB::getQueryLog();
        // print_r(json_encode($recibos));

        if ($recibo) {

            $recibo->user;

            //temporal
            $reciboHistorial = $recibo->recibo_historial()->where([
                ['estado', '=', 1],
            ])
                ->orderBy('created_at', 'desc');
            // print(count($reciboHistorial->get()));

            if (!$request->allDates) {
                $reciboHistorial = $reciboHistorial->whereBetween('created_at', [$dateIni->toDateString(),  $dateFin->toDateString()]);
            }

            if (!$request->allNumber) {
                if ($request->numRecibo != 0) {
                    $reciboHistorial = $reciboHistorial->where('numero', '>=', $request->numRecibo);
                }
            }

            $recibo->recibo_historial = $reciboHistorial->get();

            if (count($recibo->recibo_historial) > 0) {
                foreach ($recibo->recibo_historial as $recibo_historial) {
                    // print_r(json_encode($recibo_historial));

                    $recibo_historial->factura_historial = $recibo_historial->factura_historial()->where([
                        ['estado', '=', 1],
                    ])->first(); // traigo los abonos de facturas de tipo credito

                    $recibo_historial->factura_historial->cliente;
                    $recibo_historial->factura_historial->metodo_pago;

                    $recibo_historial->factura_historial->precio_cambio = convertTazaCambio($recibo_historial->factura_historial->precio);;
                    $saldoCliente = calcularDeudaFacturasGlobal($recibo_historial->factura_historial->cliente->id);

                    if ($saldoCliente > 0) {
                        $recibo_historial->saldo_cliente = number_format(-(float) $saldoCliente, 2);
                    }

                    if ($saldoCliente == 0) {
                        $recibo_historial->saldo_cliente = $saldoCliente;
                    }

                    if ($saldoCliente < 0) {
                        // $recibo_historial->saldo_cliente = number_format((float) str_replace("-", "", $saldoCliente), 2);

                        $saldo_sin_guion = str_replace("-", "", $saldoCliente);
                        $recibo_historial->saldo_cliente = decimal(filter_var($saldo_sin_guion, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
                    }

                    if ($recibo_historial->factura_historial->metodo_pago) {
                        $recibo_historial->factura_historial->metodo_pago->tipoPago = $recibo_historial->factura_historial->metodo_pago->getTipoPago();
                    }

                    if ($recibo_historial->factura_historial) {
                        $response["total_contado"] += $recibo_historial->factura_historial->precio;
                    }
                }
            }

            ///////////////// Contado (factura) /////////////////////////////

            $recibo_historial_contado = $recibo->recibo_historial_contado()->where([
                ['estado', '=', 1],
            ]);

            if (!$request->allDates) {
                $recibo_historial_contado = $recibo_historial_contado->whereBetween('created_at', [$dateIni->toDateString(),  $dateFin->toDateString()]);
            }

            if (!$request->allNumber) {
                if ($request->numRecibo != 0) {
                    $recibo_historial_contado = $recibo_historial_contado->where('numero', '>=', $request->numRecibo);
                }
            }

            if (!$request->allNumber) {
                if ($request->numDesde != 0 && $request->numHasta != 0) {
                    $recibo_historial_contado = $recibo_historial_contado->whereBetween('numero', [$request->numDesde, $request->numHasta]);
                } else if ($request->numDesde != 0) {
                    $recibo_historial_contado = $recibo_historial_contado->where('numero', '=', $request->numDesde);
                }
            }

            $recibo->recibo_historial_contado = $recibo_historial_contado->get();

            if (count($recibo->recibo_historial_contado) > 0) {
                foreach ($recibo->recibo_historial_contado as  $recibo_historial_contado) {
                    $recibo_historial_contado->factura = $recibo_historial_contado->factura()->where([
                        ['status', '=', 1],
                    ])->first(); // traigo las facturas contado //monto

                    $recibo_historial_contado->factura->cliente;

                    if ($recibo_historial_contado->factura) {
                        $response["total_credito"] += $recibo_historial_contado->factura->monto;
                    }
                }
            }



            $response["total_credito"] = number_format($response["total_credito"], 2, ".", "");
            $response["total_contado"] = number_format($response["total_contado"], 2, ".", "");
            $response["total"]         = number_format($response["total_contado"] + $response["total_credito"], 2, ".", "");
            $response["recibo"]        = $recibo;
        }

        return $response;
    }

    function resumenDashboard(Request $request)
    {
        $response = [];

        // {
        //     "dateIni": "2023-08-01",
        //     "dateFin": "2023-08-31",
        //     "userId": 25,
        //     "numRecibo": null
        // }
        $user = User::where([
            ["estado", "=", 1],
            ["id", "=", $request->userId]
        ])->first();


        $response["recuperacionMensual"] = newrecuperacionQuery($user, $request->dateIni, $request->dateFin);
        $response["cartera"] = carteraQuery($request);
        $response["recuperacion"] = $this->RecuperacionRecibosMensualQuery($request);
        $response["mora30_60"] = $this->mora30_60Query($request);
        $response["mora60_90"] = $this->mora60_90Query($request);
        $response["clientesNuevos"] = $this->clienteNuevo($request);
        $response["incentivos"] = $this->incentivosQuery($request);
        $response["incentivosSupervisor"] = $this->incentivoSupervisorQuery($request);
        $response["clientesInactivos"] = $this->clientesInactivosQuery($request);
        $response["clientesReactivados"] = $this->clientesReactivadosQuery($request);
        $response["ventasMeta"] = ventasMetaQuery($request);
        $response["productosVendidos"] = productosVendidosPorUsuario($user, $request);

        return response()->json($response, 200);
    }
}
