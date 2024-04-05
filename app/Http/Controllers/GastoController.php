<?php

namespace App\Http\Controllers;

use App\Models\Gasto;
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

        $dateIni = empty($request->dateIni) ? Carbon::now() : Carbon::parse($request->dateIni);
        $dateFin = empty($request->dateFin) ? Carbon::now() : Carbon::parse($request->dateFin);

        // DB::enableQueryLog();

        $gastos =  Gasto::query();

        // ** Filtrado por rango de fechas 
        $gastos->when($request->allDates && $request->allDates == "false", function ($q) use ($dateIni, $dateFin) {
            return $q->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
        });

        $gastos->when($request->estado, function ($q) use ($request) {
            return $q->where('estado', $request->estado);
        });

        // filtrados para campos numericos
        $gastos->when($request->filter && !is_numeric($request->filter), function ($q) use ($request) {
            $query = $q;
            // id de recibos 
            $query = $query->where(
                [
                    ['numero', 'LIKE', '%' . $request->filter . '%'],
                ]
            );

            return $query;
        }); // Fin Filtrado


        if ($request->disablePaginate == 0) {
            $gastos = $gastos->orderBy('created_at', 'desc')->paginate(15);
        } else {
            $gastos = $gastos->orderBy('created_at', 'desc')->get();
        }

        // dd(DB::getQueryLog());

        if (count($gastos) > 0) {
            foreach ($gastos as $gasto) {
                $gasto->typeValueString();
                // $importacion->inversion;
                // $importacion->inversion_detalle;
            }

            $response[] = $gastos;
        }

        $response = $gastos;


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
        //
    }
}
