<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\CommonValidations;
use App\Models\Talonario;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\DB;


class TalonariosControler extends Controller
{
    use CommonValidations;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $response = [];
        $dateIni = empty($request->dateIni) ? Carbon::now() : Carbon::parse($request->dateIni);
        $dateFin = empty($request->dateFin) ? Carbon::now() : Carbon::parse($request->dateFin);

        // DB::enableQueryLog();

        $Talonarios =  Talonario::query();

        // ** Filtrado por rango de fechas 
        $Talonarios->when($request->allDates && $request->allDates == "false", function ($q) use ($dateIni, $dateFin) {
            return $q->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
        });

        $Talonarios->when(isset($request->asignado) && $request->asignado != 2, function ($q) use ($request) {
            return $q->where('asignado', $request->asignado);
        });

        $Talonarios->when($request->estado, function ($q) use ($request) {
            return $q->where('estado', $request->estado);
        });

        $Talonarios->when(isset($request->tipoGasto) && $request->tipoGasto != 99, function ($q) use ($request) {
            return $q->where('tipo', $request->tipoGasto);
        });

        $Talonarios = $Talonarios->orderBy('min', 'asc');
        if ($request->disablePaginate == 0) {
            $Talonarios = $Talonarios->paginate(15);
        } else {
            $Talonarios = $Talonarios->get();
        }

        // dd(DB::getQueryLog());
        foreach ($Talonarios as $talonario) {
            $talonario->user;
        }

        // $gastos->totalGastos = $TotalMonto;

        // }
        $response = $Talonarios;
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
            'min' => 'required|numeric',
            'max' => 'required|numeric',
        ]);

        if ($validation->fails()) {
            return response()->json([$validation->errors()], 400);
        }
        // DB::enableQueryLog();

        if ($request['min'] > $request['max']) {
            $response = ["mensaje" => "El mínimo no puede ser mayor o igual al máximo."];
            return response()->json($response, 400);
        }

        if (!$this->validNumberRangeTalonarios($request['min'], $request['max'], false)) {
            $response = ["mensaje" => "El rango numérico del talonario ya coincide con uno existente."];
            return response()->json($response, 400);
        }

        $Talonario = Talonario::create([
            'min' => $request->min,
            'max' => $request->max,
            'user_id' => $request->user_id,
        ]);

        return response()->json([
            'mensaje' => 'Talonario creado con éxito',
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
}
