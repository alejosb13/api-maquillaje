<?php

namespace App\Http\Controllers;

use App\Models\Municipio;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class MunicipioController extends Controller
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

        $MunicipioQuery =  Municipio::query();

        // $facturas =  Factura::query();
        $MunicipioQuery = $MunicipioQuery->orderBy('id', 'asc')->with(["departamento", "departamento.zona"]);

        if ($request->disablePaginate == 0) {
            $MunicipioQuery = $MunicipioQuery->paginate(15);
        } else {
            $MunicipioQuery = $MunicipioQuery->get();
        }

        $response = $MunicipioQuery;

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
            'departamento' => ['required'],
        ]);

        if ($validation->fails()) {
            return response()->json([$validation->errors()], 400);
        }
        // DB::enableQueryLog();

        $Talonario = Municipio::create([
            'nombre' => $request->nombre,
            'departamento_id' => $request->departamento,
        ]);

        return response()->json([
            'mensaje' => 'Municipio creada con éxito',
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
    public function show($id)
    {
        //
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
            'departamento' => ['required'],
        ]);

        if ($validation->fails()) {
            return response()->json([$validation->errors()], 400);
        }
        // DB::enableQueryLog();

        
        $Zona =  Municipio::find($id);

        $Zona->update([
            'nombre' => $request->nombre,
            'departamento_id' => $request->departamento,
        ]);

        return response()->json([
            'mensaje' => 'Municipio editada con éxito',
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

        $Zona =  Municipio::find($id);

        if (!$Zona) {
            $response = ["mensaje" => "El municipio no existe o fue eliminado."];
            return response()->json($response, $status);
        }

        $ZonaDelete = $Zona->delete();

        if ($ZonaDelete) {
            $response = ["mensaje" => "El municipio fue eliminado con éxito."];
            $status = 200;
        } else {
            $response["mensaje"] = 'Error al eliminar el municipio.';
        }

        return response()->json($response, $status);
    }
}
