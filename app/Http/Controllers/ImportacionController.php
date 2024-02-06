<?php

namespace App\Http\Controllers;

use App\Models\Importacion;
use App\Models\Inversion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ImportacionController extends Controller
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

        $importaciones =  Importacion::query();

        // ** Filtrado por rango de fechas 
        $importaciones->when($request->allDates && $request->allDates == "false", function ($q) use ($dateIni, $dateFin) {
            return $q->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
        });

        $importaciones->when($request->estado, function ($q) use ($request) {
            return $q->where('estado', $request->estado);
        });

        // filtrados para campos numericos
        $importaciones->when($request->filter && is_numeric($request->filter), function ($q) use ($request) {
            $query = $q;
            // id de recibos 
            $query = $query->where(
                [
                    ['id', 'LIKE', '%' . $request->filter . '%', "or"],
                    ['numero_seguimiento', 'LIKE', '%' . $request->filter . '%', "or"],
                ]
            );

            return $query;
        }); // Fin Filtrado


        if ($request->disablePaginate == 0) {
            $importaciones = $importaciones->orderBy('created_at', 'desc')->paginate(15);
        } else {
            $importaciones = $importaciones->orderBy('created_at', 'desc')->get();
        }

        // dd(DB::getQueryLog());

        if (count($importaciones) > 0) {
            foreach ($importaciones as $importacion) {
                $importacion->inversion;
                // $importacion->inversion_detalle;
            }

            $response[] = $importaciones;
        }

        $response = $importaciones;


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

        $validation = Validator::make($request->all() ,[
            'fecha_inversion' => 'required',
            'numero_recibo' => 'required',
            'numero_inversion' => 'required',
            'monto_compra' => 'required|numeric',
            'conceptualizacion' => 'required',
            'precio_envio' => 'required',
            'inversion_id' => 'required',
        ]);

        // dd($request->all());
        // dd($validation->errors());
        if($validation->fails()) {
            $response[] =  $validation->errors();
        } else {

            $categoria = Importacion::create([
                'fecha_inversion' => $request['fecha_inversion'],
                'numero_recibo' => $request['numero_recibo'],
                'numero_inversion' => $request['numero_inversion'],
                'monto_compra' => $request['monto_compra'],
                'conceptualizacion' => $request['conceptualizacion'],
                'precio_envio' => $request['precio_envio'],
                'inversion_id' => $request['inversion_id'],
                'estado' => 1,
            ]);

            $response['id'] =  $categoria->id;
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

        if (is_numeric($id)) {

            // if(!is_null($request['estado'])) $clienteEstado = $request['estado'];

            // dd($request['estado']);
            $Importacion =  Importacion::where([
                ['id', '=', $id],
                // ['estado', '=', $clienteEstado],
            ])->first();

            // $cliente =  Cliente::find($id);
            if ($Importacion) {
                // $Inversion->user;
                $Importacion->inversion;

                $response = $Importacion;
                $status = 200;
            } else {
                $response[] = "La importación no existe o fue eliminada.";
            }
        } else {
            $response[] = "El Valor de Id debe ser numérico.";
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
            $Importacion =  Importacion::find($id);

            if ($Importacion) {
                $validation = Validator::make($request->all(), [
                    'fecha_inversion' => 'required',
                    'numero_recibo' => 'required',
                    'numero_inversion' => 'required',
                    'monto_compra' => 'required|numeric',
                    'conceptualizacion' => 'required',
                    'precio_envio' => 'required',
                    'inversion_id' => 'required',
                ]);

                if ($validation->fails()) {
                    $response[] = $validation->errors();
                } else {

                    // dd($request->all());
                    $importacionUpdate = $Importacion->update([
                        'fecha_inversion' => $request['fecha_inversion'],
                        'numero_recibo' => $request['numero_recibo'],
                        'numero_inversion' => $request['numero_inversion'],
                        'monto_compra' => $request['monto_compra'],
                        'conceptualizacion' => $request['conceptualizacion'],
                        'precio_envio' => $request['precio_envio'],
                        'inversion_id' => $request['inversion_id'],
                    ]);


                    if ($importacionUpdate) {
                        $response[] = 'Importación modificada con éxito.';
                        $status = 200;
                    } else {
                        $response[] = 'Error al modificar los datos.';
                    }
                }
            } else {
                $response[] = "El cliente no existe.";
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
        //
    }
}
