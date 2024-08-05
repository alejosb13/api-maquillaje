<?php

namespace App\Http\Controllers;

use App\Models\DevolucionProducto;
use App\Models\Factura_Detalle;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class DevolucionProductoController extends Controller
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
        // $facturaEstado = 1; // Activo
        $parametros = [];

        if (!is_null($request['estado'])) $parametros[] = ["estado", $request['estado']];


        // dd($facturaEstado);
        $productos =  DevolucionProducto::query()->with(['factura_detalle','factura_detalle.producto' => function ($query) {
            $query->select('id', 'descripcion');
        }, 'user'])->where($parametros);

        if (empty($request->dateIni)) {
            $dateIni = Carbon::now();
        } else {
            $dateIni = Carbon::parse($request->dateIni);
        }

        if (empty($request->dateFin)) {
            $dateFin = Carbon::now();
        } else {
            $dateFin = Carbon::parse($request->dateFin);
        }

        $productos->when($request->allDates && $request->allDates == "false", function ($q) use ($dateIni, $dateFin) {
            return $q->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
        });

        // ** Filtrado para string
        $productos->when($request->filter && !is_numeric($request->filter), function ($q) use ($request) {
            $query = $q;
            $filter = $request->filter;

            return $query->where('descripcion', 'like', '%' . $filter . '%')
                ->orWhereHas('factura_detalle.producto', function ($query) use ($filter) {
                    $query->where('descripcion', 'like', '%' . $filter . '%');
                });
        }); // Fin Filtrado por cliente

        // ** Filtrado para string
        $productos->when($request->filter && is_numeric($request->filter), function ($q) use ($request) {
            $query = $q;

            $query = $query->where(
                [
                    ['id', 'LIKE', '%' . $request->filter, "or"],
                ]
            );
            return $query;
        }); // Fin Filtrado por cliente

        if (isset($request->disablePaginate) && $request->disablePaginate == 0) {
            $productos = $productos->orderBy('created_at', 'desc')->paginate(15);
        } else {
            $productos = $productos->orderBy('created_at', 'desc')->get();
        }
        // echo "<pre>";
        // print_r (json_encode($productos));
        // echo "</pre>";

        // if (count($productos) > 0) {
        //     foreach ($productos as $key => $producto) {
        //         // $producto->factura_detalle->producto;
        //         $producto->user;
        //     }

        // }

        $response = $productos;
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
            'factura_detalle_id' => 'required|numeric',
            'user_id'           => 'required|numeric',
            'cantidad'           => 'required|numeric',
            'descripcion' => 'string|nullable',
            'estado' => 'required|numeric|max:1',
        ]);
        // dd($request->all());
        // dd($validation->errors());
        if ($validation->fails()) {
            return response()->json($validation->errors(), 400);
        } else {

            DB::beginTransaction();
            try {
                $devolucionProducto = DevolucionProducto::create([
                    'factura_detalle_id' => $request['factura_detalle_id'],
                    'user_id' => $request['user_id'],
                    'cantidad' => $request['cantidad'],
                    'descripcion' => $request['descripcion'],
                    'estado'     => $request['estado'],
                ]);

                if ($devolucionProducto) {
                    actualizarCantidadDetalleProducto($request['factura_detalle_id'], $request['cantidad']);
                    devolverStockProducto($request['factura_detalle_id'], $request['cantidad']);
                }

                DB::commit();

                return response()->json([
                    // 'success' => 'Usuario Insertado con exito',
                    // 'data' =>[
                    'id' => $devolucionProducto->id,
                    // ]
                ], 201);
            } catch (Exception $e) {
                DB::rollback();
                // print_r(json_encode($e));
                return response()->json(["mensaje" => json_encode($e)], 400);
            }
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
        // $productoEstado = 1; // Activo

        if (is_numeric($id)) {

            // if($request->input("estado") != null) $productoEstado = $request->input("estado");
            // dd($productoEstado);

            $devolucionProducto =  DevolucionProducto::where([
                ['id', '=', $id],
                // ['estado', '=', $productoEstado],
            ])->first();


            // $cliente =  Cliente::find($id);
            if ($devolucionProducto) {
                $response = $devolucionProducto;
                $status = 200;
            } else {
                $response[] = "El producto no existe o fue eliminado.";
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
            $producto =  DevolucionProducto::find($id);

            if ($producto) {
                $validation = Validator::make($request->all(), [
                    'factura_detalle_id' => 'required|numeric',
                    'user_id'            => 'required|numeric',
                    'descripcion'         => 'string|nullable',
                    'cantidad'           => 'required|numeric',
                    'estado'             => 'required|numeric|max:1',
                ]);

                if ($validation->fails()) {
                    $response[] = $validation->errors();
                } else {


                    $productoUpdate = $producto->update([
                        'factura_detalle_id' => $request['factura_detalle_id'],
                        'user_id'            => $request['user_id'],
                        'descripcion'         => $request['descripcion'],
                        'cantidad'           => $request['cantidad'],
                        'estado'             => $request['estado'],
                    ]);


                    if ($productoUpdate) {
                        $response[] = 'El producto fue modificado con exito.';
                        $status = 200;
                    } else {
                        $response[] = 'Error al modificar los datos.';
                    }
                }
            } else {
                $response[] = "El producto no existe.";
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
            $DevolucionProducto =  DevolucionProducto::find($id);

            if ($DevolucionProducto) {
                $DevolucionProductoDelete = $DevolucionProducto->update([
                    'estado' => 0,
                ]);

                if ($DevolucionProductoDelete) {
                    $response[] = 'la devolucion fue eliminada con exito.';
                    $status = 200;
                } else {
                    $response[] = 'Error al eliminar la devolución.';
                }
            } else {
                $response[] = "La devolución no existe.";
            }
        } else {
            $response[] = "El Valor de Id debe ser numerico.";
        }

        return response()->json($response, $status);
    }
}
