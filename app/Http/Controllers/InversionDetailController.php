<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\Factura_Detalle;
use App\Models\Producto;
use App\Models\ProductoParaRegalo;
use App\Models\RegalosFacturados;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class InversionDetailController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
        dd($request->all());
        // $validation = Validator::make($request->all(), [
        //     "inversion_id" => 'required|numeric',
        //     "codigo" => 'required|string',
        //     "producto" => 'required|string',
        //     "marca" => 'required|string',
        //     "cantidad" => 'required|numeric',
        //     "precio_unitario" => 'required|numeric',
        //     "porcentaje_ganancia" => 'required|numeric',
        //     "costo" => 'required|numeric',
        //     "peso_porcentual" => 'required|numeric',
        //     "peso_absoluto" => 'required|numeric',
        //     "c_u_distribuido" => 'required|numeric',
        //     "costo_total" => 'required|numeric',
        //     "subida_ganancia" => 'required|numeric',
        //     "precio_venta" => 'required|numeric',
        //     "venta" => 'required|numeric',
        //     "venta_total" => 'required|numeric',
        //     "costo_real" => 'required|numeric',
        //     "ganancia_bruta" => 'required|numeric',
        //     "comision_vendedor" => 'required|numeric',
        // ]);
        // dd($validation->errors());
        // if ($validation->fails()) {
        //     return response()->json($validation->errors(), 400);
        // }

        // $user = ProductoParaRegalo::create([
        //     'inversion_id' => $request['inversion_id'],
        //     'codigo' => $request['codigo'],
        //     'producto' => $request['producto'],
        //     'marca' => $request['marca'],
        //     'cantidad' => $request['cantidad'],
        //     'precio_unitario' => $request['precio_unitario'],
        //     'porcentaje_ganancia' => $request['porcentaje_ganancia'],
        //     'costo' => $request['costo'],
        //     'peso_porcentual' => $request['peso_porcentual'],
        //     'peso_absoluto' => $request['peso_absoluto'],
        //     'c_u_distribuido' => $request['c_u_distribuido'],
        //     'costo_total' => $request['costo_total'],
        //     'subida_ganancia' => $request['subida_ganancia'],
        //     'precio_venta' => $request['precio_venta'],
        //     'venta' => $request['venta'],
        //     'venta_total' => $request['venta_total'],
        //     'costo_real' => $request['costo_real'],
        //     'ganancia_bruta' => $request['ganancia_bruta'],
        //     'comision_vendedor' => $request['comision_vendedor'],
        // ]);

        return response()->json([
            // 'success' => 'Usuario Insertado con exito',
            // 'data' =>[
            // 'id' => $user->id,
            // ]
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
        $response = [];
        $status = 400;
        // $productoEstado = 1; // Activo

        if (is_numeric($id)) {

            // if($request->input("estado") != null) $productoEstado = $request->input("estado");
            // dd($productoEstado);

            $regalos =  ProductoParaRegalo::where([
                ['producto_id', '=', $id],
                ['estado', '=', 1],
            ])->get();

            // $regalos = $producto->regalo;
            if (count($regalos) > 0) {
                foreach ($regalos as $regalo) {
                    $regalo->data =  Producto::where([
                        ['id', '=',  $regalo->id_producto_regalo],
                    ])->first();
                }
            }


            // $cliente =  Cliente::find($id);
            if ($regalos) {
                $response = $regalos;
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
            $productoRegalo =  ProductoParaRegalo::find($id);

            if ($productoRegalo) {
                $validation = Validator::make($request->all(), [
                    'cantidad' => 'required|numeric',
                ]);

                if ($validation->fails()) {
                    $response[] = $validation->errors();
                } else {


                    $productoUpdate = $productoRegalo->update([
                        'cantidad' => $request['cantidad'],
                    ]);


                    if ($productoUpdate) {
                        $response[] = 'El regalo fue modificado con exito.';
                        $status = 200;
                    } else {
                        $response[] = 'Error al modificar los datos.';
                    }
                }
            } else {
                $response[] = "El regalo no existe.";
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
            $regalo =  ProductoParaRegalo::find($id);

            if ($regalo) {
                $regaloDelete = $regalo->update([
                    'estado' => 0,
                ]);

                if ($regaloDelete) {
                    $response[] = 'El regalo fue eliminado con exito.';
                    $status = 200;
                } else {
                    $response[] = 'Error al eliminar el regalo.';
                }
            } else {
                $response[] = "El regalo no existe.";
            }
        } else {
            $response[] = "El Valor de Id debe ser numerico.";
        }

        return response()->json($response, $status);
    }

    public function regaloXdetalle($id)
    {
        $response = [];
        $status = 400;

        if (is_numeric($id)) {
            $facturaDetalle = Factura_Detalle::where([
                ['id', '=', $id],
                ['estado', '=', 1],
            ])->first();

            if ($facturaDetalle) {
                // foreach ($facturaDetalle as $producto) {
                // $producto->regaloFacturado = $producto->regalos;
                foreach ($facturaDetalle->regaloFacturado as $regaloF) {
                    $regaloF->regalo;
                    $regaloF->detalle_regalo =  Producto::where([
                        ['id', '=', $regaloF->regalo->id_producto_regalo],
                    ])->first();
                }

                // $regalo->regalos->data 
                // $producto->factura_detalle =  Producto::where([
                //     ['id', '=',  $producto->regalos->id_producto_regalo],
                // ])->first();
                // }

                $response = $facturaDetalle;
                $status = 200;
            } else {
                $response[] = "El producto facturado no existe.";
            }
        } else {
            $response[] = "El Valor de Id debe ser numerico.";
        }

        return response()->json($response, $status);
    }

    public function regalosXFactura($id)
    {
        $response = [];
        $status = 400;

        if (is_numeric($id)) {
            $factura =  Factura::where([
                ['id', '=', $id],
                // ['estado', '=', $facturaEstado],
            ])->first();
            // validarStatusPagadoGlobal($factura->cliente->id);
            $factura->factura_detalle = $factura->factura_detalle()->where([
                ['estado', '=', 1],
            ])->get();

            if (count($factura->factura_detalle) > 0) {
                $regalosFacturados = [];
                foreach ($factura->factura_detalle as $detalle) {
                    $regalos = RegalosFacturados::where([
                        ['factura_detalle_id', '=', $detalle->id],
                        ['estado', '=', 1],
                    ])->get();

                    $valores = $detalle->regaloFacturado;
                    array_push($regalosFacturados, $valores);

                    // $detalle->regalos_factura->regalos = $regalos;  


                }


                $response = $regalosFacturados;
                $status = 200;


                // dd( json_encode($factura));

                // $regalos = RegalosFacturados::where([
                //     ['factura_detalle_id', '=', $id],
                //     ['estado', '=', 1],
                // ])->get();

                // if ($regalos) {
                //     foreach ($regalos as $regalo) {
                //         $regalo->regalos = $regalo->regalos;

                //         $regalo->regalos->data = $regalo->data =  Producto::where([
                //             ['id', '=',  $regalo->regalos->id_producto_regalo],
                //         ])->first();
                //     }

                //     $response = $regalos;
                //     $status = 200;
                // } else {
                //     $response[] = "El cliente no existe.";
                // }
            }
        } else {
            $response[] = "El Valor de Id debe ser numerico.";
        }

        return response()->json($response, $status);
    }
}
