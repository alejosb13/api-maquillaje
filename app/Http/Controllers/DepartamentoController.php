<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\Talonario;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
        $DepartamentoQuery = $DepartamentoQuery->orderBy('id', 'asc')->with(["zona", "municipios"]);

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
            'nombre' => ['required', 'string', Rule::unique('zonas', 'nombre')],
            'zona' => ['required'],
        ]);

        if ($validation->fails()) {
            return response()->json([$validation->errors()], 400);
        }
        // DB::enableQueryLog();

        $Talonario = Departamento::create([
            'nombre' => $request->nombre,
            'zona_id' => $request->zona,
        ]);

        return response()->json([
            'mensaje' => 'Departamento creado con éxito',
            'data' => [
                'id' => $Talonario->id,
            ]
        ], 201);
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
        $validation = Validator::make($request->all(), [
            // 'nombre' => 'required',
            'nombre' => ['required', 'string', Rule::unique('zonas', 'nombre')->ignore($id)],
            'zona' => ['required'],
        ]);

        if ($validation->fails()) {
            return response()->json([$validation->errors()], 400);
        }
        // DB::enableQueryLog();

        
        $Zona =  Departamento::find($id);

        $Zona->update([
            'nombre' => $request->nombre,
            'zona_id' => $request->zona,
        ]);

        return response()->json([
            'mensaje' => 'Deparamento editado con éxito',
        ], 200);
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

        $Zona =  Departamento::find($id);

        if (!$Zona) {
            $response = ["mensaje" => "El departamento no existe o fue eliminado."];
            return response()->json($response, $status);
        }

        $ZonaDelete = $Zona->delete();

        if ($ZonaDelete) {
            $response = ["mensaje" => "El departamento fue eliminada con éxito."];
            $status = 200;
        } else {
            $response["mensaje"] = 'Error al eliminar el departamento.';
        }

        return response()->json($response, $status);
    }
}
