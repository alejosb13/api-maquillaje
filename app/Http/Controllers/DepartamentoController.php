<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\DevolucionSupervisorFactura;
use App\Models\DevolucionSupervisorFacturaProducto;
use App\Models\Talonario;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DepartamentoController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $response = [];


        // DB::enableQueryLog();

        $DepartamentoQuery =  Departamento::query();

        // $facturas =  Factura::query();
        $DepartamentoQuery = $DepartamentoQuery->orderBy('created_at', 'desc')->with(["zona", "municipios"]);

        if ($request->disablePaginate == 0) {
            $DepartamentoQuery = $DepartamentoQuery->paginate(15);
        } else {
            $DepartamentoQuery = $DepartamentoQuery->get();
        }

        $response = $DepartamentoQuery; 
        
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
}
