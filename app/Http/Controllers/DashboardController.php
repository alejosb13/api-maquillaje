<?php

namespace App\Http\Controllers;

use App\Models\IndicesDashboard;

class DashboardController extends Controller
{

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
            $indicesDashboard =  IndicesDashboard::where([
                ['user_id', '=', $id],
            ])->first();

            $response = $indicesDashboard;
            $status = 200;
        } else {
            $response[] = "El Valor de Id debe ser numerico.";
        }

        return response()->json($response, $status);
    }
}
