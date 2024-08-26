<?php

namespace App\Http\Controllers;

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\DevolucionesSupervisorTrait;
use App\Models\DevolucionSupervisorFactura;
use App\Models\DevolucionSupervisorFacturaProducto;
use App\Models\Talonario;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\DB;


class DevolucionSupervisorController extends Controller
{
    use DevolucionesSupervisorTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $response = [];


        // // DB::enableQueryLog();

        // $facturas =  Factura::query();
        // // $facturas =  Factura::query()->with(['facturas']);
        // // ** Filtrado por rango de fechas 
        // $facturas->when(!$request->allDates && $request->allDates == "false", function ($q) use ($dateIni, $dateFin) {
        //     return $q->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
        // });

        // // Facturas antes de una fecha específica
        // $facturas->when($beforeDate && $beforeDate != "false", function ($q) use ($beforeDate) {
        //     return $q
        //     // ->where('created_at', '>', $beforeDate->toDateString() . " 00:00:00");
        //         ->where('created_at', '<', $beforeDate->toDateString() . " 00:00:00");
        // });

        // $facturas->when($request->estado , function ($q) use ($request) {
        //     return $q->where('status', $request->estado);
        // });

        // // $facturas =  Factura::query();
        // $facturas = $facturas->orderBy('created_at', 'desc');

        // if ($request->disablePaginate == 0) {
        //     $facturas = $facturas->paginate(15);
        // } else {
        //     $facturas = $facturas->get();
        // }

        $response = $this->devolucionesFacturasAndProductos($request);
        return response()->json($response, 200);
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
            'factura_id' => 'required|numeric',
            'monto' => 'required|numeric',
            'saldo_restante' => 'required|numeric',
            'origen' => 'required',
            'monto_devueltos' => 'required',
            'productos' => 'required',
        ]);

        if ($validation->fails()) {
            return response()->json([$validation->errors()], 400);
        }
        // DB::enableQueryLog();
        DB::beginTransaction();
        try {



            $DevolucionSupervisorFactura = DevolucionSupervisorFactura::create([
                'factura_id' => $request->factura_id,
                'monto' => $request->monto,
                'saldo_restante' => $request->saldo_restante,
                'origen' => $request->origen,
                'monto_devueltos' => $request->monto_devueltos,
                'estado' => 1,
            ]);
            $productos = $request->productos;

            // print_r($DevolucionSupervisorFactura->id);
            foreach ($productos as $key => $producto) {
                $DevolucionSupervisorFacturaProducto = DevolucionSupervisorFacturaProducto::create([
                    'devolucion_supervisor_factura_id' => $DevolucionSupervisorFactura->id,
                    'factura_detalle_id' => $producto["id"],
                    'cantidad' => $producto["cantidad"],
                    'monto' => $producto["precio"],
                    'monto_unidad' => $producto["precio_unidad"],
                    'estado' => 1,
                ]);
            }

            DB::commit();

            return response()->json([
                'mensaje' => 'deducción de Supervisor creada con éxito',
                'data' => [
                    'id' => $DevolucionSupervisorFactura->id,
                ]
            ], 201);
        } catch (Exception $e) {
            DB::rollback();
            // print_r(json_encode($e));
            return response()->json(["mensaje" => json_encode($e->getMessage())], 400);
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
        $response = null;
        $status = 400;
        // $clienteEstado = 1; // Activo

        if (!is_numeric($id)) {
            $response = ["mensaje" => "El Valor de Id debe ser numérico."];
            return response()->json($response, $status);
        }

        $Talonario =  Talonario::where([
            ['id', '=', $id],
            // ['estado', '=', $clienteEstado],
        ])->first();

        if (!$Talonario) {
            $response = ["mensaje" => "El talonario no existe o fue eliminado."];
            return response()->json($response, $status);
        }

        $Talonario->user;
        $response = ["talonario" => $Talonario];
        $status = 200;
        return response()->json($response, 200);
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

        if (!is_numeric($id)) {
            $response = ["mensaje" => "El Valor de Id debe ser numérico."];
            return response()->json($response, $status);
        }

        $Talonario =  Talonario::find($id);

        if (!$Talonario) {
            $response = ["mensaje" => "El talonario no existe o fue eliminado."];
            return response()->json($response, $status);
        }

        $validation = Validator::make($request->all(), [
            'min' => 'required|numeric',
            'max' => 'required|numeric',
            'user_id' => 'nullable|numeric',
        ]);

        if ($validation->fails()) {
            return response()->json([$validation->errors()], 400);
        }

        $talonarioUpdate = $Talonario->update([
            'min' => $request['min'],
            'max' => $request['max'],
            'user_id' => $request['user_id'],
        ]);


        if ($talonarioUpdate) {
            $response = ["mensaje" => "Talonario modificado con éxito."];
            $status = 200;
        } else {
            $response = ["mensaje" => 'Error al modificar los datos.'];
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

        $Talonario =  Talonario::find($id);

        if (!$Talonario) {
            $response = ["mensaje" => "El talonario no existe o fue eliminado."];
            return response()->json($response, $status);
        }

        $TalonarioDelete = $Talonario->update([
            'estado' => 0,
        ]);

        if ($TalonarioDelete) {
            $response = ["mensaje" => "El talonario fue eliminado con éxito."];
            $status = 200;
        } else {
            $response["mensaje"] = 'Error al eliminar el talonario.';
        }

        return response()->json($response, $status);
    }

    public function talonario(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'talonarios' => 'required',
            ]);

            if ($validation->fails()) {
                return response()->json([$validation->errors()], 400);
            }
            // DB::enableQueryLog();
            $talonarios = $request->talonarios;
            $rangoTalonario = [];
            $currenTime = Carbon::now();;


            foreach ($talonarios as $talonario) {
                // Obtener el primer valor
                $primero = reset($talonario);

                // Obtener el último valor
                $ultimo = end($talonario);

                if ($primero > $ultimo) {
                    $response = ["mensaje" => "El mínimo no puede ser mayor o igual al máximo."];
                    return response()->json($response, 400);
                }

                if (!$this->validNumberRangeTalonarios($primero, $ultimo, false)) {
                    $response = ["mensaje" => "El rango numérico de ($primero - $ultimo)  del talonario ya coincide con uno existente."];
                    return response()->json($response, 400);
                }

                if (!$this->validNumberRange($primero, $ultimo, false)) {
                    $response = ["mensaje" => "El rango numérico de ($primero - $ultimo) ya existe en un recibo."];
                    return response()->json($response, 400);
                }

                array_push($rangoTalonario, [
                    'min' => $primero,
                    'max' => $ultimo,
                    'user_id' => null,
                    'created_at' => $currenTime,
                    'updated_at' => $currenTime
                ]);
            }

            Talonario::insert($rangoTalonario);

            DB::commit();

            return response()->json([
                'mensaje' => 'Talonarios creado con éxito'
            ], 201);
        } catch (Exception $e) {
            DB::rollback();
            // print_r(json_encode($e));
            return response()->json(["mensaje" => json_encode($e)], 400);
        }
    }

    public function deducciones(Request $request)
    {
        $response = [];

        $response = $this->deduccionesSupervisor($request);
        return response()->json($response, 200);
    }

    public function deleteDeducciones($id)
    {
        $response = [];
        $status = 400;

        if (!is_numeric($id)) {
            $response["mensaje"] = "El Valor de Id debe ser numérico.";
            return response()->json($response, $status);
        }

        $Talonario =  DevolucionSupervisorFactura::find($id);

        if (!$Talonario) {
            $response = ["mensaje" => "La deducción no existe o fue eliminada."];
            return response()->json($response, $status);
        }

        $TalonarioDelete = $Talonario->update([
            'estado' => 0,
        ]);

        if ($TalonarioDelete) {
            $response = ["mensaje" => "la deducción fue eliminada con éxito."];
            $status = 200;
        } else {
            $response["mensaje"] = 'Error al eliminar la deducción.';
        }

        return response()->json($response, $status);
    }
}
