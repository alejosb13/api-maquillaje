<?php

namespace App\Http\Controllers;

use App\Models\Recibo;
use App\Models\RecibosRangosSinTerminar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReciboController extends Controller
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
        $parametros = [];

        if (!is_null($request['estado'])) $parametros[] = ["estado", $request['estado']];
        if (!is_null($request['recibo_cerrado'])) $parametros[] = ["recibo_cerrado", $request['recibo_cerrado']];


        // dd($facturaEstado);
        $recibos =  Recibo::where($parametros)->get();

        if (count($recibos) > 0) {
            foreach ($recibos as $recibo) {
                $recibo->user;
                $recibo->recibo_historial;
            }

            $response = $recibos;
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
            'user_id'        => 'required|numeric|unique:recibos,user_id',
            'max'            => 'numeric|required',
            'min'            => 'numeric|required',
            'recibo_cerrado' => 'required|numeric|max:1',
            'estado'         => 'required|numeric|max:1',
        ]);
        if ($validation->fails()) {
            return response()->json([$validation->errors()], 400);
        } else {
            $response = array();
            $error = 401;


            if ($request['min'] < $request['max']) {
                if ($this->validNumberRange($request['min'], $request['max'], false)) {
                    $response = Recibo::create([
                        'max' => $request['max'],
                        'min' => $request['min'],
                        'user_id' => $request['user_id'],
                        'estado' => $request['estado'],
                    ]);
                    $error = 201;
                } else {
                    $response[] = array('mensaje' => "El rango numerico del recibo ya coincide con uno existente.");
                    $error = 400;
                }
            } else {
                $response[] = array('mensaje' => "El mínimo no puede ser mayor o igual al máximo.");
                $error = 400;
            }

            return response()->json($response, $error);
        }
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
        // User::whereHas("roles", function($q){ $q->where("name", "admin"); })->get()

        if (is_numeric($id)) {

            // if($request->input("estado") !== null) $clienteEstado = $request->input("estado");

            // dd($clienteEstado);
            $recibo =  Recibo::where([
                ['id', '=', $id],
                // ['estado', '=', $clienteEstado],
            ])->first();



            // $cliente =  Cliente::find($id);
            if ($recibo) {
                $recibo->user;
                $recibo->recibo_historial;

                $response = $recibo;
                $status = 200;
            } else {
                $response[] = "El recibo no existe o fue eliminado.";
            }
        } else {
            $response[] = "El recibo de Id debe ser numerico.";
        }

        return response()->json($response, $status);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getNumeroRecibo($id)
    {
        $response = [];
        $status = 400;

        if (is_numeric($id)) {
            $parametros[] = ["user_id", $id];
            $parametros[] = ["recibo_cerrado", 0];
            $parametros[] = ["estado", 1];

            $recibo =  Recibo::where($parametros)->first();

            if ($recibo) {
                // print_r (json_encode($recibo));
                $posiblesNumerosRecibos = [];

                $i = $recibo->min;
                while ($i <= $recibo->max) {
                    $posiblesNumerosRecibos[] = $i;
                    $i++;
                }
                // print_r (json_encode($posiblesNumerosRecibos));
                // validarStatusPagadoGlobal(4); // logica para calcular el saldo restante de una factura y si se cierra la factura

                $recibo->recibo_historial = $recibo->recibo_historial()->where([
                    // ['estado', '=', 1], se comento porque se quiere tomar en cuenta los recibos eliminados y no reutilizar esos numeros
                ])->get();

                if (count($recibo->recibo_historial) > 0) {
                    foreach ($recibo->recibo_historial as $itemHistorial) {
                        $posicionReciboExistente = array_search($itemHistorial->numero, $posiblesNumerosRecibos);

                        if ($posicionReciboExistente !== FALSE) array_splice($posiblesNumerosRecibos, $posicionReciboExistente, 1); // Elimino de la lista de numeros validos los recibos existentes

                    }
                }

                // Historial de recivos de Facturas Contado
                $recibo->recibo_historial_contado = $recibo->recibo_historial_contado()->where([
                    // ['estado', '=', 1], se comento porque se quiere tomar en cuenta los recibos eliminados y no reutilizar esos numeros
                ])->get();

                if (count($recibo->recibo_historial_contado) > 0) {
                    foreach ($recibo->recibo_historial_contado as $itemHistorialContado) {
                        $posicionReciboExistenteContado = array_search($itemHistorialContado->numero, $posiblesNumerosRecibos);

                        if ($posicionReciboExistenteContado !== FALSE) array_splice($posiblesNumerosRecibos, $posicionReciboExistenteContado, 1); // Elimino de la lista de numeros validos los recibos existentes

                    }
                }

                if (count($posiblesNumerosRecibos) > 0) { // si aun tiene numeros disponibles retorno el numero
                    $response = ["numero" => $posiblesNumerosRecibos[0]];
                    $status = 200;
                } else {
                    $response[] = "Error generando el recibo";
                }

                // print_r (json_encode($recibo));

            } else {
                $response[] = "Pide que asignen un talonario de recibos.";
            }
        } else {
            $response[] = "El recibo de Id debe ser numerico.";
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
            $recibo =  Recibo::find($id);

            if ($recibo) {
                $validation = Validator::make($request->all(), [
                    'user_id'        => 'required|numeric',
                    'max'            => 'numeric|required',
                    'min'            => 'numeric|required',
                    'recibo_cerrado' => 'required|numeric|max:1',
                    'estado'         => 'required|numeric|max:1',
                ]);

                if ($validation->fails()) {
                    $response[] = $validation->errors();
                } else {
                    $responseValidLastRanges = $this->validLastRanges($request['user_id']);
                    if (!$responseValidLastRanges["valido"]) {
                        return response()->json($responseValidLastRanges, $status);
                    }

                    // dd("test");

                    if ($this->validNumberRange($request['min'], $request['max'], $id)) {
                        $reciboUpdate = $recibo->update([
                            'max' => $request['max'],
                            'min' => $request['min'],
                            'user_id' => $request['user_id'],
                            'estado' => $request['estado'],
                        ]);

                        if ($reciboUpdate) {
                            $response = $recibo;
                            $status = 200;
                        } else {
                            $response[] = 'Error al modificar los datos.';
                        }
                    } else {
                        $response[] = "El rango numerico del recibo ya coincide con uno existente.";
                    }
                }
            } else {
                $response[] = "El Recibo no existe.";
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
            $recibo =  Recibo::find($id);

            if ($recibo) {
                $reciboDelete = $recibo->update([
                    'estado' => 0,
                ]);

                if ($reciboDelete) {
                    $response[] = 'El recibo fue eliminado con exito.';
                    $status = 200;
                } else {
                    $response[] = 'Error al eliminar el recibo.';
                }
            } else {
                $response[] = "El recibo no existe.";
            }
        } else {
            $response[] = "El Valor de Id debe ser numerico.";
        }

        return response()->json($response, $status);
    }


    private function validNumberRange($min, $max, $id)
    {

        if ($id) {
            $minimo = DB::table('recibos')->where([
                ['id', "!=", $id],
                ['estado', "=", 1],
            ])->whereBetween('min', [$min, $max])->get();

            $maximo = DB::table('recibos')->where([
                ['id', "!=", $id],
                ['estado', "=", 1],
            ])->whereBetween('max', [$min, $max])->get();
        } else {
            $minimo = DB::table('recibos')->where([
                ['estado', "=", 1],
            ])->whereBetween('min', [$min, $max])->get();

            $maximo = DB::table('recibos')->where([
                ['estado', "=", 1],
            ])->whereBetween('max', [$min, $max])->get();
        }

        // print_r (json_encode($minimo));
        // print_r (json_encode($maximo));
        if (count($minimo) == 0 && count($maximo) == 0) {
            return true;
        }

        return false;
    }

    private function getRecibosSinCrear($min, $max, $user_id)
    {

        $posiblesNumerosRecibos = [];

        $i = $min;
        while ($i <= $max) {
            $posiblesNumerosRecibos[] = $i;
            $i++;
        }

        $recibosExistentes = Recibo::where([
            ['user_id', $user_id],
            ['recibo_historials.rango', "$min-$max"],
            ['recibo_historials.estado', 1],
        ])
            ->select(DB::raw('recibo_historials.numero '))
            ->join('recibo_historials', 'recibo_historials.recibo_id', '=', 'recibos.id')
            ->pluck('numero')
            ->toArray();

        $recibosFaltantes = array_diff($posiblesNumerosRecibos, $recibosExistentes);

        // dd([$recibosExistentes, $recibosFaltantes]);
        return $recibosFaltantes;
    }

    private function validLastRanges($user_id)
    {
        $ListaSinTerminarOmitir = RecibosRangosSinTerminar::where([
            ['user_id', $user_id],
            ['habilitado', 0],
            ['estado', 1],
        ])
            ->select(DB::raw('rango'))
            ->pluck('rango')
            ->toArray(); // ej : [7101-7150, 7101-7150, 7101-7150]

        $recibosExistentes = Recibo::where([
            ['user_id', $user_id],
            ['recibo_historials.created_at', ">", "2023-07-01"], //  coloco esta validacion porque antes la migracion de usuario pisaba los recibos y apartir del 2023-07-01 se arreglo
        ]);

        $recibosExistentes->when(count($ListaSinTerminarOmitir) > 0, function ($q) use ($ListaSinTerminarOmitir) {
            return $q->whereNotIn('recibo_historials.rango', $ListaSinTerminarOmitir);
        });

        $recibosExistentes = $recibosExistentes->select(DB::raw('recibo_historials.rango'))
            ->join('recibo_historials', 'recibo_historials.recibo_id', '=', 'recibos.id')
            ->groupBy('recibo_historials.rango')
            ->pluck('rango')
            ->toArray(); // ej : [7101-7150, 7101-7150, 7101-7150]


        $recibosRangosSinTerminar = [];
        $totalRecibosSinTerminar = [];
        if (count($recibosExistentes) > 0) { // tiene rangos anteriores
            foreach ($recibosExistentes as $rangoRecibo) {

                $rango = explode("-", $rangoRecibo);
                $recibosFaltantes =  $this->getRecibosSinCrear($rango[0], $rango[1], $user_id);
                // dd(["a"=>$recibosFaltantes]);
                if (count($recibosFaltantes) > 0) { // le faltan recibos del rango existente
                    RecibosRangosSinTerminar::firstOrCreate([ // agrego a lista de lectura 
                        "user_id" => $user_id,
                        "rango" => $rangoRecibo,
                        "recibos_faltantes" => implode("-", $recibosFaltantes),
                        "habilitado" => 1,
                        'estado' => 1,
                    ]);
                    $totalRecibosSinTerminar = array_merge($totalRecibosSinTerminar, $recibosFaltantes);
                    $recibosRangosSinTerminar[] = $rangoRecibo;
                }
            }
        }

        if (count($recibosRangosSinTerminar) > 0) {
            return [
                "valido" => false,
                "mensaje" => "Este vendedor posee recibos disponibles en el rango <b>[" . implode(" | ", $recibosRangosSinTerminar) . "]</b> y posee un total de <b>" . count($totalRecibosSinTerminar) . "</b> recibos sin generar"
            ];
        }

        return ["valido" => true];
    }

    public function changeRangoRecibo($id)
    {
        $response = ["El numero de recibo debe ser numerico."];
        $status = 400;

        if (is_numeric($id)) {
            $recibosRangosSinTerminar =  RecibosRangosSinTerminar::find($id);


            // $cliente =  Cliente::find($id);
            if ($recibosRangosSinTerminar) {
                if ($recibosRangosSinTerminar->habilitado == 1) {
                    $recibosRangosSinTerminar->habilitado = 0;
                } else {
                    $recibosRangosSinTerminar->habilitado = 1;
                }

                $recibosRangosSinTerminar->save();

                $response = ["mensaje" => "Se modifico el estado correctamente", "info" => $recibosRangosSinTerminar];
                $status = 200;
            } else {
                $response = ["El rango de recibo no existe o fue eliminado."];
            }
        }

        return response()->json($response, $status);
    }
}
