<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\Factura_Detalle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CostosController extends Controller
{
    public function getAllProductosVendidos(Request $request)
    {
        $user = (object) [
            "id" => 25,
        ];
        // dd([$user,$request->all()]);

        $id = $user->id;
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

        if (!$request->allDates) {
            $facturasStorage = $facturasStorage->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
        }

        // $facturasStorage = $facturasStorage->where('user_id', $id);


        $facturas = $facturasStorage->get();
        foreach ($facturas as $factura) {
            $factura->factura_detalle = $factura->factura_detalle()->where([
                ['estado', '=', 1],
            ])->get();

            if (count($factura->factura_detalle) > 0) {
                foreach ($factura->factura_detalle as $factura_detalle) {
                    array_push($idProductos, $factura_detalle->id);
                    $contadorProductos = $contadorProductos + $factura_detalle->cantidad;
                    // $factura_detalle->producto  = $factura_detalle->producto; 
                }
            }

            // $response["productos"][] = $factura->factura_detalle; 
            // array_push($response["productos"],$factura->factura_detalle) ; 
        }

        if (count($idProductos) > 0) {


            $productos = Factura_Detalle::join('productos', 'productos.id', '=', 'factura_detalles.producto_id')
                ->wherein('factura_detalles.id', $idProductos)
                ->leftjoin('inversion_details', 'productos.id', '=', 'inversion_details.codigo')
                ->select(DB::raw('SUM(factura_detalles.cantidad) AS cantidad, productos.*, inversion_details.codigo'))
                ->groupBy('factura_detalles.producto_id')->paginate(15);
                $response["totalProductos"] = $productos;
        }

        // $response = $facturas;
        // $response["id"] = $idProductos;


        return response()->json($productos, 200);
    }
}
