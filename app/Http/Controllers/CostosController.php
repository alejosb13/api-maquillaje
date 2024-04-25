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
        $result = ListadoCostosProductosVendidos($request);
        
        return response()->json($result, 200);
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
