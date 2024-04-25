<?php

namespace App\Http\Controllers;

use App\Models\Gasto;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class GastoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // dd($request->all());
        $response = [];
        $status = 200;

        $response = ListadoGastos($request);

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
        $response = [];
        $status = 400;

        $validation = Validator::make($request->all(), [
            'tipo' => 'required',
            'numero' => 'required',
            'conceptualizacion' => 'required',
            'tipo_pago' => 'required',
            'monto' => 'required|numeric',
            'fecha_comprobante' => 'required',
        ]);

        // dd($request->all());
        // dd($validation->errors());
        if ($validation->fails()) {
            $response[] =  $validation->errors();
        } else {

            $Gasto = Gasto::create([
                'tipo' => $request['tipo'],
                'numero' => $request['numero'],
                'conceptualizacion' => $request['conceptualizacion'],
                'tipo_pago' => $request['tipo_pago'],
                'pago_desc' => $request['pago_desc'],
                'monto' => $request['monto'],
                'fecha_comprobante' => $request['fecha_comprobante'],
                'estado' => 1,
            ]);

            $response['id'] =  $Gasto->id;
            $status = 201;
        }
        return response()->json($response, $status);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $response = [];
        $status = 400;
        // $clienteEstado = 1; // Activo

        if (!is_numeric($id)) {
            $response[] = "El Valor de Id debe ser numérico.";
        }
        // if(!is_null($request['estado'])) $clienteEstado = $request['estado'];

        // dd($request['estado']);
        $Gasto =  Gasto::where([
            ['id', '=', $id],
            // ['estado', '=', $clienteEstado],
        ])->first();

        // $cliente =  Cliente::find($id);
        if ($Gasto) {
            // $Inversion->user;
            // $Gasto->inversion;

            $response = $Gasto;
            $status = 200;
        } else {
            $response[] = "El gasto no existe o fue eliminada.";
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
            $Gasto =  Gasto::find($id);

            if ($Gasto) {
                $validation = Validator::make($request->all(), [
                    'tipo' => 'required',
                    'numero' => 'required',
                    'conceptualizacion' => 'required',
                    'tipo_pago' => 'required',
                    'monto' => 'required|numeric',
                    'fecha_comprobante' => 'required',
                ]);

                if ($validation->fails()) {
                    $response[] = $validation->errors();
                } else {

                    // dd($request->all());
                    $GastoUpdate = $Gasto->update([
                        'tipo' => $request['tipo'],
                        'numero' => $request['numero'],
                        'conceptualizacion' => $request['conceptualizacion'],
                        'tipo_pago' => $request['tipo_pago'],
                        'pago_desc' => $request['pago_desc'],
                        'monto' => $request['monto'],
                        'fecha_comprobante' => $request['fecha_comprobante'],
                        'estado' => 1,
                    ]);


                    if ($GastoUpdate) {
                        $response[] = 'Gasto modificado con éxito.';
                        $status = 200;
                    } else {
                        $response[] = 'Error al modificar los datos.';
                    }
                }
            } else {
                $response[] = "El gasto no existe.";
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

        if (!is_numeric($id)) {
            $response["mensaje"] = "El Valor de Id debe ser numérico.";
            return response()->json($response, $status);
        }

        $Gasto =  Gasto::find($id);

        if (!$Gasto) {
            $response = ["mensaje" => "El gasto no existe o fue eliminado."];
            return response()->json($response, $status);
        }

        $GastoDelete = $Gasto->update([
            'estado' => 0,
        ]);

        if ($GastoDelete) {
            $response = ["mensaje" => "El gasto fue eliminado con éxito."];
            $status = 200;
        } else {
            $response["mensaje"] = 'Error al eliminar el gasto.';
        }

        return response()->json($response, $status);
    }

    public function EstadoResultado(Request $request)
    {
        $response = [
            'ventas_listado' => [],
            'ventas_totalMetas' => 0,
            'ventas_total' => 0,
            'costo_listado' => [],
            'costo_total_porcentaje' => 0,
            'costo_total' => 0,
            'utilidad_bruta_total' => 0,
            'gasto_general_total' => 0,
            'gasto_total' => 0,
            'gasto_total_porcentaje' => 0,
            'incentivos_vendedor_total' => 0,
            'incentivos_supervisor_total' => 0,
            'incentivos_total' => 0,
            'utilidad_neta_total' => 0,
            'utilidad_neta_total_porcentaje' => 0,
        ];

        $users = User::where([
            ["estado", "=", 1],
        ])->get();

        $dataRequest = (object) [
            "allDates" => false,
            "dateFin" => $request->dateFin,
            "dateIni" => $request->dateIni,
            "status_pagado" => 0,
            "userId" => 0,
            "allNumber" => true,
            'allUsers' => false,
            'estado' => 1,
            'disablePaginate' => 1,
        ];

        $resultCostosProductosVendidos = ListadoCostosProductosVendidos($dataRequest);
        // dd($dataRequest);
        // dd($resultCostosProductosVendidos["totalProductos"]);
        $response["costo_listado"][] = $resultCostosProductosVendidos["productos"];

        foreach ($resultCostosProductosVendidos["productos"] as $costo) {
            if ($costo->inversion) {
                $response["costo_total"] += $costo->inversion->costo * $costo->cantidad;
            } else {
                if ($costo->costo_opcional) {
                    $response["costo_total"] += $costo->costo_opcional->costo * $costo->cantidad;
                }
            }
        }

        $responseGastos = ListadoGastos($dataRequest);
        $response["gasto_general_total"] = $responseGastos["total_monto"];

        foreach ($users as $user) {
            $dataRequest->userId = $user->id;

            if (!in_array($dataRequest->userId, [20, 21, 23, 24, 25, 32])) {
                $responseIncentivo = incentivosQuery($dataRequest);
                $response["incentivos_vendedor_total"] += $responseIncentivo["total"];
            }

            $listadoVentasUser = ventasMes($dataRequest, $user);
            $response["ventas_totalMetas"] += $listadoVentasUser['meta'];
            $response["ventas_total"] += $listadoVentasUser['totalVentas'];
            $response["ventas_listado"][] = $listadoVentasUser;
        }

        $incentivosSupervisor = incentivoSupervisorQuery($dataRequest);
        $response["incentivos_supervisor_total"] = decimal($incentivosSupervisor["totalFacturaVendedores2Porciento"] + $incentivosSupervisor["totalRecuperacionVendedores"]);
        $response["incentivos_vendedor_total"]  = decimal($response["incentivos_vendedor_total"]  * 0.20);
        $response["incentivos_total"]  = decimal($response["incentivos_vendedor_total"]  + $response["incentivos_supervisor_total"]);

        $response["gasto_total"]  = decimal($response["incentivos_total"] + $response["gasto_general_total"]);
        $response["gasto_total_porcentaje"]  = ($response["ventas_total"]) ? decimal($response["gasto_total"] / $response["ventas_total"]) : 0;

        $response["costo_total_porcentaje"]  = ($response["ventas_total"]) ? decimal($response["costo_total"] / $response["ventas_total"]) : 0;

        $response["utilidad_bruta_total"] = decimal($response["ventas_total"] - $response["costo_total"]);
        $response["utilidad_neta_total"] = decimal($response["utilidad_bruta_total"] - $response["gasto_total"]);
        $response["utilidad_neta_total_porcentaje"]  = $response["ventas_total"] ? decimal($response["utilidad_neta_total"] / $response["ventas_total"]) : 0;
        return response()->json($response, 200);
    }
}
