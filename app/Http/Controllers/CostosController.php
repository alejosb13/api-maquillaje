<?php

namespace App\Http\Controllers;

use App\Models\CostosVentas;
use App\Models\Factura;
use App\Models\Factura_Detalle;
use App\Models\InversionDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CostosController extends Controller
{
    public function getAllProductosVendidos(Request $request)
    {
        $user = (object) [
            "id" => 25,
        ];
        // dd([$user,$request->all()]);

        $response = [
            'totalProductos' => 0,
            'productos' => [],
            'user' => $user,
        ];
        $contadorProductos = 0;
        $idProductos = [];

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
        $facturasStorage = Factura::select("*")
        // ->where('status_pagado', $request->status_pagado ? $request->status_pagado : 0) // si envian valor lo tomo, si no por defecto asigno por pagar = 0
        ->where('status', 1);
        
        // dd([ $request->allDates]);
        if ($request->allDates == "false") {
            $facturasStorage = $facturasStorage->whereBetween('created_at', 
            [
                $dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"
            ]);
            // dd([ $dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
        }

        $facturas = $facturasStorage->get();

        foreach ($facturas as $factura) {
            $factura->factura_detalle = $factura->factura_detalle()->where([
                ['estado', '=', 1],
            ])->get();

            if (count($factura->factura_detalle) > 0) {
                foreach ($factura->factura_detalle as $factura_detalle) {
                    array_push($idProductos, $factura_detalle->producto_id);
                    $contadorProductos = $contadorProductos + $factura_detalle->cantidad;
                    // $factura_detalle->producto  = $factura_detalle->producto; 
                }
            }

            // $response["productos"][] = $factura->factura_detalle; 
            // array_push($response["productos"],$factura->factura_detalle) ; 
        }

        // if (count($idProductos) > 0) {


            $productoVendidos = Factura_Detalle::join('productos', 'productos.id', '=', 'factura_detalles.producto_id')
                ->wherein('productos.id', $idProductos)
                ->where([
                    ["productos.estado", "=", "1"],
                    ["factura_detalles.estado", "=", "1"]
                ])
                ->select(
                    DB::raw(
                        'productos.id,
                        SUM(factura_detalles.cantidad) AS cantidad, 
                        productos.marca, 
                        productos.modelo, 
                        productos.linea, 
                        productos.descripcion'

                    )
                )
                ->groupBy('factura_detalles.producto_id')
                ->paginate(15);
            // ->get();

            foreach ($productoVendidos as $productoVendido) {
                $productoVendido->costo_opcional = CostosVentas::where([
                    ["producto_id", "=", $productoVendido->id]
                ])->first();

                $productoVendido->inversion = InversionDetail::where([
                    ["codigo", "=", $productoVendido->id],
                    ["updated_at", "=", DB::raw('(
                            SELECT MAX(updated_at)
                            FROM inversion_details
                        )')]
                ])->first();
            }
            $response["totalProductos"] = $productoVendidos;
        // }

        // $response = $facturas;
        // $response["id"] = $idProductos;


        return response()->json($response["totalProductos"], 200);
    }

    public function saveCostosVentas(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'producto_id' => 'required|numeric',
            'costo' => 'required|numeric',
        ]);
        // dd($request->all());
        // dd($validation->errors());
        if ($validation->fails()) {
            return response()->json($validation->errors(), 400);
        } else {

            $costoVenta = CostosVentas::create([
                'producto_id' => $request['producto_id'],
                'costo' => $request['costo'],
            ]);

            return response()->json([
                // 'success' => 'Usuario Insertado con exito',
                // 'data' =>[
                'id' => $costoVenta->id,
                // ]
            ], 201);
        }
    }

    public function updateCostosVentas($id, Request $request)
    {
        $validation = Validator::make($request->all(), [
            'producto_id' => 'required|numeric',
            'costo' => 'required|numeric',
        ]);
        // dd($request->all());
        // dd($validation->errors());
        if ($validation->fails()) {
            return response()->json($validation->errors(), 400);
        } else {
            $costoVenta =  CostosVentas::find($id);
            if (!$costoVenta) {
                $response[] = "El costo no existe.";
            }

            $costoVentaUpdate = $costoVenta->update([
                'producto_id' => $request['producto_id'],
                'costo' => $request['costo'],
            ]);

            return response()->json([
                'success' => 'Costo actualizado con éxito',

            ], 200);
        }
    }

    public function deleteCostoVenta($id)
    {
        $response = [];
        $status = 400;

        if (is_numeric($id)) {
            $costoVenta =  CostosVentas::find($id);

            if ($costoVenta) {
                if ($costoVenta->estado == 1) {
                    $costoVentaDelete = $costoVenta->update([
                        'estado' => 0,
                    ]);
                } else {
                    $costoVentaDelete = $costoVenta->update([
                        'estado' => 1,
                    ]);
                }

                if ($costoVentaDelete) {
                    $response[] = 'El costo fue eliminado con éxito.';
                    $status = 200;
                } else {
                    $response[] = 'Error al eliminar el usuario.';
                }
            } else {
                $response[] = "El costo no existe.";
            }
        } else {
            $response[] = "El Valor de Id debe ser numérico.";
        }

        return response()->json($response, $status);
    }
}
