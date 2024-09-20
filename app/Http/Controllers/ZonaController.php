<?php

namespace App\Http\Controllers;

use Illuminate\Validation\Rule;
use App\Models\Zona;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ZonaController extends Controller
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

        $ZonaQuery =  Zona::query();

        // $facturas =  Factura::query();
        $ZonaQuery->when($request->estado, function ($q) use ($request) {
            return $q->where('estado', $request->estado);
        });

        $ZonaQuery = $ZonaQuery->orderBy('created_at', 'desc')->with(["departamentos", "departamentos.municipios"]);

        if ($request->disablePaginate == 0) {
            $ZonaQuery = $ZonaQuery->paginate(15);
        } else {
            $ZonaQuery = $ZonaQuery->get();
        }

        $response = $ZonaQuery;

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
        ]);

        if ($validation->fails()) {
            return response()->json([$validation->errors()], 400);
        }
        // DB::enableQueryLog();

        $Talonario = Zona::create([
            'nombre' => $request->nombre,
        ]);

        return response()->json([
            'mensaje' => 'Zona creada con éxito',
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
        ]);

        if ($validation->fails()) {
            return response()->json([$validation->errors()], 400);
        }
        // DB::enableQueryLog();

        
        $Zona =  Zona::find($id);

        $Talonario = $Zona->update([
            'nombre' => $request->nombre,
        ]);

        return response()->json([
            'mensaje' => 'Zona editada con éxito',
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

        $Zona =  Zona::find($id);

        if (!$Zona) {
            $response = ["mensaje" => "La zona no existe o fue eliminado."];
            return response()->json($response, $status);
        }

        $ZonaDelete = $Zona->delete();

        if ($ZonaDelete) {
            $response = ["mensaje" => "La zona fue eliminada con éxito."];
            $status = 200;
        } else {
            $response["mensaje"] = 'Error al eliminar la zona.';
        }

        return response()->json($response, $status);
    }
}
