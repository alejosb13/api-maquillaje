<?php

use App\Http\Controllers\Traits\DevolucionesSupervisorTrait;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\ClientesInactivosNotas;
use App\Models\ClientesReactivados;
use App\Models\CostosVentas;
use App\Models\Factura;
use App\Models\Factura_Detalle;
use App\Models\FacturaHistorial;
use App\Models\Frecuencia;
use App\Models\Gasto;
use App\Models\InversionDetail;
use App\Models\Meta;
use App\Models\MetaHistorial;
use App\Models\MetaRecuperacion;
use App\Models\Producto;
use App\Models\Recibo;
use App\Models\TazaCambio;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

function validarStatusPagadoGlobal($clienteID)
{

    $cliente = Cliente::find($clienteID);

    $cliente->factura = $cliente->facturas()->where([
        ['status', '=', 1],
        ['tipo_venta', '=', 1]  // 1 = Credito | 2 = Contado,
        // ['status_pagado', '=', 0] // 0 = en proceso | 1 = Finalizado,
    ])->get();

    $cliente->factura_historial = $cliente->factura_historial()->where([
        ['estado', '=', 1],
        // ['debitado', '=', 0] // 0 = aun no usado el abono | 1 = ya se uso el abono,
    ])->get();

    $totalAbonos = 0;
    if (count($cliente->factura_historial) > 0) {
        foreach ($cliente->factura_historial as $itemHistorial) {
            $totalAbonos += $itemHistorial["precio"];

            $itemHistorial["debitado"] = 1; // coloco como debitado los abonos que ya fuy sumando al acumulador
            $itemHistorial->update();
        }
    }

    if (count($cliente->factura) > 0) {
        $tieneSaldo = TRUE; // Bandera para saber cuando debo de dejar ajustar el calculo de saldo restante de las facturas

        foreach ($cliente->factura as $factura) {
            $fechaPago = is_null($factura["status_pagado_at"]) ? Carbon::now() : $factura["status_pagado_at"];

            if ($tieneSaldo) {
                // print_r (json_encode( ["monto" => $factura["monto"], "totalAbonos"=>$totalAbonos ]));
                // print_r (json_encode( ["totalAbonos" => decimal($totalAbonos) ]));
                $totalAbonos =  decimal($totalAbonos -  $factura["monto"]);
                // print_r (json_encode( ["monto" => $factura["monto"], "totalAbonos"=>$totalAbonos ]));
                if ($totalAbonos < 0) { // si el precio es mas alto que el total de abonos (dejo la factura abierta y ajusto el saldo_restante)
                    $tieneSaldo = FALSE;
                    $factura["status_pagado"] = 0;
                    $factura["saldo_restante"] = abs($totalAbonos);
                    $factura["status_pagado_at"] = null;
                } else { // cierro la factura y el saldo restante lo dejo 0
                    $factura["status_pagado"] = 1;
                    $factura["saldo_restante"] = 0;
                    $factura["status_pagado_at"] = $fechaPago;
                }
            } else { // si no tiene saldo reinicio la factura
                $factura["saldo_restante"] = $factura["monto"];
                $factura["status_pagado"] = 0;
                $factura["status_pagado_at"] = null;
            }

            if ($factura["monto"] == 0 && $factura["saldo_restante"] == 0) {
                $factura["status_pagado"] = 1;
                $factura["status_pagado_at"] = $fechaPago;
            }
            // print_r (json_encode( ["monto" => $factura ]));

            $factura->update();
        }
    }

    // print_r (json_encode( ["cliente" => $cliente, "totalAbonos"=>$totalAbonos ]));

}

function debitarAbonosClientes($clienteID)
{
    $cliente = Cliente::find($clienteID);
    // $cliente = Cliente::find($clienteID);
    // $cliente = $abono->cliente;

    $cliente->factura = $cliente->facturas()->where([
        ['status', '=', 1],
        ['status_pagado', '=', 0] // 0 = en proceso | 1 = Finalizado,
    ])->get();

    $cliente->factura_historial = $cliente->factura_historial()->where([
        ['estado', '=', 1],
        ['debitado', '=', 0] // 0 = aun no usado el abono | 1 = ya se uso el abono,
    ])->get();

    // print_r(json_encode($cliente->factura_historial));
    $totalAbonos = 0;
    if (count($cliente->factura_historial) > 0) {
        foreach ($cliente->factura_historial as $itemHistorial) {
            $totalAbonos += $itemHistorial["precio"];

            $itemHistorial["debitado"] = 1; // coloco como debitado los abonos que ya fuy sumando al acumulador
            $itemHistorial->update();
        }
    }

    if (count($cliente->factura) > 0) {
        $tieneSaldo = TRUE; // Bandera para saber cuando debo de dejar ajustar el calculo de saldo restante de las facturas

        foreach ($cliente->factura as $factura) {
            $fechaPago = is_null($factura["status_pagado_at"]) ? Carbon::now() : $factura["status_pagado_at"];
            if ($tieneSaldo) {
                // 200 - 500 = -300 ajusta el restante
                // 500 - 500 = 0  cierra factura y ajusta restante
                $totalAbonos =  $totalAbonos - $factura["saldo_restante"];

                if ($totalAbonos < 0) { // si el precio es mas alto que el total de abonos (dejo la factura abierta y ajusto el saldo_restante)
                    $tieneSaldo = FALSE;
                    $factura["saldo_restante"] = abs($totalAbonos);
                } else { // cierro la factura y el saldo restante lo dejo 0
                    $factura["saldo_restante"] = 0;
                    $factura["status_pagado"] = 1;
                    $factura["status_pagado_at"] = $fechaPago;
                }
            }

            $factura->update();
        }
    }

    // print_r (json_encode( ["cliente" => $cliente->factura, "totalAbonos"=>$totalAbonos ]));

}

function calcularDeudaFacturaCliente($clienteID)
{
    $cliente = Cliente::find($clienteID);
    $data = array(
        "saldo_restante" => 0,
        "totalFactura" => 0
    );

    $cliente->factura = $cliente->facturas()->where([
        ['status', '=', 1], //1 Activo | 0 Inactivo
        ['tipo_venta', '=', 1], // 1 = Credito | 2 = Contado,
        ['status_pagado', '=', 0] // 0 = en proceso | 1 = Finalizado,
    ])->get();

    // print_r (count($cliente->factura));
    if (count($cliente->factura) > 0) {

        $totalFactura = 0;
        $saldoRestanteFactura = 0;

        foreach ($cliente->factura as $factura) {
            $saldoRestanteFactura += $factura["saldo_restante"];
            $totalFactura += $factura["monto"];
        }

        $data["saldo_restante"] = $saldoRestanteFactura;
        $data["totalFactura"] = $totalFactura;
    }


    return $data;
}

function calcularDeudaFacturasGlobal($clienteID)
{
    $cliente = Cliente::find($clienteID);

    $cliente->factura = $cliente->facturas()->where([
        ['status', '=', 1],
        ['tipo_venta', '=', 1]  // 1 = Credito | 2 = Contado,la comente porque hay casos que tienen factura contado
        // ['status_pagado', '=', 0] // 0 = en proceso | 1 = Finalizado,
    ])->get();

    $cliente->factura_historial = $cliente->factura_historial()->where([
        ['estado', '=', 1],
        // ['debitado', '=', 0] // 0 = aun no usado el abono | 1 = ya se uso el abono,
    ])->get();

    $totalAbonos = 0;
    if (count($cliente->factura_historial) > 0) {
        foreach ($cliente->factura_historial as $itemHistorial) {
            $totalAbonos += $itemHistorial["precio"];
        }
    }

    if (count($cliente->factura) > 0) {
        foreach ($cliente->factura as $factura) {
            $totalAbonos =  $totalAbonos - $factura["monto"];
            // print_r("abono ".$totalAbonos);
            // print_r(" Factura  ".$factura["monto"]);
        }
    }

    return number_format($totalAbonos, 2);
}

function actualizarCantidadDetalleProducto($detalleID, $cantidad)
{
    $detalle = Factura_Detalle::find($detalleID);

    if ($detalle) {
        if (($detalle->cantidad - $cantidad) <= 0) {
            $detalle->cantidad = 0;  // si la cantidad es menor o igual a 0 la pongo en 0
            $detalle->estado = 0;    // si la cantidad es menor o igual a 0 desactivo el producto de la factura
        } else {
            $detalle->cantidad = $detalle->cantidad - $cantidad;
            // $total +=  $productoDetalle["precio_unidad"] * $productoDetalle["cantidad"];
            $detalle->precio = $detalle["precio_unidad"] * $detalle->cantidad;
        }

        $detalle->update();

        ActualizarPrecioFactura($detalle->factura_id);
        return true;
    }



    return false;
}


function ActualizarPrecioFactura($factura_id)
{
    $factura = Factura::find($factura_id);
    $factura_detalle = $factura->factura_detalle()->where([
        ['estado', '=', 1],
    ])->get();

    if (count($factura_detalle) > 0) {
        $total = 0;

        foreach ($factura_detalle as $productoDetalle) {
            // $total +=  $productoDetalle["precio_unidad"] * $productoDetalle["cantidad"];
            $total +=  $productoDetalle["precio"];
        }

        $factura->monto = $total;
        $factura->saldo_restante = $total;
    } else { // NO TIENES PRODUCTOS ACTIVOS EN LA FACTURA
        // $factura->monto = 0;
        // $factura->saldo_restante = 0;

        // desactivo la factura para que no se tome en cuenta
        $factura->status = 0;
        validarFacturaClienteReactivado($factura_id);
    }

    // print_r (json_encode($factura));

    $factura->update();

    validarStatusPagadoGlobal($factura->cliente_id);
}

function validarFacturaClienteReactivado($factura_id)
{
    ClientesReactivados::where([
        ['estado', '=', 1],
        ['factura_id', '=', $factura_id],
    ])->update(['estado' => 0]);
}



function AsignarPrecioPorUnidadGlobal()
{
    $facturas = Factura::all();

    foreach ($facturas  as $key => $factura) {
        $factura->factura_detalle = $factura->factura_detalle()->where([
            ['estado', '=', 1],
        ])->get();
        if (count($factura->factura_detalle) > 0) {
            $precio_unidad = 0;

            foreach ($factura->factura_detalle as $productoDetalle) {

                $precio_unidad =  $productoDetalle["precio"] / $productoDetalle["cantidad"];
                $precio_unidad_format = number_format($precio_unidad, 2);

                $productoDetalle["precio_unidad"] = $precio_unidad_format;
                $productoDetalle->update();
            }
            // $factura->precio_unidad = $precio_unidad;

            ActualizarPrecioFactura($factura->id);
            // validarStatusPagadoGlobal($factura->cliente_id);

        }
    }
}

function devolverStockProducto($detalle_id, $cantidad)
{
    // print_r(json_encode($detalle_id));
    $detalle = Factura_Detalle::where("id", $detalle_id)->first();
    if ($detalle) {
        $producto = Producto::where("id", $detalle->producto_id)->first();
        // print_r(json_encode($producto));
        $producto->stock = $producto->stock + $cantidad;
        $producto->estado = 1;
        $producto->update();

        if (count($detalle->regaloFacturado) > 0) {
            foreach ($detalle->regaloFacturado as $regaloF) {
                // agrego la relacion con la tabla producto para regalo
                $regaloF->regalo;

                // producto para regalo
                $regalo = Producto::where("id", $regaloF->regalo->id_producto_regalo)->first();
                // print_r(json_encode($producto));
                $regalo->stock = $regalo->stock + ($regaloF->regalo->cantidad * $cantidad);
                $regalo->estado = 1;
                $regalo->update();

                // regalo facturado 
                $regaloF->cantidad_regalada = $regaloF->cantidad_regalada - ($regaloF->regalo->cantidad * $cantidad);
                $regaloF->update();
            }
        }

        // print_r(json_encode($producto));

        return true;
    }

    return false;
}

function queryEstadoCuenta($cliente_id)
{
    $response = [
        "estado_cuenta" => [],
        "facturas" => [],
        "abonos" => [],
    ];

    if (is_numeric($cliente_id)) {
        $query = "SELECT
                *
            FROM (
                SELECT
                    c.id AS cliente_id,
                    CONCAT('PED-',f.id ) AS `numero_documento`,
                    'Pedido' as tipo_documento,
                    f.created_at AS fecha,
                    f.fecha_vencimiento AS f_vencimiento,
                    f.monto AS credito,
                    '' AS abono,
                    '--' AS autorizacion,                    
                    f.status_pagado_at                      
                FROM clientes c
                INNER JOIN facturas f ON f.cliente_id = c.id
                WHERE
                    f.`status` = 1 AND
                    f.tipo_venta = 1
            UNION ALL
                SELECT
                    c.id AS cliente_id,
                    CONCAT('REC-', rh.numero ) AS `numero_documento`,
                    'Recibo' as tipo_documento,
                    rh.created_at AS fecha,
                    rh.created_at AS f_vencimiento,
                    '' AS credito,
                    fh.precio AS abono,
					CASE 
                        WHEN mp.tipo = 2 THEN CONCAT('tf-', COALESCE(mp.autorizacion, '--'))
                        WHEN mp.tipo = 3 THEN CONCAT('tc-', COALESCE(mp.autorizacion, '--'))
						ELSE CONCAT('efvo', COALESCE(mp.autorizacion, '--'))
					END AS `autorizacion`,
                    '' as status_pagado_at            
                FROM	clientes c
                INNER JOIN factura_historials fh ON fh.cliente_id = c.id
                INNER JOIN recibo_historials rh ON rh.factura_historial_id = fh.id
                LEFT JOIN metodo_pagos mp ON mp.factura_historial_id = fh.id
                WHERE
                    fh.`estado` = 1
            ) estado_cuenta
            WHERE estado_cuenta.cliente_id = $cliente_id
            ORDER BY estado_cuenta.fecha ASC
        ";

        // if($request->userId != 0){
        //     $query = $query." AND c.user_id = ".$request->userId;
        // }

        $estadoCuenta = DB::select($query);

        if (count($estadoCuenta) > 0) {
            $saldo = 0;
            foreach ($estadoCuenta as $operacion) {
                // if(!isset($operacion->saldo)) $operacion->saldo = 0;
                $saldo = ($operacion->credito != "") ? decimal($operacion->credito) + decimal($saldo)   : decimal($saldo) - decimal($operacion->abono);

                $operacion->saldo = $saldo;
                // print_r(intval($operacion->credito) + $operacion->saldo ."<br>");

                if ($operacion->credito != "") {
                    array_push($response["facturas"], $operacion);
                } else {
                    array_push($response["abonos"], $operacion);
                }

                $response["estado_cuenta"] = $estadoCuenta;
            }
        }
    }

    return $response;
}

function validarReactivacionCliente($user_id, $cliente_id, $factura_id, $dataClienteInactivo)
{

    // print_r($listaInactivos);
    if (count($dataClienteInactivo) > 0) { // si existe en la lista de clientes inactivos registro el dia que se reactivo

        ClientesReactivados::create([
            'user_id'         => $user_id,
            'cliente_id' => $cliente_id,
            'factura_id' => $factura_id,
            'estado' => 1,

        ]); // inserto registro de reactivacion de cliente

    }
}

function carteraQuery($request)
{
    $response = [
        'factura' => [],
        'total' => 0,
        'recuperacion' => 0,
        'abonos' => [],
    ];

    $userId = $request->userId;
    // "dateIni": "2022-03-15",
    // "dateFin": "2022-03-15",
    if (empty($request->dateIni)) {
        $dateIni = Carbon::now();
    } else {
        $dateIni = Carbon::parse($request->dateIni);
    }

    if (empty($request->dateIni)) {
        $dateFin = Carbon::now();
    } else {
        $dateFin = Carbon::parse($request->dateFin);
    }
    // DB::enableQueryLog();
    $facturasStorage = Factura::select("*")
        //->where('tipo_venta', $request->tipo_venta ? $request->tipo_venta : 1) // si envian valor lo tomo, si no por defecto toma credito
        ->where('status_pagado', $request->status_pagado ? $request->status_pagado : 0) // si envian valor lo tomo, si no por defecto asigno por pagar = 0
        ->where('status', 1);

    if (!$request->allDates) {
        $facturasStorage = $facturasStorage->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
    }

    if ($userId != 0) {
        $facturasStorage = $facturasStorage->where('user_id', $userId);
    }

    $facturas = $facturasStorage->get();
    // $query = DB::getQueryLog();
    // dd(json_encode($query));

    if (count($facturas) > 0) {
        $total = 0;
        $clientes = [];

        foreach ($facturas as $factura) {
            $total += $factura->saldo_restante;
            // $total += number_format((float) ($factura->monto),2,".","");
            //$total += number_format((float) ($factura->saldo_restante),2,".","");


            $factura->user;
            $factura->cliente->factura_historial = $factura->cliente->factura_historial()->where([
                ['estado', '=', 1],
            ])->get()->last();
            $factura->factura_detalle = $factura->factura_detalle()->where([
                ['estado', '=', 1],
            ])->get();

            $factura->cliente->zona;
            $factura->cliente->departamento;
            $factura->cliente->municipio;

            $factura->montos_recibos = $factura
                ->cliente
                ->factura_historial()
                ->where([
                    ['estado', '=', 1],
                ])
                ->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"])
                ->get();

            // $factura->recibos = $factura->cliente->factura_historial()->where([
            //     ['estado', '=', 1],
            // ])->recibo_historial->get();

            array_push($clientes, $factura->cliente_id);
        }

        if (count($clientes) > 0) {
            $clientesUnicos = array_unique($clientes);

            $clienteStore =  FacturaHistorial::whereIn('cliente_id', $clientesUnicos)
                // ->select('id', 'cliente_id','precio','estado','created_at')
                ->where([
                    ['estado', '=', 1],
                ]);

            if (!$request->allDates) {
                $clienteStore = $clienteStore->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
            }

            $abonos = $clienteStore->get();
            $response["recuperacion"] = sumaRecuperacion($abonos);
            $response["abonos"] = $abonos;

            // echo json_encode($cliente);
        }

        $response["total"]   = $total;
        $response["factura"] = $facturas;
    }

    return $response;
}


function sumaRecuperacion($abonos)
{
    $result = 0;
    // echo json_encode($abonos);

    if (count($abonos) > 0) {
        foreach ($abonos as $abono) {
            $result += number_format((float) ($abono->precio), 2, ".", "");
        }
    }

    return $result;
}


function ventasMetaQuery($request)
{
    $response = [
        'factura' => [],
        'total' => 0,
        'meta' => 0,
        'meta_monto' => 0,
        'recuperacion' => 0,
        'recuperacion_monto' => 0,
        'abonos' => [],
    ];

    $metaValue = 0;
    $userId = $request->userId;

    // "dateIni": "2022-03-15",
    // "dateFin": "2022-03-15",
    if (empty($request->dateIni)) {
        $dateIni = Carbon::now();
    } else {
        $dateIni = Carbon::parse($request->dateIni);
    }

    if (empty($request->dateIni)) {
        $dateFin = Carbon::now();
    } else {
        $dateFin = Carbon::parse($request->dateFin);
    }

    $dateIni =  Carbon::parse($dateIni);
    $dateFin =  Carbon::parse($dateFin);


    $facturasStorage = Factura::select("*")
        //->where('tipo_venta', $request->tipo_venta ? $request->tipo_venta : 1) // si envian valor lo tomo, si no por defecto toma credito
        // ->where('status_pagado', $request->status_pagado ? $request->status_pagado : 0) // si envian valor lo tomo, si no por defecto asigno por pagar = 0
        ->where('status', 1);

    if (!$request->allDates) {
        $facturasStorage = $facturasStorage->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
    }

    if ($userId != 0) {
        $facturasStorage = $facturasStorage->where('user_id', $userId);
    }

    $facturas = $facturasStorage->get();
    $clientes = [];
    if (count($facturas) > 0) {
        $total = 0;
        foreach ($facturas as $factura) {
            // $total += $factura->saldo_restante;
            $total += number_format((float) ($factura->monto), 2, ".", "");
            //$total += number_format((float) ($factura->saldo_restante),2,".","");


            $factura->user;
            $factura->cliente->factura_historial = $factura->cliente->factura_historial()->where([
                ['estado', '=', 1],
            ])->get()->last();
            $factura->factura_detalle = $factura->factura_detalle()->where([
                ['estado', '=', 1],
            ])->get();

            array_push($clientes, $factura->cliente_id);
        }

        $response["total"]    = $total;
        $response["factura"] = $facturas;
    }

    // $meta = Meta::where('user_id', $userId)->first();
    // DB::enableQueryLog();
    $meta = getMetaPorUsuario($userId, $dateIni->toDateString() . " 00:00:00", $dateFin->toDateString() . " 23:59:59");
    // dd(DB::getQueryLog());
    // $meta = Meta::select("*")
    //     ->where('user_id', $userId)
    //     ->first();
    // print_r(json_encode($meta));
    // (453 * 100)/1500

    // dd(json_encode([$userId, $dateIni->toDateString() . " 00:00:00", $dateFin->toDateString() . " 23:59:59"]));
    // dd(json_encode($meta));
    if ($meta) {
        $metaValue = $meta->monto_meta;
        $response["meta_monto"] = $meta->monto_meta;
        // print_r(json_encode($metaValue));
        if ($metaValue == 0) {
            $averageMeta = 0;
        } else {
            $averageMeta = ($response["total"] / $metaValue) * 100;
        }

        $response["meta"] = (float) number_format((float) ($averageMeta), 2, ".", "");
    }

    if (count($clientes) > 0) {
        $clientesUnicos = array_unique($clientes);

        $abonosStore =  FacturaHistorial::whereIn('cliente_id', $clientesUnicos)
            // ->select('id', 'cliente_id','precio','estado','created_at')
            ->where([
                ['estado', '=', 1],
            ]);

        if (!$request->allDates) {
            $abonosStore = $abonosStore->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
        }

        $abonos = $abonosStore->get();
        $response["recuperacion_monto"] = sumaRecuperacion($abonos);

        if ($response["meta_monto"] == 0) {
            $recuperacion = 0;
        } else {
            $recuperacion = ($response["recuperacion_monto"] * 100) / $response["meta_monto"];
        }

        $response["recuperacion"] = (float) number_format((float) $recuperacion, 2, ".", "");
        $response["abonos"] = $abonos;

        // echo json_encode($cliente);
    }

    return $response;
}

function newrecuperacionQuery($user, $dateini, $dateFin)
{
    $userId = $user->id;
    $response = [
        // 'facturasTotal' => 0,
        'abonosTotal' => 0,
        'abonosTotalLastMount' => 0,
        'recuperacionPorcentaje' => 0,
        'recuperacionTotal' => 0,
        'user_id' => $userId,

    ];

    $inicioMesActual =  Carbon::parse($dateini)->firstOfMonth()->toDateString();
    $finMesActual =  Carbon::parse($dateFin)->lastOfMonth()->toDateString();
    // $inicioMesActual =  Carbon::now()->firstOfMonth()->toDateString();
    // $finMesActual =  Carbon::now()->lastOfMonth()->toDateString();
    // dd([$inicioMesActual,$finMesActual]);

    $meta_recuperacion = getMetaRecuperacionMensual($userId, $inicioMesActual, $finMesActual);

    if ($meta_recuperacion) {
        $response["recuperacionTotal"] = (float) number_format((float) $meta_recuperacion->monto_meta, 2, ".", "");
    } else {
        // crearMetaRecuperacionMensual();
        crearMetaRecuperacionMensualCuotas();
        $metaCreada = getMetaRecuperacionMensual($userId, $inicioMesActual, $finMesActual);
        $response["recuperacionTotal"] = (float) number_format((float) $metaCreada, 2, ".", ""); // meta
    }


    // Inicio el calculo de recuperacion

    // $abonosStore =  FacturaHistorial::where('user_id', $userId)
    //     // ->whereBetween('created_at', [$inicioMesAnterior." 00:00:00",  $finMesAnterior ." 23:59:59"])
    //     ->where('created_at', "<", $inicioMesActual . " 00:00:00")
    //     ->where('estado', 1);

    // $abonos = $abonosStore->get();

    // $response["abonosTotal"] =  (float) number_format((float) sumaRecuperacion($abonos), 2, ".", "");

    $clienteStoreCurrentMount =  FacturaHistorial::where('user_id', $userId)
        ->whereBetween('created_at', [$inicioMesActual . " 00:00:00",  $finMesActual . " 23:59:59"])
        ->where('estado', 1)
        ->get();

    // Ahora es el mes actual y no ultimo mes 
    $response["abonosTotalLastMount"] =  (float) number_format((float) sumaRecuperacion($clienteStoreCurrentMount), 2, ".", "");


    if ($response["abonosTotalLastMount"] >= 1 && $response["recuperacionTotal"] >= 1) {
        $porcentaje = ($response["abonosTotalLastMount"] * 100) / $response["recuperacionTotal"]; // porcentaje
    } else {
        $porcentaje = 0; // porcentaje
    }

    $response["recuperacionPorcentaje"] = (float) number_format((float) $porcentaje, 2, ".", "");
    // }

    $response["user"] = $user;

    return $response;
}

function productosVendidosPorUsuario($user, $request, $allUsers = false)
{
    $id = $user->id;
    $response = [
        'totalProductos' => 0,
        'productos' => [],
        'user' => $user,
    ];
    $contadorProductos = 0;
    $idProductos = [];

    if (empty($request->dateIni)) {
        $dateIni = Carbon::now();
    } else {
        $dateIni = Carbon::parse($request->dateIni);
    }

    if (empty($request->dateIni)) {
        $dateFin = Carbon::now();
    } else {
        $dateFin = Carbon::parse($request->dateFin);
    }

    $facturasStorage = Factura::select("*")
        // ->where('status_pagado', $request->status_pagado ? $request->status_pagado : 0) // si envian valor lo tomo, si no por defecto asigno por pagar = 0
        ->where('status', 1);

    if (!$request->allDates) {
        $facturasStorage = $facturasStorage->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
    }

    if (!$allUsers) {
        $facturasStorage = $facturasStorage->where('user_id', $id);
    }


    $facturas = $facturasStorage->get();
    foreach ($facturas as $factura) {
        $factura->factura_detalle = $factura->factura_detalle()->where([
            ['estado', '=', 1],
        ])->get();

        if (count($factura->factura_detalle) > 0) {
            foreach ($factura->factura_detalle as $factura_detalle) {
                array_push($idProductos, $factura_detalle->id);
                $contadorProductos = $contadorProductos + $factura_detalle->cantidad;
                // $factura_detalle->producto  = $factura_detalle->producto; 
            }
        }

        // $response["productos"][] = $factura->factura_detalle; 
        // array_push($response["productos"],$factura->factura_detalle) ; 
    }

    if (count($idProductos) > 0) {
        // $facturas_detalle =  Factura_Detalle::whereIn('id', $idProductos)->get();
        // if(count($facturas_detalle)>0){
        //     foreach ($facturas_detalle as $factura_detalle) {
        //         $factura_detalle->producto = $factura_detalle->producto;
        //         $response["productos"][] = $factura_detalle;
        //     }
        // }


        $query = "SELECT 
            SUM(fd.cantidad) AS cantidad,
            p.*
        FROM factura_detalles fd
        INNER JOIN productos p ON p.id = fd.producto_id
        WHERE fd.id IN(" . implode(",", $idProductos) . ")
        GROUP BY fd.producto_id";

        $productos = DB::select($query);

        if (count($productos) > 0) {
            $response["productos"] = $productos;
        }
    }

    // $response = $facturas;
    $response["totalProductos"] = $contadorProductos;
    // $response["id"] = $idProductos;

    return $response;
}


function crearMetaRecuperacionMensual()
{
    // La meta de recuperacion no es lo mismo que META. Es solo para la seccion de recuperacion
    $inicioMesActual =  Carbon::now()->firstOfMonth()->toDateString();
    $finMesActual =  Carbon::now()->lastOfMonth()->toDateString();
    // DB::enableQueryLog();

    $users = User::where([
        ["estado", "=", 1]
    ])->get();

    foreach ($users as $user) {
        $total = 0;

        $facturas = Factura::select("*")
            ->where('tipo_venta',  1) // credito 
            ->where('status_pagado', 0)
            ->where('created_at', "<", $inicioMesActual . " 00:00:00")
            ->where('user_id', $user->id)
            ->where('status', 1)
            ->get();

        if (count($facturas) > 0) {
            foreach ($facturas as $factura) {
                $factura->user;
                $total += number_format((float) ($factura->saldo_restante), 2, ".", "");
            }
        }

        // $resultado = $total  * 0.85;
        $resultado = $total  * 1;
        $monto_meta = (float) number_format((float) $resultado, 2, ".", ""); // meta

        $existeUsuarioMesActual = !!getMetaRecuperacionMensual($user->id, $inicioMesActual, $finMesActual);

        if (!$existeUsuarioMesActual) {
            MetaRecuperacion::create([
                'user_id' => $user->id,
                'monto_meta' => $monto_meta,
                'estado' => 1,
            ]);
        }
    }
}

function incentivoSupervisorQuery($request)
{
    $response = [
        "dataVendedores" => [],
        "totalFacturaVendedores2Porciento" => 0,
        "totalFacturaVendedores" => 0,
        "deducciones" => 0,
        "deducciones_porcentaje" => 0
    ];
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
            $responsequery = CalcularIncentivo($request, $user->id);
            $sumaRecuperacion += (float) $responsequery['porcentaje5'];

            $dataVendedor = [];
            $dataVendedor["nombreCompleto"]  = "$user->name $user->apellido";
            $dataVendedor["idUser"] = $user->id;
            $dataVendedor["porcentajeRecuperacion5"] = (float) $responsequery['porcentaje5'];
            $dataVendedor["totalRecuperacion"] = (float) $responsequery['total'];
            $dataVendedor["totalFacturaVendedor"] = 0;

            // array_push($response, $responsequery);

            $facturasStorage = Factura::select("*")
                ->where('user_id', $user->id)
                ->where('status', 1);

            if (!$request->allDates) {
                $facturasStorage = $facturasStorage->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
            }

            $facturas = $facturasStorage->get();

            if (count($facturas) > 0) {
                $fechaNuevoPorcentaje = Carbon::parse('2023-10-9'); // fecha cambio de porcentaje a 4%
                // $totalFacturas = 0;
                foreach ($facturas as $factura) {
                    $fechaFactura = Carbon::parse($factura->created_at);
                    if ($fechaFactura >= $fechaNuevoPorcentaje) { // apartir del 2023-10-9 se aplico nuevo porcentaje
                        // $totalFacturas += (float) number_format((float) ($factura->monto), 2, ".", "");
                        $response["totalFacturaVendedores2Porciento"] += decimal($factura->monto * 0.01);
                    } else {
                        $response["totalFacturaVendedores2Porciento"] += decimal($factura->monto * 0.02);
                    }

                    $dataVendedor["totalFacturaVendedor"] += decimal($factura->monto);
                    $response["totalFacturaVendedores"] += $dataVendedor["totalFacturaVendedor"];
                }

                // $dataVendedor["totalFacturaVendedor"]  = (float) number_format($totalFacturas, 2, ".", "");
                // $sumaFactura += $dataVendedor["totalFacturaVendedor"];
            } else {
                $dataVendedor["totalFacturaVendedor"]  = 0;
            }
            array_push($response["dataVendedores"], $dataVendedor);
        }
    }

    $DevolucionesSupervisorTrait = new class {
        use DevolucionesSupervisorTrait;
    };

    // Llamar a la función del trait desde la clase anónima
    $Deducciones = $DevolucionesSupervisorTrait->deduccionesSupervisor($request);
    // dd(json_encode($Deducciones) );
    $response["deducciones"] = decimal($Deducciones["total"]);
    $response["deducciones_porcentaje"] = decimal($Deducciones["total"] * 0.01);

    // dd([$response["totalFacturaVendedores2Porciento"], $response["deducciones_porcentaje"]]);
    $response["totalFacturaVendedores2Porciento"] = decimal($response["totalFacturaVendedores2Porciento"] - $response["deducciones_porcentaje"]);


    $response["totalRecuperacionVendedores"] = (float) number_format($sumaRecuperacion, 2, ".", "");
    // $response["totalFacturaVendedores"] = (float) number_format($sumaFactura, 2, ".", "");
    //totalFacturaVendedores2Porciento = (float) number_format($response["totalFacturaVendedores"] * 0.02, 2, ".", "");

    // $response["totalFacturas"] = number_format($totalFacturas, 2, ".", "");
    // $response["totalFacturasX02"] = number_format($totalFacturas * 0.02, 2, ".", "");;
    // $response["factura"] = $facturas;

    return $response;
}

function incentivosQuery($request)
{
    $response = [
        'recibo' => [],
        'total_contado' => 0,
        'total_credito' => 0,
        'porcentaje20' => 0,
        'porcentaje_asignado' => 0.20,
        'total' => 0,
    ];

    $userId = $request->userId;
    $fechaLimite = Carbon::create(2024, 9, 23, 0, 0, 0); // ($userId == 28 || $userId == 34)
    $fechaActual = Carbon::now();
    // $porcentaje = 0.20;

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

                    // if ($fechaActual->greaterThanOrEqualTo($fechaLimite) && ($userId == 28 || $userId == 34)) {
                    //     // $porcentaje = 0.22;
                    //     $response["porcentaje_asignado"] = 0.22;
                    // }

                    $response["porcentaje20"] += decimal($recibo_historial->factura_historial->precio * $response["porcentaje_asignado"]);

                    // if ($fechaActual->greaterThanOrEqualTo($fechaLimite)) {
                    //     // Si la fecha actual es mayor o igual a la fecha límite
                    //     if ($userId == 28 || $userId == 34) {
                    //         $response["porcentaje20"] += decimal($recibo_historial->factura_historial->precio * 0.22);
                    //     } else {
                    //         $response["porcentaje20"] += decimal($recibo_historial->factura_historial->precio * 0.20);
                    //     }
                    // } else {
                    //     $response["porcentaje20"] += decimal($recibo_historial->factura_historial->precio * 0.20);
                    // }

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
                    if ($fechaActual->greaterThanOrEqualTo($fechaLimite) && ($userId == 28 || $userId == 34)) {
                        // $porcentaje = 0.22;
                        $response["porcentaje_asignado"] = 0.22;
                    }

                    $response["porcentaje20"] += decimal($recibo_historial_contado->factura->monto * $response["porcentaje_asignado"]);

                    // if ($userId == 28 || $userId == 34) {
                    //     $response["porcentaje20"] += decimal($recibo_historial_contado->factura->monto * 0.22);
                    // } else {
                    //     $response["porcentaje20"] += decimal($recibo_historial_contado->factura->monto * 0.20);
                    // }
                }
            }
        }



        $response["total_credito"] = number_format($response["total_credito"], 2, ".", "");
        $response["total_contado"] = number_format($response["total_contado"], 2, ".", "");
        $response["total"]         = number_format($response["total_contado"] + $response["total_credito"], 2, ".", "");
        // $response["porcentaje20"]  = number_format($response["total"] * 0.20, 2, ".", "");

        $response["recibo"]        = $recibo;
    }
    return $response;
}

function crearMetaRecuperacionMensualCuotas()
{
    // La meta de recuperacion no es lo mismo que META. Es solo para la seccion de recuperacion
    $inicioMesActual =  Carbon::now()->firstOfMonth()->toDateString();
    $finMesActual =  Carbon::now()->lastOfMonth()->toDateString();
    // DB::enableQueryLog();

    $users = User::where([
        ["estado", "=", 1]
    ])->get();

    foreach ($users as $user) {
        $total = 0;

        $facturas = Factura::select("*")
            ->where('tipo_venta',  1) // credito 
            ->where('status_pagado', 0)
            ->where('created_at', "<", $inicioMesActual . " 00:00:00")
            ->where('user_id', $user->id)
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
        $resultado = $total * 1;

        $existeUsuarioMesActual = !!getMetaRecuperacionMensual($user->id, $inicioMesActual, $finMesActual);

        if (!$existeUsuarioMesActual) {
            MetaRecuperacion::create([
                'user_id' => $user->id,
                'monto_meta' => $resultado,
                'estado' => 1,
            ]);
        }
    }
}

function crearMetaMensual($mes = "")
{
    // Para crear la meta mensual solo tomamos el valor de la tabla meta, ese es el valor por defecto
    $mensajes = [];

    $response[] = 'La meta fue eliminado con exito.';

    if ($mes == "") {
        $inicioMesActual =  Carbon::now()->firstOfMonth()->toDateString();
        $finMesActual =  Carbon::now()->lastOfMonth()->toDateString();
    } else {
        $inicioMesActual =  Carbon::parse($mes)->firstOfMonth()->toDateString();
        $finMesActual =  Carbon::parse($mes)->lastOfMonth()->toDateString();
    }
    // DB::enableQueryLog();

    $users = User::where([
        ["estado", "=", 1]
    ])->get();

    foreach ($users as $user) {
        $meta = Meta::where('user_id', $user->id)->first(); // Meta default

        if (is_null($meta)) { // agrego meta por no existir
            Meta::create([
                'user_id' => $user->id,
                'monto' => 0,
                'estado' => 1,
            ]);

            $meta = Meta::where('user_id', $user->id)->first(); // Meta default
            // dd(json_encode($meta ));
        }

        $existeUsuarioMesActual = getMetaPorUsuario($user->id, $inicioMesActual . " 00:00:00", $finMesActual . " 23:59:59");
        if (!$existeUsuarioMesActual) {
            // dd([$user->id, $inicioMesActual . " 00:00:00", $finMesActual . " 23:59:59"] );
            $fechaActual = Carbon::now();

            MetaHistorial::create([
                'user_id' => $user->id,
                'monto_meta' => $meta->monto,
                'fecha_asignacion' => ($mes == "") ? $fechaActual->toDateTimeString() : $inicioMesActual . " 00:00:00",
                'estado' => 1,
            ]);
        }
    }
}

function getMetaRecuperacionMensual($user_id, $inicioMesActual, $finMesActual)
{
    // $inicioMesActual =  Carbon::now()->firstOfMonth()->toDateString();
    // $finMesActual =  Carbon::now()->lastOfMonth()->toDateString();

    $meta_recuperacion = MetaRecuperacion::where('user_id', $user_id)
        ->whereBetween('created_at', [$inicioMesActual . " 00:00:00",  $finMesActual . " 23:59:59"])
        ->where('estado', 1)
        ->first();

    return ($meta_recuperacion) ? $meta_recuperacion : false;
}

function getMetaPorUsuario($user_id, $fechaInicio = "", $fechaFin = "")
{
    $meta_recuperacion = MetaHistorial::where('user_id', $user_id)
        ->where('estado', 1);


    $meta_recuperacion->when($fechaInicio != "", function ($q) use ($fechaInicio, $fechaFin) {
        return $q->whereBetween('fecha_asignacion', [$fechaInicio,  $fechaFin]);
    });

    // $meta_recuperacion->first();

    return ($meta_recuperacion) ? $meta_recuperacion->first() : false;
}

function cambiarClientesAListaNegraFacturasMora60_90()
{
    // calculo si tiene un recibo en mora de 60 - 90
    $fechaHoy = Carbon::now();

    $categoriaListaNegra =  Categoria::where([
        ['tipo', '=', "LN"],
        ['estado', '=', 1]
    ])->first();

    $clientes = Cliente::select("*")
        ->where('categoria_id', $categoriaListaNegra->id)
        ->where('estado', 1)
        ->get();

    $idClientesConListaNegra = [];

    foreach ($clientes as $cliente) {
        $idClientesConListaNegra[] = $cliente->id;
    }

    $facturas = Factura::where('status_pagado', 0)
        ->where('status', 1);

    $facturas->when(count($idClientesConListaNegra) > 0, function ($q) use ($idClientesConListaNegra) {
        return $q->whereNotIn('cliente_id', $idClientesConListaNegra);
    });

    $facturas = $facturas->get();
    // dd(json_encode($facturas->get()));

    if (count($facturas) > 0) {
        foreach ($facturas as $factura) { // valido todas sus facturas, para ver si tiene una en mora
            // $fechaPasado60DiasVencimiento = Carbon::parse($factura->fecha_vencimiento)->addDays(60)->toDateTimeString();

            $fechaPasado60DiasVencimiento = Carbon::parse($factura->fecha_vencimiento)->addDays(61)->toDateTimeString();
            $fechaPasado90DiasVencimiento = Carbon::parse($factura->fecha_vencimiento)->addDays(91)->toDateTimeString();
            // $fechaPasado60DiasVencimiento = Carbon::parse($factura->created_at)->addDays(60)->toDateTimeString();
            // $fechaPasado90DiasVencimiento = Carbon::parse($factura->created_at)->addDays(90)->toDateTimeString();

            if ($fechaHoy->gte($fechaPasado60DiasVencimiento)) {
                // if (Carbon::parse($fechaPasado60DiasVencimiento)->diffInDays($fechaHoy) >= 60) {
                $clienteEnMora = Cliente::find($factura->cliente_id);
                $clienteEnMora->categoria_id = $categoriaListaNegra->id; // agrego a lista negra por estas en mora de 60 dias o mas
                $clienteEnMora->save();
            }
        }
    }
}

function convertTazaCambio($monto)
{
    $result = number_format((float) (0), 2, ".", "");

    $tazacambio = TazaCambio::where('estado', 1)->first();
    $taza = number_format((float) ($tazacambio->monto), 2, ".", "");
    $montoCambio = number_format((float) ($monto), 2, ".", "");

    $result = (float) number_format((float) ($taza * $montoCambio), 2, ".", "");

    return $result;
}

function decimal($monto)
{
    return (float) number_format((float) ($monto), 2, ".", "");
}

function productosVendidos($request)
{
    // $id = $user->id;
    $response = [
        'totalProductos' => 0,
        'productos' => [],
        // 'user' => $user,
    ];
    $contadorProductos = 0;
    $idProductos = [];

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

    $facturasStorage = Factura::select("*")->where('status', 1);

    $facturasStorage->when($request->allDates && $request->allDates == "false", function ($q) use ($dateIni, $dateFin) {
        return $q->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
    });
    // if (!$request->allDates) {
    //     $facturasStorage = $facturasStorage->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
    // }

    // $facturasStorage = $facturasStorage->where('user_id', $id);

    $facturas = $facturasStorage->get();
    foreach ($facturas as $factura) {
        $factura->factura_detalle = $factura->factura_detalle()->where([
            ['estado', '=', 1],
        ])->get();

        if (count($factura->factura_detalle) > 0) {
            foreach ($factura->factura_detalle as $factura_detalle) {
                array_push($idProductos, $factura_detalle->id);
                $contadorProductos = $contadorProductos + $factura_detalle->cantidad;
            }
        }
    }

    if (count($idProductos) > 0) {

        $query = "SELECT 
            SUM(fd.cantidad) AS cantidad,
            p.*
        FROM factura_detalles fd
        INNER JOIN productos p ON p.id = fd.producto_id
        WHERE fd.id IN(" . implode(",", $idProductos) . ")
        GROUP BY fd.producto_id";

        $productos = DB::select($query);

        if (count($productos) > 0) {
            $response["productos"] = $productos;
        }
    }

    // $response = $facturas;
    $response["totalProductos"] = $contadorProductos;
    // $response["id"] = $idProductos;

    return $response;
}


function CalcularIncentivo($request, $userId)
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
            $fechaNuevoPorcentaje = Carbon::parse('2023-10-9'); // fecha cambio de porcentaje a 4%
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
                    $fechaCreacionAbono = Carbon::parse($recibo_historial->factura_historial->created_at);
                    // dd([json_encode($fechaNuevoPorcentaje->toDateString()),json_encode($recibo_historial->factura_historial)]);
                    if ($fechaCreacionAbono >= $fechaNuevoPorcentaje) { // apartir del 2023-10-9 se aplico nuevo porcentaje
                        $response["total_credito"]  += decimal($recibo_historial->factura_historial->precio * 0.04);
                    } else {
                        $response["total_credito"] += decimal($recibo_historial->factura_historial->precio * 0.05);
                    }
                    $response["total"] += decimal($recibo_historial->factura_historial->precio);
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

                $fechaCreacionAbonoContado = Carbon::parse($recibo_historial_contado->factura->created_at);
                if ($recibo_historial_contado->factura) {

                    if ($fechaCreacionAbonoContado >= $fechaNuevoPorcentaje) { // apartir del 2023-10-9 se aplico nuevo porcentaje
                        $response["total_contado"]  += decimal($recibo_historial_contado->factura->monto * 0.04);
                    } else {
                        $response["total_contado"] += decimal($recibo_historial_contado->factura->monto * 0.05);
                    }
                    $response["total"] += decimal($recibo_historial_contado->factura->monto);
                    // $response["total_credito"] += $recibo_historial_contado->factura->monto;
                }
            }
        }

        // $response["total_credito"] = number_format($response["total_credito"], 2, ".", "");
        // $response["total_contado"] = number_format($response["total_contado"], 2, ".", "");
        // $response["total"]         = $response["total_contado"] + $response["total_credito"];
        // $response["porcentaje5"]   = $response["total"];
        $response["porcentaje5"] = $response["total_contado"] + $response["total_credito"];

        $response["recibo"]        = $recibo;
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

    $userId = $request->userId;
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

function clienteNuevo($request)
{
    $response = [];
    $condiciones = [
        ['status', 1],
        ['monto', '>', 1],
    ];

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
    if ($request->userId != 0) {
        // $clienteStore = $clienteStore->where('clientes.user_id', $request->userId);
        $condiciones[] = ["user_id", $request->userId];
    }

    // DB::enableQueryLog();


    $clienteStore =  Cliente::whereDoesntHave('facturas', function ($query) use ($dateIni, $condiciones) {
        // Filtra clientes que no tienen facturas anteriores al primer día del mes actual, con facturas activas (status = 1), monto mayor a 1 y que coincidan con el user_id 
        $query
            ->where('created_at', '<', $dateIni->toDateString() . " 00:00:00")
            ->where($condiciones);
    })
        ->with(['frecuencia', 'categoria', 'facturas', 'departamento', 'municipio', 'zona'])
        ->whereHas('facturas', function ($query) use ($dateIni, $dateFin, $condiciones) {
            // Asegura que el cliente tenga al menos una factura en el rango de fechas del mes actual, con facturas activas (status = 1), saldo restante mayor a 1 y que coincidan con el user_id.
            $query
                ->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"])
                ->where($condiciones);
        });

    if (!$request->allDates) {
        // $clienteStore = $clienteStore->whereBetween('facturas.created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
    }

    // if ($request->userId != 0) {
    //     $clienteStore = $clienteStore->where('clientes.user_id', $request->userId);
    // }

    // $clienteStore = $clienteStore->WhereHas('facturas', function ($query) use ($filter) {
    //     $query->where('descripcion', 'like', '%' . $filter . '%');
    // });

    $clientes_id = [];
    // $facturasQuery = Factura::where([
    //     ['status', '=', 1],
    //     ['saldo_restante', '>=', 1],
    // ])->groupBy('cliente_id')->select("cliente_id")->get();

    // if (count($facturasQuery) > 0) {
    //     foreach ($facturasQuery as $factura) {

    //         $clientes_id[] = $factura->cliente_id;
    //     }
    // }

    // print_r($clientes_id);

    // $clientes = $clienteStore->wherein('id', $clientes_id)->get();
    $clientes = $clienteStore->get();

    // $query = DB::getQueryLog();
    // print_r(json_encode($query));
    // if (count($clientes) > 0) {
    //     foreach ($clientes as $cliente) {
    //         $cliente->frecuencia = $cliente->frecuencia;
    //         $cliente->categoria = $cliente->categoria;
    //         $cliente->facturas = $cliente->facturas;
    //     }

    // }

    $response = $clientes;

    return $response;
}

function clientesInactivosQuery($request)
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
            if(q.cantidad_factura = q.cantidad_finalizadas, 1, 0) AS cliente_inactivo,
            zon.nombre AS 'nombre_zona',
            dep.nombre AS 'nombre_departamento',
            mun.nombre AS 'nombre_municipio'    
        FROM clientes c
        INNER JOIN (
            SELECT
                c.id AS cliente_id,
                c.user_id AS user_id,
                cat.tipo,
                cat.descripcion,
                COUNT(c.id) AS cantidad_factura,
                MAX(f.status_pagado_at) AS last_date_finalizada,
                SUM(if(f.status_pagado = 1, 1, 0)) AS cantidad_finalizadas
            FROM clientes c
            INNER JOIN facturas f ON c.id = f.cliente_id
            INNER JOIN categorias cat ON c.categoria_id = cat.id 

            WHERE  f.`status` = 1 AND c.estado = 1 AND cat.tipo !='DP'
            GROUP BY c.id
            ORDER BY c.id ASC
        )q ON c.id = q.cliente_id
            LEFT JOIN zonas zon ON zon.id = c.zona_id 
            LEFT JOIN departamentos dep ON dep.id = c.departamento_id 
            LEFT JOIN municipios mun ON mun.id = c.municipio_id        
        WHERE
            q.cantidad_factura = q.cantidad_finalizadas AND
            TIMESTAMPDIFF(MONTH,last_date_finalizada, NOW()) >= 1 
    ";

    if ($request->userId != 0) {
        $query = $query . " AND c.user_id = " . $request->userId;
    }

    if (isset($request->diasCobros)) {
        if (count($request->diasCobros) > 0) {
            $diasCobros = $request->diasCobros;

            $query = $query . ' AND ';
            foreach ($diasCobros as $index => $diaCobro) {
                // array_push($condicionDiasCobro, ['dias_cobro', 'LIKE', '%' . $dia . '%', "or"]);
                $query = $query . 'c.dias_cobro LIKE "%' . $diaCobro . '%"';
                if (count($diasCobros) > ($index + 1)) {
                    $query = $query . ' OR ';
                }
            }
        }
    }

    // $clientes->when($request->diasCobros, function ($q) use ($request) {
    //     $query = $q;
    //     $dias = explode(",", $request->diasCobros);
    //     $condicionDiasCobro = [];
    //     foreach ($dias as $dia) {
    //         array_push($condicionDiasCobro, ['dias_cobro', 'LIKE', '%' . $dia . '%', "or"]);
    //     }
    //     return $query->where($condicionDiasCobro);
    // });

    $clientes = DB::select($query);
    $clientesFiltrados = [];

    if (count($clientes) > 0) {
        foreach ($clientes as $cliente) {
            $cliente->frecuencia = Frecuencia::find($cliente->frecuencia_id);
            $cliente->categoria = Categoria::find($cliente->categoria_id);
            $cliente->user = User::find($cliente->user_id);

            $nota = ClientesInactivosNotas::where("cliente_id", $cliente->id)->first();
            if ($nota) $nota->notaValueString();

            $cliente->nota = $nota;

            if (isset($request->tipos)) {
                if ($request->tipos == 0) {
                    $clientesFiltrados[] = $cliente;
                } else {
                    if (!$nota) continue;

                    if ($request->tipos == $cliente->nota->tipos) {
                        $clientesFiltrados[] = $cliente;
                    }
                }
            } else {
                $clientesFiltrados[] = $cliente;
            }
        }

        $response = $clientesFiltrados;
    }

    // print_r(count($cliente));
    return $response;
}

function clientesReactivadosQuery($request)
{
    $response = [];

    $userId = $request->userId;

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

function mora30_60Query($request)
{
    $response = [
        'factura' => [],
        'total_saldo' => 0
    ];
    $userId = $request->userId;
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
            $fechaPasado30DiasVencimiento = Carbon::parse($factura->fecha_vencimiento)->addDays(1)->toDateTimeString();
            $fechaPasado60DiasVencimiento = Carbon::parse($factura->fecha_vencimiento)->addDays(61)->toDateTimeString();
            //$fechaPasado30DiasVencimiento = Carbon::parse($factura->created_at)->addDays(30)->toDateTimeString();
            //$fechaPasado60DiasVencimiento = Carbon::parse($factura->created_at)->addDays(60)->toDateTimeString();

            if ($fechaActual->gte($fechaPasado30DiasVencimiento) && $fechaActual->lte($fechaPasado60DiasVencimiento)) {
                $factura->user;
                $factura->cliente;
                $factura->vencimiento30 = $fechaPasado30DiasVencimiento;
                $factura->vencimiento60 = $fechaPasado60DiasVencimiento;
                $response["total_saldo"] += $factura->saldo_restante;

                // $factura->diferenciaDias = Carbon::parse($factura->fecha_vencimiento)->diffInDays($fechaActual);
                $factura->diferenciaDias = Carbon::parse($fechaPasado30DiasVencimiento)->diffInDays($fechaActual);

                array_push($response["factura"], $factura);
            }
        }
    }

    return $response;
}


function mora60_90Query($request)
{
    $response = [
        'factura' => [],
        'total_saldo' => 0,
    ];

    $userId = $request->userId;
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

            $fechaPasado60DiasVencimiento = Carbon::parse($factura->fecha_vencimiento)->addDays(61)->toDateTimeString();
            $fechaPasado90DiasVencimiento = Carbon::parse($factura->fecha_vencimiento)->addDays(91)->toDateTimeString();
            // $fechaPasado60DiasVencimiento = Carbon::parse($factura->created_at)->addDays(60)->toDateTimeString();
            // $fechaPasado90DiasVencimiento = Carbon::parse($factura->created_at)->addDays(90)->toDateTimeString();

            if ($fechaActual->gte($fechaPasado60DiasVencimiento)) {
                //if (Carbon::parse($fechaPasado60DiasVencimiento)->diffInDays($fechaActual) >= 31) {


                if ($factura->cliente->categoria->tipo != "DP") { // si no pertenece a depurados lo agrego
                    $factura->user;
                    $factura->cliente->categoria;
                    $factura->vencimiento60  = $fechaPasado60DiasVencimiento;
                    $factura->vencimiento90  = $fechaPasado90DiasVencimiento;

                    $response["total_saldo"] += $factura->saldo_restante;

                    // $factura->diferenciaDias = Carbon::parse($factura->fecha_vencimiento)->diffInDays($fechaActual);
                    $factura->diferenciaDias = Carbon::parse($factura->fecha_vencimiento)->diffInDays($fechaActual);
                    array_push($response["factura"], $factura);
                }
            }
        }
    }

    return $response;
}

function ventasMes($request, $usuario)
{
    $response = [
        'totalVentas' => 0,
        'meta' => 0,
        'porcentaje' => 0,
        'factura' => [],
    ];

    $metaValue = 0;
    $userId = $request->userId;
    if (empty($request->dateIni)) {
        $dateIni = Carbon::now();
    } else {
        $dateIni = Carbon::parse($request->dateIni);
    }

    if (empty($request->dateIni)) {
        $dateFin = Carbon::now();
    } else {
        $dateFin = Carbon::parse($request->dateFin);
    }

    $dateIni =  Carbon::parse($dateIni);
    $dateFin =  Carbon::parse($dateFin);


    $facturasStorage = Factura::select("*")
        //->where('tipo_venta', $request->tipo_venta ? $request->tipo_venta : 1) // si envian valor lo tomo, si no por defecto toma credito
        // ->where('status_pagado', $request->status_pagado ? $request->status_pagado : 0) // si envian valor lo tomo, si no por defecto asigno por pagar = 0
        ->where('status', 1);

    if (!$request->allDates) {
        $facturasStorage = $facturasStorage->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
    }

    if ($userId != 0) {
        $facturasStorage = $facturasStorage->where('user_id', $userId);
    }

    $facturas = $facturasStorage->get();
    if (count($facturas) > 0) {
        $total = 0;
        foreach ($facturas as $factura) {
            // $factura->user;
            $total += decimal($factura->monto);
        }

        $response["totalVentas"] = $total;
        $response["factura"] = $facturas;
    }

    $meta = getMetaPorUsuario($userId, $dateIni->toDateString() . " 00:00:00", $dateFin->toDateString() . " 23:59:59");

    if ($meta) {
        $metaValue = $meta->monto_meta;
        // print_r(json_encode($metaValue));
        if ($metaValue == 0) {
            $averageMeta = 0;
        } else {
            $averageMeta = ($response["totalVentas"] / $metaValue) * 100;
        }

        $response["meta"] = $metaValue;
        $response["porcentaje"] = decimal($averageMeta);
    }

    $response["user"] = $usuario;

    return $response;
}

function ListadoCostosProductosVendidos($request)
{
    $user = (object) [
        "id" => 25,
    ];
    // dd([$user,$request->all()]);

    $response = [
        'costoTotal' => 0,
        'totalProductos' => 0,
        'productos' => [],
        'user' => $user,
    ];
    $contadorProductos = 0;
    $idProductos = [];
    $idFacturaDetalles = [];

    if (empty($request->dateIni)) {
        $dateIni = Carbon::now();
    } else {
        // dd($request);
        $dateIni = Carbon::parse($request->dateIni);
    }

    if (empty($request->dateFin)) {
        $dateFin = Carbon::now();
    } else {
        // dd($request);
        $dateFin = Carbon::parse($request->dateFin);
    }
    // dd($request->all());
    $facturasStorage = Factura::select("*")
        // ->where('status_pagado', $request->status_pagado ? $request->status_pagado : 0) // si envian valor lo tomo, si no por defecto asigno por pagar = 0
        ->where('status', 1);

    // dd([ $request->allDates]);
    if (isset($request->allDates) && ($request->allDates == false || $request->allDates == "false")) {
        // dd($request);
        $facturasStorage = $facturasStorage->whereBetween(
            'created_at',
            [
                $dateIni->toDateString() . " 00:00:00",
                $dateFin->toDateString() . " 23:59:59"
            ]
        );
        // print_r([$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
    }
    // dd($request);
    $facturas = $facturasStorage->get();

    foreach ($facturas as $factura) {
        $factura->factura_detalle = $factura->factura_detalle()->where([
            ['estado', '=', 1],
        ])->get();

        if (count($factura->factura_detalle) > 0) {
            foreach ($factura->factura_detalle as $factura_detalle) {
                array_push($idProductos, $factura_detalle->producto_id);
                array_push($idFacturaDetalles, $factura_detalle->id);
                $contadorProductos += $factura_detalle->cantidad;
                // $factura_detalle->producto  = $factura_detalle->producto; 
            }
        }

        // $response["productos"][] = $factura->factura_detalle; 
        // array_push($response["productos"],$factura->factura_detalle) ; 
    }

    // if (count($idProductos) > 0) {


    $productoVendidos = Factura_Detalle::join('productos', 'productos.id', '=', 'factura_detalles.producto_id')
        // ->wherein('productos.id', $idProductos)
        ->wherein('factura_detalles.id', $idFacturaDetalles)
        ->where([
            ["productos.estado", "=", "1"],
            ["factura_detalles.estado", "=", "1"]
        ])
        ->select(
            DB::raw(
                'productos.id,
                    SUM(factura_detalles.cantidad) AS cantidad, 
                    productos.marca, 
                    productos.modelo, 
                    productos.linea, 
                    productos.descripcion'
            )
        )
        ->groupBy('factura_detalles.producto_id');

    $todosLosProductos = $productoVendidos->get();
    foreach ($todosLosProductos as $productoVentaTotal) {

        $costo_opcional = CostosVentas::where('producto_id', $productoVentaTotal->id)
            ->whereHas('costo_ventas_detalles', function ($query) use ($dateIni, $dateFin) {
                $query->whereBetween('fecha', [
                    $dateIni->toDateString() . " 00:00:00",
                    $dateFin->toDateString() . " 23:59:59"
                ]);
            })
            ->with(['costo_ventas_detalles' => function ($query) use ($dateIni, $dateFin) {
                $query->whereBetween('fecha', [
                    $dateIni->toDateString() . " 00:00:00",
                    $dateFin->toDateString() . " 23:59:59"
                ]);
            }])
            ->first();

        if ($costo_opcional) {
            $response["costoTotal"] += decimal($costo_opcional->costo_ventas_detalles[0]->costo * $productoVentaTotal->cantidad);
        } else {
            $inversion = InversionDetail::where([
                ["codigo", "=", $productoVentaTotal->id],
                ["estado", "=", 1],
                // ["updated_at", "=", DB::raw('(
                //     SELECT MAX(updated_at)
                //     FROM inversion_details
                //     )')]
            ])
                ->orderBy("updated_at", "desc")
                ->first();
            // $response["costoTotal"] += decimal($inversion->costo * $productoVentaTotal->cantidad);

            if ($inversion) {
                $response["costoTotal"] += decimal($inversion->costo_total * $productoVentaTotal->cantidad);
            }
        }
    }

    if ($request->disablePaginate && $request->disablePaginate == 1) {
        $productoVendidos = $productoVendidos->get();
    } else {
        $productoVendidos = $productoVendidos->paginate(15);
    }

    foreach ($productoVendidos as $productoVendido) {
        // DB::enableQueryLog();
        $productoVendido->costo_opcional = CostosVentas::where('producto_id', $productoVendido->id)
            ->whereHas('costo_ventas_detalles', function ($query) use ($dateIni, $dateFin) {
                $query->whereBetween('fecha', [
                    $dateIni->toDateString() . " 00:00:00",
                    $dateFin->toDateString() . " 23:59:59"
                ]);
            })
            ->with(['costo_ventas_detalles' => function ($query) use ($dateIni, $dateFin) {
                $query->whereBetween('fecha', [
                    $dateIni->toDateString() . " 00:00:00",
                    $dateFin->toDateString() . " 23:59:59"
                ]);
            }])
            ->first();

        $productoVendido->inversion = $productoVendido->costo_opcional ? null : InversionDetail::where([
            ["codigo", "=", $productoVendido->id],
            ["estado", "=", 1],
            // ["updated_at", "=", DB::raw('(
            //     SELECT MAX(updated_at)
            //     FROM inversion_details
            //     )')]
        ])
            ->orderBy("updated_at", "desc")
            ->first();

        // $query = DB::getQueryLog();
        // print_r($query);
        // if ($productoVendido->id == 129) {
        //     dd($query);
        // }
    }
    $response["productos"] = $productoVendidos;
    $response["totalProductos"] = $contadorProductos;
    // }
    // dd(json_encode($response));
    // $response = $facturas;
    // $response["id"] = $idProductos;
    return $response;
}

function ListadoGastos($request)
{
    $response = [];
    $dateIni = empty($request->dateIni) ? Carbon::now() : Carbon::parse($request->dateIni);
    $dateFin = empty($request->dateFin) ? Carbon::now() : Carbon::parse($request->dateFin);

    // DB::enableQueryLog();

    $gastos =  Gasto::query();

    // ** Filtrado por rango de fechas 
    //     if (isset($request->allDates) && ($request->allDates == false||$request->allDates== "false")) {
    $gastos->when(isset($request->allDates) && ($request->allDates == false || $request->allDates == "false"), function ($q) use ($dateIni, $dateFin) {
        return $q->whereBetween('fecha_comprobante', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
    });

    $gastos->when($request->estado, function ($q) use ($request) {
        return $q->where('estado', $request->estado);
    });

    $gastos->when(isset($request->metodoPago) && $request->metodoPago != 99, function ($q) use ($request) {
        return $q->where('tipo_pago', $request->metodoPago);
    });

    $gastos->when(isset($request->tipoGasto) && $request->tipoGasto != 99, function ($q) use ($request) {
        return $q->where('tipo', $request->tipoGasto);
    });

    // filtrados para campos numericos
    $gastos->when(isset($request->filter) && is_numeric($request->filter), function ($q) use ($request) {
        $query = $q;
        // id de recibos 
        $query = $query->where(
            [
                ['numero', 'LIKE', '%' . $request->filter . '%'],
            ]
        );

        return $query;
    }); // Fin Filtrado

    // filtrados para campos numericos
    $gastos->when(isset($request->filter) && !is_numeric($request->filter), function ($q) use ($request) {
        $query = $q;
        // id de recibos 
        $query = $query->where(
            [
                ['conceptualizacion', 'LIKE', '%' . $request->filter . '%'],
            ]
        );

        return $query;
    }); // Fin Filtrado

    $TotalMonto = $gastos->sum('monto');
    if ($request->disablePaginate == 0) {
        $gastos = $gastos->orderBy('fecha_comprobante', 'desc')->paginate(15);
    } else {
        $gastos = $gastos->orderBy('fecha_comprobante', 'desc')->get();
    }

    // dd(DB::getQueryLog());

    // if (count($gastos) > 0) {
    foreach ($gastos as $gasto) {
        $gasto->typeValueString();
        $gasto->typePayValueString();
        // $importacion->inversion;
        // $importacion->inversion_detalle;
    }

    // $gastos->totalGastos = $TotalMonto;

    // }
    $response["response"] = $gastos;
    $response["total_monto"] = decimal($TotalMonto);
    return $response;
}
