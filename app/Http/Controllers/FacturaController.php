<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\ClientesReactivados;
use App\Models\Factura;
use App\Models\Factura_Detalle;
use App\Models\Producto;
use App\Models\ProductoParaRegalo;
use App\Models\ReciboHistorialContado;
use App\Models\RegalosFacturados;
use App\Models\TazaCambio;
use App\Models\TazaCambioFactura;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FacturaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $response = [];
        $status = 200;
        // $facturaEstado = 1; // Activo
        $parametros = [];

        if (!is_null($request['estado'])) $parametros[] = ["status", $request['estado']];
        if (!is_null($request['tipo_venta'])) $parametros[] = ["tipo_venta", $request['tipo_venta']];
        if (!is_null($request['status_pagado'])) $parametros[] = ["status_pagado", $request['status_pagado']];
        if (!is_null($request['status_pagado'])) $parametros[] = ["status_pagado", $request['status_pagado']];
        if (!is_null($request['status_entrega'])) $parametros[] = ["entregado", $request['status_entrega']];
        if (!is_null($request['despachado'])) $parametros[] = ["despachado", $request['despachado']];
        if (!is_null($request['created_at'])) {
            $created_at = Carbon::parse($request['created_at']);
            $parametros[] = ["created_at", '>=', $created_at . " 00:00:00"];
        }

        // dd($facturaEstado);
        $facturas =  Factura::where($parametros)->get();

        if (count($facturas) > 0) {
            foreach ($facturas as $key => $factura) {
                $factura->user;
                $factura->cliente->factura_historial;
                $factura->factura_detalle = $factura->factura_detalle()->where([
                    ['estado', '=', 1],
                ])->get();
                // $factura->factura_historial;
            }

            $response = $facturas;
        }

        return response()->json($response, $status);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'user_id'           => 'required|numeric',
            'cliente_id'        => 'required|numeric',
            'monto'             => 'required|numeric',
            'fecha_vencimiento' => 'required|date',
            'tipo_venta'        => 'required|numeric',
            'status_pagado'     => 'required|boolean',
            'iva'               => 'required|numeric',
            'despachado'        => 'required|numeric|max:1',
            'estado'            => 'required|numeric|max:1',

            'factura_detalle'                   => 'required|array',
            'factura_detalle.*.producto_id'     => 'required|numeric',
            'factura_detalle.*.cantidad'        => 'required|numeric',
            'factura_detalle.*.precio'          => 'required|numeric',
            'factura_detalle.*.precio_unidad'   => 'required|numeric',
            'factura_detalle.*.estado'          => 'required|numeric',

            'numero'       => [
                'required',
                'numeric',
                Rule::unique('recibo_historial_contado')->where(fn ($query) => $query->where('estado', 1)),
            ],
            'recibo_id'         => 'numeric|required',
            'rango'             => 'required|string',

        ], [
            'numero.unique' => 'El número de recibo ya existe en nuestra base de datos.',
        ]);

        if ($validation->fails()) {
            return response()->json($validation->errors(), 400);
        }

        try {
            $facturaInsert = [
                'user_id'           => $request['user_id'],
                'cliente_id'        => $request['cliente_id'],
                'monto'             => $request['monto'],
                'saldo_restante'    => $request['monto'],
                'fecha_vencimiento' => $request['fecha_vencimiento'],
                'iva'               => $request['iva'],
                'tipo_venta'        => $request['tipo_venta'],
                'status_pagado'     => $request['status_pagado'],
                'despachado'        => $request['despachado'],
                'status'            => $request['estado'],
                'status_pagado_at'  => null,
            ];

            // obtener el saldo del cliente
            $FacturasxClientexSinPagar = Factura::where([
                ['cliente_id', '=', $request['cliente_id']],
                ['status_pagado', '=', 0], // 0 = sin pagar
                ['status', '=', 1],
            ])->sum('saldo_restante');
            // dd(json_encode($FacturasxClientexSinPagar));

            // calculo si tiene un recibo en mora de 60 - 90
            $fechaHoy = Carbon::now();
            $facturasMora60_90 = Factura::select("*")
                ->where('status_pagado', 0)
                ->where('cliente_id', $request['cliente_id'])
                ->where('status', 1)
                ->get();

            // obtener data del cliente
            $cliente =  Cliente::where([
                ['id', '=',  $request['cliente_id']],
                ['estado', '=', 1]
            ])->first();

            // $user = User::where([
            //     ["estado", "=", 1],
            //     ["id", "=", $request->userId]
            // ])->first();
            // dd(json_encode($request['user_id']));
            // $model_has_roles = DB::table('model_has_roles')->where('model_id', $request['user_id'])->first(); // traigo el rol
            // // dd(json_encode($model_has_roles));
            // if ($model_has_roles->role_id != 2) { // si no es admin que valide lista negra
            //     // verifico si el cliente esta en lista negra

            // }

            if (!preg_match('/^\d{13}[A-Za-z]$/', $cliente->cedula)) {
                return response()->json([
                    "mensaje" => 'El cliente <a href="./cliente/editar/'.$cliente->id.'" target="_blank">'. $cliente->nombreCompleto .'</a> debe actualizar su cédula por una valida para poder avanzar.', 
                    "cliente" => $cliente
                ],400);
            }
            return response()->json(["mensaje" => "El"], 400);
            if ($cliente->categoria->tipo == "DP") {
                return response()->json(["mensaje" => "El crédito de " . $cliente->nombreCompleto . " esta fuera de los rangos. Pertenece a la lista depuración."], 400);
            }

            if ($cliente->categoria->tipo == "LN") {
                return response()->json(["mensaje" => "El crédito de " . $cliente->nombreCompleto . " esta fuera de los rangos. Pertenece a lista negra. "], 400);
            }

            if (count($facturasMora60_90) > 0) {
                foreach ($facturasMora60_90 as $facturaMora60_90) { // valido todas sus facturas, para ver si tiene una en mora
                    $fechaPasado60DiasVencimiento = Carbon::parse($facturaMora60_90->created_at)->addDays(60)->toDateTimeString();

                    if (Carbon::parse($fechaPasado60DiasVencimiento)->diffInDays($fechaHoy) >= 60) {
                        $categoriaListaNegra =  Categoria::where([
                            ['tipo', '=', "LN"],
                            ['estado', '=', 1]
                        ])->first();

                        $cliente->categoria_id = $categoriaListaNegra->id; // agrego a lista negra por estas en mora de 60 dias o mas
                        $cliente->save();

                        return response()->json(["mensaje" => "Este cliente posee alguna factura en mora de 60-90 dias", "cliente" => $cliente], 400);
                    }
                }
            }
            $montoSaldoPorAcumular = $request['monto'] + $FacturasxClientexSinPagar;
            if ($cliente->categoria->condicion == 1) { // mayor que
                if (!($montoSaldoPorAcumular > $cliente->categoria->monto_maximo)) { // si el saldo acumulado no es mayor que el monto de la categoria
                    return response()->json(["mensaje" => "El crédito de " . $cliente->nombreCompleto . " esta fuera de los rangos."], 400);
                }
            }

            if ($cliente->categoria->condicion == 2) { // menor que
                if (!($montoSaldoPorAcumular < $cliente->categoria->monto_menor)) { // si el saldo acumulado no es menor que el monto de la categoria
                    return response()->json(["mensaje" => "El crédito de " . $cliente->nombreCompleto . " esta fuera de los rangos."], 400);
                }
            }

            if ($cliente->categoria->condicion == 3) { // menor o igual que
                if (!($montoSaldoPorAcumular <= $cliente->categoria->monto_menor)) { // si el saldo acumulado no es menor o igual que el monto de la categoria
                    return response()->json(["mensaje" => "El crédito de " . $cliente->nombreCompleto . " esta fuera de los rangos."], 400);
                }
            }

            if ($cliente->categoria->condicion == 4) { // mayor o igual que
                if (!($montoSaldoPorAcumular >= $cliente->categoria->monto_maximo)) { // si el saldo acumulado no es mayor o igual que el monto de la categoria
                    return response()->json(["mensaje" => "El crédito de " . $cliente->nombreCompleto . " esta fuera de los rangos."], 400);
                }
            }

            if ($cliente->categoria->condicion == 5) { // entre
                if (
                    !($montoSaldoPorAcumular >= $cliente->categoria->monto_menor &&
                        $montoSaldoPorAcumular <= $cliente->categoria->monto_maximo)
                ) { // si el saldo acumulado no esta entre el monto menor y mayor de la categoria
                    return response()->json(["mensaje" => "El crédito de " . $cliente->nombreCompleto . " esta fuera de los rangos."], 400);
                }
            }

            // if ($montoSaldoPorAcumular >= $cliente->categoria->monto) { // en este se evalua la deuda y el monto de la factura por guardar
            //     return response()->json(["mensaje" => "El credito de " . $cliente->nombreCompleto . " esta fuera de los rangos"], 400);
            // }
            // dd(json_encode($cliente));

            DB::beginTransaction(); // inicio los transaccitions luego de acabar las validaciones al cliente

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
                    SUM(if(f.status_pagado = 1, 1, 0)) AS cantidad_finalizadas,
                    MAX(f.status_pagado_at) AS last_date_finalizada
                FROM clientes c
                INNER JOIN facturas f ON c.id = f.cliente_id
                WHERE  f.`status` = 1
                GROUP BY c.id
                ORDER BY c.id ASC
            )q ON c.id = q.cliente_id
            WHERE
                q.cantidad_factura = q.cantidad_finalizadas AND
                TIMESTAMPDIFF(MONTH,last_date_finalizada, NOW()) >= 1 AND
                c.id = " . $request['cliente_id']; // Valido que el cliente este en la lista de clientes inactivos

            $clientesInactivos = DB::select($query);

            $factura = Factura::create($facturaInsert); // inserto factura

            $tazacambio = TazaCambio::where('estado', 1)->first();
            TazaCambioFactura::create([
                'monto'         => $tazacambio->monto,
                'factura_id'    => $factura->id,
            ]); // Cargo taza de cambio de la factura

            if ($request["tipo_venta"] == 2) { // si es contado
                $recibo = ReciboHistorialContado::create([ // genero recibo de factura
                    'numero'                => $request['numero'],
                    'recibo_id'             => $request['recibo_id'],
                    'factura_id'            => $factura->id,
                    'rango'                 => $request['rango'],
                    'estado'                => 1,
                ]);
            }

            $currentDate = Carbon::now('utc')->toDateTimeString();
            foreach ($request['factura_detalle'] as $key => $productoDetalle) { // cargo productos
                $producto = Producto::firstWhere('id', $productoDetalle['producto_id']);



                if (!$producto) {
                    return response()->json(["mensaje" => "El producto no existe o fue removido"], 400);
                }

                if ($producto->estado == 0) {
                    return response()->json(["mensaje" => "El producto {$producto->descripcion} esta inactivo o eliminado"], 400);
                }

                if ($producto->stock < $productoDetalle["cantidad"]) {
                    return response()->json(["mensaje" => "El producto {$producto->descripcion} solo posee {$producto->stock} en stock"], 400);
                }

                $producto->stock = $producto->stock - $productoDetalle['cantidad'];
                $producto->save();

                $facturadetalle =  Factura_Detalle::create([
                    'producto_id'       => $productoDetalle['producto_id'],
                    'factura_id'        => $factura->id,
                    'cantidad'          => $productoDetalle['cantidad'],
                    'precio'            => $productoDetalle['precio'],
                    'precio_unidad'     => $productoDetalle['precio_unidad'],
                    'estado'            => $productoDetalle['estado']
                ]);


                // dd(json_encode($facturadetalle));

                // agrego el regalo al validar la carga del producto
                $regalos =  ProductoParaRegalo::where([
                    ['producto_id', '=',  $productoDetalle['producto_id']],
                    ['estado', '=', 1],
                ])->get();

                // $regalos = $producto->regalo;
                if (count($regalos) > 0) {
                    foreach ($regalos as $regalo) {
                        $productoRegalo =  Producto::where([
                            ['id', '=',  $regalo->id_producto_regalo],
                            ['estado', '=', 1],

                        ])->first();


                        if ($productoRegalo) {
                            if ($productoRegalo->stock < ($regalo->cantidad * $productoDetalle['cantidad'])) {
                                // si no tiene que no cargue nada
                                return response()->json(["mensaje" => "El producto {$productoRegalo->descripcion} tiene asociado regalos con stock insuficiente. Comuniquese con el admin para cargar nuevos en stock o removerlo "], 400);
                            } else {
                                // si tiene stock que guarde el regalo

                                // dd(json_encode([ // genero recibo de regalos facturados
                                //     'factura_detalle_id' => $facturadetalle->id,
                                //     'cantidad_regalada'  => $regalo->cantidad * $productoDetalle['cantidad'],
                                //     'regalo_id'          => $regalo->id,
                                //     'estado'             => 1,
                                //     '$productoRegalo->stock'             =>  $productoRegalo->stock,
                                //     '$regalo->cantidad'             => $regalo->cantidad,
                                //     '$productoDetalle[cantidad]'             => $productoDetalle['cantidad'],
                                //     '$productoRegalo->stock'             =>  $productoRegalo,
                                // ]));
                                $productoRegalo->stock =  $productoRegalo->stock - ($regalo->cantidad * $productoDetalle['cantidad']);
                                $productoRegalo->save();

                                $regalosFacturados = RegalosFacturados::create([ // genero recibo de regalos facturados
                                    'factura_detalle_id' => $facturadetalle->id,
                                    'cantidad_regalada'  => $regalo->cantidad * $productoDetalle['cantidad'],
                                    'regalo_id'          => $regalo->id,
                                    'estado'             => 1,
                                ]);
                            }
                        }
                    }
                }
            }


            // $factura_Detalle = Factura_Detalle::insert($fDetalles); // inserto detalle de factura
            // dd(json_encode($factura_Detalle));
            validarStatusPagadoGlobal($request['cliente_id']); // valido si todas las facturas y ajusto en caso de que se le deba al cliente

            validarReactivacionCliente($request['user_id'], $request['cliente_id'], $factura->id, $clientesInactivos);

            DB::commit();
            // DB::rollback();
            return response()->json([
                "factura_id" => $factura->id,
                "status" => true,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            // dd($e);
            return response()->json(["mensaje" =>  $e], 400);
        }
    }

    /**
     * Display the specified resource.
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, Request $request)
    {
        $response = [];
        $status = 400;
        $facturaEstado = 1; // Activo

        if (is_numeric($id)) {

            // if($request->input("estado") != null) $facturaEstado = $request->input("estado");
            // dd($productoEstado);
            $factura =  Factura::with('cliente', 'user')->where([
                ['id', '=', $id],
                // ['estado', '=', $facturaEstado],
            ])->first();
            // validarStatusPagadoGlobal($factura->cliente->id);
            $factura->factura_detalle = $factura->factura_detalle()->where([
                ['estado', '=', 1],
            ])->get();
            $factura->cliente->factura_historial = $factura->cliente->factura_historial()->where([
                ['estado', '=', 1],
                ['debitado', '=', 1] // 0 = en proceso | 1 = Finalizado,
            ])->get();

            if (count($factura->factura_detalle) > 0) {
                foreach ($factura->factura_detalle as $key => $productoDetalle) {
                    $producto = Producto::find($productoDetalle["producto_id"]);

                    $productoDetalle["marca"]       = $producto->marca;
                    $productoDetalle["modelo"]      = $producto->modelo;
                    // $productoDetalle["stock"]       = $producto->stock;
                    // $productoDetalle["precio"]      = $producto->precio;
                    $productoDetalle["linea"]       = $producto->linea;
                    $productoDetalle["descripcion"] = $producto->descripcion;
                    // $productoDetalle["estado"]      = $producto->estado;

                }
            }

            // if(count($factura->factura_historial)>0){
            //     foreach ($factura->factura_historial as $key => $itemHistorial) {
            //         $user = User::find($itemHistorial["user_id"]);

            //         $itemHistorial["name"]      = $user->name;
            //         $itemHistorial["apellido"]  = $user->apellido;
            //     }
            // }



            if ($factura) {
                $response = $factura;
                $status = 200;
            } else {
                $response[] = "La factura no existe o fue eliminado.";
            }
        } else {
            $response[] = "El Valor de Id debe ser numerico.";
        }

        return response()->json($response, $status);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $response = [];
        $status = 400;

        if (is_numeric($id)) {
            $cliente =  Factura::find($id);
            // dd($cliente->estado);
            if ($cliente) {
                $validation = Validator::make($request->all(), [
                    'user_id' => 'required|numeric',
                    'cliente_id' => 'required|numeric',
                    'monto' => 'required|numeric',
                    'nruc' => 'required|string',
                    'fecha_vencimiento' => 'required|date',
                    'iva' => 'required|numeric',
                    'tcambio' => 'required|numeric',
                    'estado' => 'required|numeric|max:1',
                ]);

                if ($validation->fails()) {
                    $response[] = $validation->errors();
                } else {


                    $clienteUpdate = $cliente->update([
                        'user_id' => $request['user_id'],
                        'cliente_id' => $request['cliente_id'],
                        'monto' => $request['monto'],
                        'nruc' => $request['nruc'],
                        'fecha_vencimiento' => $request['fecha_vencimiento'],
                        'iva' => $request['iva'],
                        'tcambio' => $request['tcambio'],
                        'estado' => $request['estado'],
                    ]);


                    if ($clienteUpdate) {
                        $response[] = 'La factura fue modificada con exito.';
                        $status = 200;
                    } else {
                        $response[] = 'Error al modificar los datos.';
                    }
                }
            } else {
                $response[] = "La factura no existe.";
            }
        } else {
            $response[] = "El Valor de Id debe ser numerico.";
        }

        return response()->json($response, $status);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $response = [];
        $status = 400;

        if (is_numeric($id)) {
            $factura =  Factura::find($id);

            if ($factura) {
                if ($factura->saldo_restante == $factura->monto) {
                    $facturaDelete = $factura->update([
                        'status' => 0,
                    ]);

                    if ($facturaDelete) {

                        $factura->factura_detalle;
                        // print_r(json_encode($factura));
                        foreach ($factura->factura_detalle as $key => $productoDetalle) {
                            $producto =  Producto::find($productoDetalle["producto_id"]);
                            // print_r(json_encode($producto));
                            if ($producto) {
                                $producto->stock += $productoDetalle["cantidad"];
                                $producto->update();
                            }
                            // print_r(json_encode($producto));
                            // print_r("------------------------");
                        }
                        // print_r(json_encode($factura));

                        $response[] = 'La factura fue eliminada con exito.';
                        $status = 200;
                    } else {
                        $response[] = 'Error al eliminar la factura.';
                    }
                } else {
                    $response[] = 'La factura ya debitó un abono';
                }
            } else {
                $response[] = "La factura no existe.";
            }
        } else {
            $response[] = "El Valor de Id debe ser numerico.";
        }

        return response()->json($response, $status);
    }


    public function despachar($id, Request $request)
    {
        $response = [];
        $status = 400;

        if (is_numeric($id)) {
            $factura =  Factura::find($id);

            if ($factura) {
                if (!is_null($request['despachado'])) $factura->despachado = $request['despachado'];

                $factura->update();
                $status = 200;

                if ($request['despachado'] == 1) {
                    $response[] = 'La factura fue despachada con exito.';
                } else {
                    $response[] = 'La factura ha sido devuelta a la sección de facturas despachadas.';
                }
            } else {
                $response[] = "La factura no existe.";
            }
        } else {
            $response[] = "El Valor de Id debe ser numerico.";
        }

        return response()->json($response, $status);
    }

    public function entregada($id)
    {
        $response = [];
        $status = 400;

        if (is_numeric($id)) {
            $factura =  Factura::find($id);
            // print_r($factura);
            if ($factura) {
                $factura->entregado = 1;
                $factura->save();

                // print_r($factura);
                $status = 200;
                $response[] = 'La factura fue marcada como entregada.';
            } else {
                $response[] = "La factura no existe.";
            }
        } else {
            $response[] = "El Valor de Id debe ser numerico.";
        }

        return response()->json($response, $status);
    }
}
