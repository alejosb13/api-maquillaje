<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Factura;
use App\Models\FacturaHistorial as Factura_Historial;
use App\Models\MetodoPago;
use App\Models\Recibo;
use App\Models\ReciboHistorial;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FacturaHistorial extends Controller
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
        $facturaEstado = 1; // Activo

        if (!is_null($request['estado'])) $facturaEstado = $request['estado'];

        // dd($facturaEstado);
        $facturasHistorial =  Factura_Historial::where('estado', $facturaEstado)->orderBy('id', "desc")->get();

        if (count($facturasHistorial) > 0) {
            foreach ($facturasHistorial as $key => $facturaHistorial) {
                $facturaHistorial->factura;
                $facturaHistorial->recibo_historial;

                $facturaHistorial->metodo_pago;
                if ($facturaHistorial->metodo_pago) {
                    $facturaHistorial->metodo_pago->tipoPago = $facturaHistorial->metodo_pago->getTipoPago();
                }

                $cliente = Cliente::find($facturaHistorial->cliente_id);
                $usuario = User::find($facturaHistorial->user_id);

                $facturaHistorial->cliente = $cliente;
                $facturaHistorial->usuario = $usuario;
                // $factura->cliente = $abonoFactura->cliente;
            }

            $response = $facturasHistorial;
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
            'cliente_id' => 'required|numeric',
            'user_id' => 'required|numeric',
            'precio' => 'required|numeric',
            'detalle_pago' => 'string|nullable',
            'autorizacion' => ['string', 'nullable', Rule::unique('metodo_pagos', 'autorizacion')->where('estado', 1)],
            'metodo_pago' => 'required|numeric',
            // 'estado' => 'required|numeric|max:1',

            'numero'       => [
                'required',
                'numeric',
                Rule::unique('recibo_historials')->where(fn($query) => $query->where('estado', 1)),
            ],
            // 'numero'       => 'required|numeric',
            'recibo_id'           => 'numeric|required',
            // 'factura_historial_id'           => 'numeric|required',
            'rango'             => 'required|string',
        ], [
            'numero.unique' => 'El número de recibo ya existe en nuestra base de datos.',
        ]);

        if ($validation->fails()) {
            return response()->json([$validation->errors()], 400);
        } else {
            // DB::enableQueryLog();

            DB::beginTransaction();
            try {

                $abono = Factura_Historial::create([
                    'cliente_id' => $request['cliente_id'],
                    'user_id'    => $request['user_id'],
                    'precio'     => $request['precio'],
                    'debitado'   => 0,
                    'estado'     => 1,
                ]);

                debitarAbonosClientes($request['cliente_id']); // logica para calcular el saldo restante de una factura y si se cierra la factura

                $recibo = ReciboHistorial::create([
                    'numero'                => $request['numero'],
                    'recibo_id'             => $request['recibo_id'],
                    'factura_historial_id'  => $abono->id,
                    'rango'                 => $request['rango'],
                    'estado'                => 1,
                ]);


                // dd($abono->id);
                $metodo = MetodoPago::create([
                    'factura_historial_id'  => $abono->id,
                    'tipo'                  => $request['metodo_pago'],
                    'detalle'               => $request['detalle_pago'],
                    'autorizacion'          => $request['autorizacion'],
                    'estado'                => 1,
                ]);

                // DB::rollback();
                DB::commit();
                return response()->json([
                    'id' => $abono->id,
                ], 201);
            } catch (Exception $e) {
                DB::rollback();

                return response()->json(["mensaje" => $e], 400);
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, Request $request)
    {
        $response = [];
        $status = 400;
        $clienteEstado = 1; // Activo

        if (is_numeric($id)) {

            if (!is_null($request['estado'])) $clienteEstado = $request['estado'];

            // dd($request['estado']);
            $abono =  Factura_Historial::where([
                ['id', '=', $id],
                // ['estado', '=', $clienteEstado],
            ])->first();

            // $cliente =  Cliente::find($id);
            // dd($abono);
            if ($abono) {
                // $abono->frecuencia = $cliente->frecuencia;
                // $abono->categoria = $cliente->categoria;
                // $abono->facturas = $cliente->facturas;
                $response = $abono;
                $status = 200;
            } else {
                $response[] = "El Abono no existe o fue eliminado.";
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
            $abono =  MetodoPago::where("factura_historial_id", $id)->first();

            if ($abono) {
                $validation = Validator::make($request->all(), [
                    'metodoPagoEditar' => 'required|numeric',
                    'detallePagoEditar' => 'required|string',
                    'autorizacion' => ['string', 'nullable', Rule::unique('metodo_pagos', 'autorizacion')->where(function ($query) {
                        return $query->where('estado', 1);
                    })->ignore($abono->id)],
                ]);

                if ($validation->fails()) {
                    $response[] = $validation->errors();
                } else {

                    // dd($request->all());
                    $abonoUpdate = $abono->update([
                        'tipo' => $request['metodoPagoEditar'],
                        'detalle' => $request['detallePagoEditar'],
                        'autorizacion' => $request['autorizacion'],
                    ]);

                    // $this->validarStatusPagado($id,$request['factura_id']);

                    if ($abonoUpdate) {
                        $response[] = 'Abono modificado con éxito.';
                        $status = 200;
                    } else {
                        $response[] = 'Error al modificar los datos.';
                    }
                }
            } else {
                $response[] = "El abono no existe.";
            }
        } else {
            $response[] = "El Valor de Id debe ser numérico.";
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

            DB::beginTransaction();
            try {
                $abono =  Factura_Historial::find($id);

                if ($abono) {
                    $abonoDelete = $abono->update([
                        'estado' => 0,
                    ]);

                    $recibo =  ReciboHistorial::find($abono->id);

                    if ($recibo) {
                        $recibo->update([
                            'estado' => 0,
                        ]);
                    }
                    validarStatusPagadoGlobal($abono->cliente_id);
                    // $this->validarStatusPagado($id);

                    if ($abonoDelete) {
                        $response[] = 'El abono fue eliminado con exito.';
                        $status = 200;
                    } else {
                        $response[] = 'Error al eliminar el abono.';
                    }
                } else {
                    $response[] = "El abono no existe.";
                }
                // DB::commit();
                DB::rollback();
            } catch (Exception $e) {
                DB::rollback();

                return response()->json(["mensaje" => "Error al insertar el abono"], 400);
            }
        } else {
            $response[] = "El Valor de Id debe ser numerico.";
        }

        return response()->json($response, $status);
    }


    // private function validarStatusPagadoGlobal($clienteID){

    //     // $cliente = $abono->cliente;
    //     $cliente = Cliente::find(1);

    //     $cliente->factura = $cliente->facturas()->where([
    //         ['status', '=', 1],
    //         // ['status_pagado', '=', 0] // 0 = en proceso | 1 = Finalizado,
    //     ])->get();

    //     $cliente->factura_historial = $cliente->factura_historial()->where([
    //         ['estado', '=', 1],
    //         // ['debitado', '=', 0] // 0 = aun no usado el abono | 1 = ya se uso el abono,
    //     ])->get();

    //     if(count($cliente->factura_historial)>0){
    //         $totalAbonos = 0 ;
    //         foreach ($cliente->factura_historial as $itemHistorial) {
    //             $totalAbonos += $itemHistorial["precio"] ;

    //             $itemHistorial["debitado"] = 1; // coloco como debitado los abonos que ya fuy sumando al acumulador
    //             $itemHistorial->update();

    //         }
    //     }

    //     if(count($cliente->factura)>0){
    //         $tieneSaldo = TRUE; // Bandera para saber cuando debo de dejar ajustar el calculo de saldo restante de las facturas

    //         foreach ($cliente->factura as $factura) {
    //             if($tieneSaldo){
    //                 // print_r (json_encode( ["monto" => $factura["monto"] , "totalAbonos"=>$totalAbonos ]));
    //                 $totalAbonos =  $totalAbonos - $factura["monto"];
    //                 if($totalAbonos < 0){ // si el precio es mas alto que el total de abonos (dejo la factura abierta y ajusto el saldo_restante)
    //                     $tieneSaldo = FALSE;
    //                     $factura["saldo_restante"] = abs($totalAbonos);

    //                 }else{// cierro la factura y el saldo restante lo dejo 0
    //                     $factura["saldo_restante"] = 0;
    //                     $factura["status_pagado"] = 1;
    //                 }

    //             }else{ // si no tiene saldo reinicio la factura
    //                 $factura["saldo_restante"] = $factura["monto"];
    //                 $factura["status_pagado"] = 0;
    //             }

    //             $factura->update();
    //         }
    //     }

    //     print_r (json_encode( ["cliente" => $cliente, "totalAbonos"=>$totalAbonos ]));

    // }

    // private function validarStatusPagado($clienteID){
    //     $cliente = Cliente::find(1);
    //     // $cliente = Cliente::find($clienteID);
    //     // $cliente = $abono->cliente;

    //     $cliente->factura = $cliente->facturas()->where([
    //         ['status', '=', 1],
    //         ['status_pagado', '=', 0] // 0 = en proceso | 1 = Finalizado,
    //     ])->get();

    //     $cliente->factura_historial = $cliente->factura_historial()->where([
    //         ['estado', '=', 1],
    //         ['debitado', '=', 0] // 0 = aun no usado el abono | 1 = ya se uso el abono,
    //     ])->get();

    //     if(count($cliente->factura_historial)>0){
    //         $totalAbonos = 0 ;
    //         foreach ($cliente->factura_historial as $itemHistorial) {
    //             $totalAbonos += $itemHistorial["precio"] ;

    //             $itemHistorial["debitado"] = 1; // coloco como debitado los abonos que ya fuy sumando al acumulador
    //             $itemHistorial->update();

    //         }
    //     }

    //     if(count($cliente->factura)>0){
    //         $tieneSaldo = TRUE; // Bandera para saber cuando debo de dejar ajustar el calculo de saldo restante de las facturas

    //         foreach ($cliente->factura as $factura) {
    //             if($tieneSaldo){
    //                 // 200 - 500 = -300 ajusta el restante
    //                 // 500 - 500 = 0  cierra factura y ajusta restante
    //                 $totalAbonos =  $totalAbonos - $factura["monto"];

    //                 if($totalAbonos < 0){ // si el precio es mas alto que el total de abonos (dejo la factura abierta y ajusto el saldo_restante)
    //                     $tieneSaldo = FALSE;
    //                     $factura["saldo_restante"] = abs($totalAbonos) ;

    //                 }else{// cierro la factura y el saldo restante lo dejo 0
    //                     $factura["saldo_restante"] = 0;
    //                     $factura["status_pagado"] = 1;
    //                 }

    //             }

    //             $factura->update();
    //         }
    //     }

    //     // print_r (json_encode( ["cliente" => $cliente, "totalAbonos"=>$totalAbonos ]));

    // }
}
