<?php

namespace App\Http\Controllers;

use App\Models\Inversion;
use App\Models\InversionDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class InversionController extends Controller
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

        $dateIni = empty($request->dateIni) ? Carbon::now() : Carbon::parse($request->dateIni);
        $dateFin = empty($request->dateFin) ? Carbon::now() : Carbon::parse($request->dateFin);

        // DB::enableQueryLog();

        $inversiones =  Inversion::query();

        // ** Filtrado por rango de fechas 
        $inversiones->when($request->allDates && $request->allDates == "false", function ($q) use ($dateIni, $dateFin) {
            return $q->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
        });

        $inversiones->when($request->estado, function ($q) use ($request) {
            return $q->where('estado', $request->estado);
        });

        // ** Filtrado por userID
        // $clientes->when($request->userId && $request->userId != 0, function ($q) use ($request) {
        //     $query = $q;
        //     // vendedor
        //     // supervisor
        //     // administrador

        //     $user = User::select("*")
        //         // ->where('estado', 1)
        //         ->where('id', $request->userId)
        //         ->first();

        //     // print_r( $request->userId);
        //     if (!$user) {
        //         return $query;
        //     }

        //     return $query->where('user_id', $user->id);
        // });


        // filtrados para campos numericos
        // $clientes->when($request->filter && is_numeric($request->filter), function ($q) use ($request) {
        //     $query = $q;
        //     // id de recibos 
        //     $filterSinNumeral = str_replace("#", "", $request->filter);

        //     $query = $query->where('id', 'LIKE', '%' . $filterSinNumeral . '%');

        //     return $query;
        // }); // Fin Filtrado


        // ** Filtrado para string
        // $clientes->when($request->filter && !is_numeric($request->filter), function ($q) use ($request) {
        //     $query = $q;

        //     // nombre cliente y empresa
        //     // $query = $query->where('nombreCompleto', 'LIKE', '%' . $request->filter . '%',"or")
        //     //     ->where('nombreEmpresa', 'LIKE', '%' . $request->filter . '%',"or")
        //     //     ->where('direccion_casa', 'LIKE', '%' . $request->filter . '%',"or");
        //     $query = $query->where(
        //         [
        //             ['nombreCompleto', 'LIKE', '%' . $request->filter . '%', "or"],
        //             ['nombreEmpresa', 'LIKE', '%' . $request->filter . '%', "or"],
        //             ['direccion_casa', 'LIKE', '%' . $request->filter . '%', "or"],
        //         ]
        //     );
        //     //     ->where('nombreEmpresa', 'LIKE', '%' . $request->filter . '%',"or")
        //     //     ->where('direccion_casa', 'LIKE', '%' . $request->filter . '%',"or");


        //     return $query;
        // }); // Fin Filtrado por cliente

        if ($request->disablePaginate == 0) {
            $inversiones = $inversiones->orderBy('created_at', 'desc')->paginate(15);
        } else {
            $inversiones = $inversiones->orderBy('created_at', 'desc')->get();
        }

        // dd(DB::getQueryLog());

        if (count($inversiones) > 0) {
            foreach ($inversiones as $inversion) {
                // dd($cliente->frecuencias);
                // validarStatusPagadoGlobal($cliente->id);
                $inversion->user;
                $inversion->inversion_detalle;
                // $clientes->categoria = $cliente->categoria;
                // $clientes->facturas = $cliente->facturas;
                // $clientes->usuario = $cliente->usuario;

                // $saldoCliente = calcularDeudaFacturasGlobal($cliente->id);

                // if ($saldoCliente > 0) {
                //     $cliente->saldo = number_format(-(float) $saldoCliente, 2);
                // }

                // if ($saldoCliente == 0) {
                //     $cliente->saldo = $saldoCliente;
                // }

                // if ($saldoCliente < 0) {
                //     // $cliente->saldo = number_format((float) str_replace("-", "", $saldoCliente), 2);
                //     $saldo_sin_guion = str_replace("-", "", $saldoCliente);
                //     $cliente->saldo = decimal(filter_var($saldo_sin_guion, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
                // }
                // dd($cliente->saldo);
            }

            $response[] = $inversiones;
        }

        $response = $inversiones;


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
            "Totales" => 'required',
            "InversionGeneral" => 'required',
            "userId" => 'required',
        ]);
        // dd($validation->errors());
        if ($validation->fails()) {
            return response()->json($validation->errors(), 400);
        }
        // dd( $request['Totales']['cantidad']);
        // dd($request->all());

        try {
            DB::beginTransaction(); // inicio los transaccitions luego de acabar las validaciones al cliente
            $Inversion = Inversion::create([
                'user_id' => $request['userId'],
                'cantidad_total' => $request['Totales']['cantidad'],
                'costo' => $request['Totales']['costo'],
                'peso_porcentual_total' => $request['Totales']['peso_porcentual'],
                'costo_total' => $request['Totales']['costo_total'],
                'precio_venta' => $request['Totales']['precio_venta'],
                'venta_total' => $request['Totales']['venta_total'],
                'costo_real_total' => $request['Totales']['costo_real'],
                'ganancia_bruta_total' => $request['Totales']['ganancia_bruta'],
                'comision_vendedor_total' => $request['Totales']['comision_vendedor'],
                'ganancia_total' => $request['Totales']['ganancia_total'],
                'envio' => $request['InversionGeneral']['envio'],
                'porcentaje_comision_vendedor' => $request['InversionGeneral']['porcentaje_comision_vendedor'],
                'estatus_cierre' => 0,

            ]);
            foreach ($request['InversionGeneral']['inversion'] as $inversionDetail) {
                $InversionDetail = InversionDetail::create([
                    'inversion_id' => $Inversion->id,
                    'codigo' => $inversionDetail['codigo'],
                    'producto' => $inversionDetail['producto'],
                    'marca' => $inversionDetail['marca'],
                    'cantidad' => $inversionDetail['cantidad'],
                    'precio_unitario' => $inversionDetail['precio_unitario'],
                    'porcentaje_ganancia' => $inversionDetail['porcentaje_ganancia'],
                    'costo' => $inversionDetail['costo'],
                    'peso_porcentual' => $inversionDetail['peso_porcentual'],
                    'peso_absoluto' => $inversionDetail['peso_absoluto'],
                    'c_u_distribuido' => $inversionDetail['c_u_distribuido'],
                    'costo_total' => $inversionDetail['costo_total'],
                    'subida_ganancia' => $inversionDetail['subida_ganancia'],
                    'precio_venta' => $inversionDetail['precio_venta'],
                    'venta' => $inversionDetail['venta'],
                    'venta_total' => $inversionDetail['venta_total'],
                    'costo_real' => $inversionDetail['costo_real'],
                    'ganancia_bruta' => $inversionDetail['ganancia_bruta'],
                    'comision_vendedor' => $inversionDetail['comision_vendedor'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => 'Usuario Insertado con exito',
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            // dd($e);
            return response()->json(["mensaje" =>  $e->getMessage()], 400);
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
        // $clienteEstado = 1; // Activo

        if (is_numeric($id)) {

            // if(!is_null($request['estado'])) $clienteEstado = $request['estado'];

            // dd($request['estado']);
            $Inversion =  Inversion::where([
                ['id', '=', $id],
                // ['estado', '=', $clienteEstado],
            ])->first();

            // $cliente =  Cliente::find($id);
            if ($Inversion) {
                $Inversion->user;
                $Inversion->inversion_detalle;

                $response = $Inversion;
                $status = 200;
            } else {
                $response[] = "La inversion no existe o fue eliminado.";
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
    public function edit($id, Request $request)
    {
        $response = [];
        $status = 400;
        // dd($request->all());
        if (is_numeric($id)) {
            $Inversion =  Inversion::find($id);

            if ($Inversion) {

                $valuesUpdate = [];
                $valuesRequested = $request->all();

                foreach ($valuesRequested as $key => $valueRq) {
                    $valuesUpdate[$key] = $valueRq;
                }

                $InversionUpdate = $Inversion->update($valuesUpdate);

                if ($InversionUpdate) {
                    $response[] = 'La inversión fue cerrada con exito.';
                    $status = 200;
                } else {
                    $response[] = 'Error al cerrar la inversión.';
                }
            } else {
                $response[] = "La inversión no existe.";
            }
        } else {
            $response[] = "El Valor de Id debe ser númerico.";
        }

        return response()->json($response, $status);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($id, Request $request)
    {
        // dd($request->all());

        $response = [];
        $status = 400;

        if (is_numeric($id)) {

            try {
                DB::beginTransaction(); // inicio los transaccitions 

                $Inversion =  Inversion::find($id);
                $Inversion->inversion_detalle_delete(); // Elimino las columnas relacionadas a la inversion
                $Inversion->update([
                    'user_id' => $request['userId'],
                    'cantidad_total' => $request['Totales']['cantidad'],
                    'costo' => $request['Totales']['costo'],
                    'peso_porcentual_total' => $request['Totales']['peso_porcentual'],
                    'costo_total' => $request['Totales']['costo_total'],
                    'precio_venta' => $request['Totales']['precio_venta'],
                    'venta_total' => $request['Totales']['venta_total'],
                    'costo_real_total' => $request['Totales']['costo_real'],
                    'ganancia_bruta_total' => $request['Totales']['ganancia_bruta'],
                    'comision_vendedor_total' => $request['Totales']['comision_vendedor'],
                    'ganancia_total' => $request['Totales']['ganancia_total'],
                    'envio' => $request['InversionGeneral']['envio'],
                    'porcentaje_comision_vendedor' => $request['InversionGeneral']['porcentaje_comision_vendedor'],
                    'estatus_cierre' => $request['InversionGeneral']['porcentaje_comision_vendedor'],
                ]);

                foreach ($request['InversionGeneral']['inversion'] as $inversionDetail) {
                    $InversionDetail = InversionDetail::create([
                        'inversion_id' => $id,
                        'codigo' => $inversionDetail['codigo'],
                        'producto' => $inversionDetail['producto'],
                        'marca' => $inversionDetail['marca'],
                        'cantidad' => $inversionDetail['cantidad'],
                        'precio_unitario' => $inversionDetail['precio_unitario'],
                        'porcentaje_ganancia' => $inversionDetail['porcentaje_ganancia'],
                        'costo' => $inversionDetail['costo'],
                        'peso_porcentual' => $inversionDetail['peso_porcentual'],
                        'peso_absoluto' => $inversionDetail['peso_absoluto'],
                        'c_u_distribuido' => $inversionDetail['c_u_distribuido'],
                        'costo_total' => $inversionDetail['costo_total'],
                        'subida_ganancia' => $inversionDetail['subida_ganancia'],
                        'precio_venta' => $inversionDetail['precio_venta'],
                        'venta' => $inversionDetail['venta'],
                        'venta_total' => $inversionDetail['venta_total'],
                        'costo_real' => $inversionDetail['costo_real'],
                        'ganancia_bruta' => $inversionDetail['ganancia_bruta'],
                        'comision_vendedor' => $inversionDetail['comision_vendedor'],
                    ]);
                }
                DB::commit();
                $response[] = "Modificación realizada";
                $status = 200;
            } catch (\Exception $e) {
                DB::rollback();
                // dd($e);
                return response()->json(["mensaje" =>  $e->getMessage()], 400);
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
            $Inversion =  Inversion::find($id);

            if ($Inversion) {
                $InversionDetail = InversionDetail::where([
                    ["inversion_id", $Inversion->id],
                ]);
                // $Inversion->inversion_detalle_delete()
                $InversionDetailUpdate = $InversionDetail->update([
                    'estado' => 0,
                ]);
                $InversionUpdate = $Inversion->update([
                    'estado' => 0,
                ]);

                if ($InversionUpdate) {
                    $response[] = 'La inversión fue eliminado con exito.';
                    $status = 200;
                } else {
                    $response[] = 'Error al eliminar la inversión.';
                }
            } else {
                $response[] = "La inversión no existe.";
            }
        } else {
            $response[] = "El Valor de Id debe ser númerico.";
        }

        return response()->json($response, $status);
    }
}
