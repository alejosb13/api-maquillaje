<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Factura;
use App\Models\FacturaHistorial;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ClienteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $response = [];
        $status = 200;
        $clienteEstado = 1; // Activo

        if (!is_null($request['estado'])) $clienteEstado = $request['estado'];
        // dd($request['estado']);

        $clientes =  Cliente::where('estado', $clienteEstado)->get();
        //dd( $clientes);
        if (count($clientes) > 0) {
            foreach ($clientes as $key => $cliente) {
                // dd($cliente->frecuencias);
                // validarStatusPagadoGlobal($cliente->id);
                $clientes->frecuencia = $cliente->frecuencia;
                $clientes->categoria = $cliente->categoria;
                $clientes->facturas = $cliente->facturas;
            }

            $response[] = $clientes;
        }

        return response()->json($clientes, $status);
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
            'categoria_id' => 'required|numeric',
            // 'frecuencia_id' => 'required|numeric',
            'user_id' => 'nullable|numeric',
            'nombreCompleto' => 'required|string|max:80|unique:clientes,nombreCompleto',
            'nombreEmpresa' => 'required|string|max:80',
            // 'celular' => 'required|numeric|unique:clientes,celular|digits_between:10,12',
            // 'telefono' => 'nullable|digits_between:10,12',
            // 'celular' => 'required|numeric',
            // 'telefono' => 'nullable',
            'celular' => 'required|numeric|unique:clientes,celular',
            'telefono' => 'nullable|numeric',
            'direccion_casa' => 'required|string|max:180',
            'direccion_negocio' => 'nullable|max:180',
            // 'cedula' => 'required|string|max:22|unique:clientes,cedula',
            'dias_cobro' => 'string|max:120',
            // 'fecha_vencimiento' => 'required|date',
            'estado' => 'required|numeric|max:1',

        ]);

        if ($validation->fails()) {
            return response()->json([$validation->errors()], 400);
        } else {
            // DB::enableQueryLog();
            $user = Cliente::create([
                'categoria_id' => $request['categoria_id'],
                // 'frecuencia_id' => $request['frecuencia_id'],
                'frecuencia_id' => null,
                'nombreCompleto' => $request['nombreCompleto'],
                'nombreEmpresa' => $request['nombreEmpresa'],
                'celular' => $request['celular'],
                'telefono' => $request['telefono'],
                'direccion_casa' => $request['direccion_casa'],
                'direccion_negocio' => $request['direccion_negocio'],
                'cedula' => isset($request['cedula']) && !empty($request['cedula']) ? $request['cedula'] : "",
                'dias_cobro' => $request['dias_cobro'],
                'user_id' => ($request['user_id'] > 0) ? $request['user_id'] : NULL,
                // 'fecha_vencimiento' => $request['fecha_vencimiento'],
                'estado' => $request['estado'],
            ]);
            // $query = DB::getQueryLog();
            // dd($query);
            return response()->json([
                // 'success' => 'Usuario Insertado con exito',
                // 'data' =>[
                'id' => $user->id,
                // ]
            ], 201);
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
        // $clienteEstado = 1; // Activo

        if (is_numeric($id)) {

            // if(!is_null($request['estado'])) $clienteEstado = $request['estado'];

            // dd($request['estado']);
            $cliente =  Cliente::where([
                ['id', '=', $id],
                // ['estado', '=', $clienteEstado],
            ])->first();



            // $cliente =  Cliente::find($id);
            if ($cliente) {
                $cliente->frecuencia;
                $cliente->categoria;

                $cliente->factura_historial = $cliente->factura_historial()->where([
                    ['estado', '=', 1],
                    ['debitado', '=', 0] // 0 = aun no usado el abono | 1 = ya se uso el abono,
                ])->get();

                $cliente->facturas = $cliente->facturas()->where([
                    ['status', '=', 1],
                    ['status_pagado', '=', 0] // 0 = en proceso | 1 = Finalizado,
                ])->get();

                $response = $cliente;
                $status = 200;
            } else {
                $response[] = "El cliente no existe o fue eliminado.";
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
            $cliente =  Cliente::find($id);

            if ($cliente) {
                $validation = Validator::make($request->all(), [
                    'categoria_id' => 'required|numeric',
                    // 'frecuencia_id' => 'required|numeric',
                    'user_id' => 'nullable|numeric',
                    // 'nombreCompleto' => 'required|string|max:80',
                    'nombreCompleto' => 'required|string|max:80|unique:clientes,nombreCompleto,' . $id,
                    'nombreEmpresa' => 'required|string|max:80',
                    // 'celular' => 'required|numeric|unique:clientes,celular,'.$id.'|digits_between:10,12',
                    // 'telefono' => 'nullable|digits_between:10,12',
                    'celular' => 'required|numeric|unique:clientes,celular,' . $id,
                    'telefono' => 'nullable|numeric',
                    'direccion_casa' => 'required|string|max:180',
                    'direccion_negocio' => 'nullable|max:180',
                    'cedula' => 'required|string|max:22|unique:clientes,cedula,' . $id,
                    'dias_cobro' => 'string|max:120',
                    // 'fecha_vencimiento' => 'required|date',
                    'estado' => 'required|numeric|max:1',
                ]);

                if ($validation->fails()) {
                    $response[] = $validation->errors();
                } else {

                    // dd($request->all());
                    $clienteUpdate = $cliente->update([
                        'categoria_id' => $request['categoria_id'],
                        'frecuencia_id' => null,
                        'nombreCompleto' => $request['nombreCompleto'],
                        'nombreEmpresa' => $request['nombreEmpresa'],
                        'celular' => $request['celular'],
                        'telefono' => $request['telefono'],
                        'direccion_casa' => $request['direccion_casa'],
                        'direccion_negocio' => $request['direccion_negocio'],
                        'cedula' => $request['cedula'],
                        'dias_cobro' => $request['dias_cobro'],
                        'user_id' => ($request['user_id'] > 0) ? $request['user_id'] : NULL,
                        // 'fecha_vencimiento' => $request['fecha_vencimiento'],
                        'estado' => $request['estado'],
                    ]);


                    if ($clienteUpdate) {
                        $response[] = 'Cliente modificado con exito.';
                        $status = 200;
                    } else {
                        $response[] = 'Error al modificar los datos.';
                    }
                }
            } else {
                $response[] = "El cliente no existe.";
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

        if (!is_numeric($id)) {
            $response[] = "El Valor de Id debe ser numérico.";
            return response()->json($response, $status);
        }


        $cliente =  Cliente::find($id);

        if (!$cliente) {
            $response[] = "El cliente no existe.";
            return response()->json($response, $status);
        }
        $clienteDelete = $cliente->update([
            'estado' => $cliente->estado == 1 ? 0 : 1,
        ]);

        if ($clienteDelete) {
            $response[] = 'El cliente fue eliminado con éxito.';

            $response[] = $cliente;
            $status = 200;
        } else {
            $response[] = 'Error al eliminar el cliente.';
        }



        return response()->json($response, $status);
    }


    function clienteToFactura($id)
    {
        $response = [];
        $status = 400;

        if (is_numeric($id)) {
            $cliente =  Cliente::find($id);

            if ($cliente) {
                $facturas = $cliente->facturas;
                foreach ($facturas as $key => $factura) {
                    $factura->user;
                    $factura->cliente;
                    $factura->factura_detalle;
                    // $factura->factura_historial;
                }

                $response = $facturas;
                $status = 200;
            } else {
                $response[] = "El cliente no existe.";
            }
        } else {
            $response[] = "El Valor de Id debe ser numerico.";
        }

        return response()->json($response, $status);
    }

    function calcularAbono($id)
    {
        $response = [];
        $status = 400;

        if (is_numeric($id)) {
            $cliente =  Cliente::find($id);

            if ($cliente) {
                // validarStatusPagadoGlobal($cliente->id);
                $dataAbono = calcularDeudaFacturaCliente($cliente->id);

                $response = $dataAbono;
                $status = 200;
            } else {
                $response[] = "El cliente no existe.";
            }
        } else {
            $response[] = "El Valor de Id debe ser numerico.";
        }

        return response()->json($response, $status);
    }

    function modificarDiasCobros(Request $request, $id)
    {
        $response = [];
        $status = 400;

        if (!is_numeric($id)) {
            $response[] = "El Valor de Id debe ser numérico.";
            return response()->json($response, $status);
        }

        $cliente =  Cliente::find($id);

        if (!$cliente) {
            $response[] = "El cliente no existe.";
            return response()->json($response, $status);
        }

        $dias = $request->diasCobro;
        // print_r($dias);
        $clienteUpdate = $cliente->update([
            'dias_cobro' => implode(",", $dias),
        ]);

        if ($clienteUpdate) {
            $response["mensaje"] = 'El cliente fue modificado con éxito.';

            $response["cliente"] = $cliente;
            $status = 200;
        } else {
            $response["mensaje"] = 'Error al modificar el cliente.';
        }



        return response()->json($response, $status);
    }

    function calcularDeudaVendedorCliente($id)
    {
        $response = ["deuda" => 0];
        $status = 200;

        if (is_numeric($id)) {
            $cliente =  Cliente::find($id);

            if ($cliente) {
                // validarStatusPagadoGlobal($cliente->id);
                $dataAbono = calcularDeudaFacturasGlobal($cliente->id);

                $response["deuda"] = $dataAbono;
                $response["deuda_vendedor"] = ($dataAbono > 0) ? TRUE : FALSE;
            }
        }

        return response()->json($response, $status);
    }

    function clientesVendedor(Request $request, $userId)
    {
        $response = [];
        $status = 200;
        // DB::enableQueryLog();

        $clientes =  Cliente::where('estado', 1);

        $clientes->when($userId && $userId != 0, function ($q) use ($userId) {
            $query = $q;
            // var_dump($userId);
            return $query->where('user_id', $userId);
        });

        // $clientes->when($request->saldo && $request->saldo == "false", function ($q) use ($userId) {
        //     $query = $q;
        //     $idClientesConSaldo = [];

        //     $clientesSubQuery =  Cliente::where('estado', 1)->get();
        //     foreach ($clientesSubQuery as $subQuerycliente) {
        //         $responseDeuda = calcularDeudaFacturaCliente($subQuerycliente->id);
        //         if ($responseDeuda["saldo_restante"] != 0) {
        //             $idClientesConSaldo[] = $subQuerycliente->id;
        //         }
        //     }

        //     return count($idClientesConSaldo) > 0 ? $query->wherein('id', $idClientesConSaldo) : $query;
        // });

        $clientes->when($request->filter && !is_numeric($request->filter), function ($q) use ($request) {

            return $q->where('nombreCompleto', 'LIKE', '%' . $request->filter . '%');
        }); // Fin Filtrado por cliente

        $clientes = $clientes->get();
        // $qe = DB::getQueryLog();
        // dd($qe);

        //dd( $clientes);
        if (count($clientes) > 0) {
            foreach ($clientes as $key => $cliente) {

                if ($request->saldo && $request->saldo == "true") {
                    $responseDeuda = calcularDeudaFacturaCliente($cliente->id);
                    if ($responseDeuda["saldo_restante"] != 0) {
                        // $idClientesConSaldo[] = $subQuerycliente->id;
                        $cliente->saldo_restante = decimal($responseDeuda["saldo_restante"]);
                        $cliente->categoria = $cliente->categoria;
                        $response[] = $cliente;
                    }
                } else {
                    $cliente->categoria = $cliente->categoria;
                    $response[] = $cliente;
                }
                // dd($cliente->frecuencias);
                // validarStatusPagadoGlobal($cliente->id);
                // $clientes->frecuencia = $cliente->frecuencia;
                // $clientes->facturas = $cliente->facturas;
            }
        }

        return response()->json($response, $status);
    }

    function calcularDeudaVendedorTodosClientesPorUsuario($userId)
    { // negativo es que debe el cliente y positivo es que le debemos al cliente
        $response = [];
        $status = 200;

        $clientes =  FacturaHistorial::where(
            [
                ["user_id", "=", $userId]
            ]
        )->get();

        foreach ($clientes as $cliente) {
            // print_r(json_encode($cliente));
            $dataAbono = calcularDeudaFacturasGlobal($cliente->cliente_id);

            array_push($response, [
                "cliente_id" => $cliente->cliente_id,
                "deuda" => $dataAbono,
                "deudaVendedor" => ($dataAbono > 0) ? TRUE : FALSE,
            ]);
        }

        return response()->json($response, $status);
    }

    function calcularDeudaVendedorTodosClientes()
    { // negativo es que debe el cliente y positivo es que le debemos al cliente
        $response = [];
        $status = 200;

        $clientes =  Cliente::all();

        foreach ($clientes as $cliente) {
            // print_r(json_encode($cliente));
            $dataAbono = calcularDeudaFacturasGlobal($cliente->id);
            array_push($response, [
                "cliente_id" => $cliente->id,
                "deuda" => $dataAbono,
                "deudaVendedor" => ($dataAbono > 0) ? TRUE : FALSE,
            ]);
        }

        return response()->json($response, $status);
    }
}
