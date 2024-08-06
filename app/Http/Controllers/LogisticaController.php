<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ClientesInactivosNotas;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LogisticaController extends Controller
{
    function carteraDate(Request $request)
    {

        $response = carteraQuery($request);
        return response()->json($response, 200);
    }

    // recuperacion
    function reciboDate(Request $request)
    {
        $response = RecuperacionRecibosMensualQuery($request);
        return response()->json($response, 200);
    }

    function Mora30A60(Request $request)
    {
        $response = mora30_60Query($request);
        return response()->json($response, 200);
    }

    function Mora60A90(Request $request)
    {
        $response = mora60_90Query($request);
        return response()->json($response, 200);
    }


    function clienteDate(Request $request)
    {
        $response = clienteNuevo($request);
        return response()->json($response, 200);
    }

    function clienteInactivo(Request $request)
    {
        $response = clientesInactivosQuery($request);
        return response()->json($response, 200);
    }


    // recuperacion
    function incentivo(Request $request)
    {
        $response = incentivosQuery($request);
        return response()->json($response, 200);
    }

    // recuperacion
    function incentivoSupervisor(Request $request)
    {
        $response = incentivoSupervisorQuery($request);
        return response()->json($response, 200);
    }

    function estadoCuenta(Request $request)
    {
        $response = queryEstadoCuenta($request->cliente_id);
        $response["cliente"] = Cliente::find($request->cliente_id);

        return response()->json($response, 200);
    }

    function productoLogistica(Request $request)
    {
        $response = [
            'productos' => 0,
            'monto_total' => 0,
        ];

        $productos =  Producto::where('estado', 1)->get();

        if (count($productos) > 0) {
            foreach ($productos as $producto) {
                $precio = number_format((float) ($producto->precio * $producto->stock), 2, ".", "");
                $response["productos"] += $producto->stock;
                $response["monto_total"] += $precio;
            }
        }

        return response()->json($response, 200);
    }

    function clientesReactivados(Request $request)
    {
        $response = clientesReactivadosQuery($request);
        return response()->json($response, 200);
    }


    function ventasDate(Request $request)
    {

        $response = ventasMetaQuery($request);
        return response()->json($response, 200);
    }

    function ventasMensual(Request $request)
    {
        $response = [
            'listadoVentas' => [],
            'totalMetas' => 0,
            'totalVentas' => 0,
            'porcentaje' => 0,
        ];

        $users = User::where([
            ["estado", "=", 1],
        ])->get();
        $dataRequest = (object) [
            "allDates" => false,
            "dateFin" => $request->dateFin,
            "dateIni" => $request->dateIni,
            "status_pagado" => 0,
            "userId" => 0,
            "allNumber" => false,
            'allUsers' => false,
        ];

        foreach ($users as $user) {
            $dataRequest->userId = $user->id;

            $response["listadoVentas"][] = ventasMes($dataRequest, $user);
        }

        foreach ($response["listadoVentas"] as $ventaUsuario) {
            $response["totalMetas"] += $ventaUsuario['meta'];
            $response["totalVentas"] += $ventaUsuario['totalVentas'];
        }
        $response["porcentaje"] = $response["totalMetas"] > 0 ? decimal(($response["totalVentas"] / $response["totalMetas"]) * 100) : 0;

        return response()->json($response, 200);
    }

    function recuperacion(Request $request)
    {
        $response = [];
        $users = User::where([
            ["estado", "=", 1]
        ])->whereNotIn('id', [32])->get();

        // dd([$request->dateIni,$request->dateFin]);
        foreach ($users as $user) {
            // $user->meta;
            // $responsequery = recuperacionQuery($user);
            $responsequery = newrecuperacionQuery($user, $request->dateIni, $request->dateFin);
            array_push($response, $responsequery);
        }
        return response()->json($response, 200);
    }

    function productosVendidos(Request $request)
    {
        $response = [];
        $users = User::where([
            ["estado", "=", 1]
        ])->get();
        // $users = Recibo::where([
        //     ["estado","=",1]
        // ])->get();

        foreach ($users as $user) {
            $role_id = DB::table('model_has_roles')->where('model_id', $user->id)->first();
            $user->role_id = $role_id->role_id;
            $responsequery = productosVendidosPorUsuario($user, $request);

            array_push($response, $responsequery);
        }
        return response()->json($response, 200);
    }

    function resumenDashboard(Request $request)
    {
        $response = [];

        // {
        //     "dateIni": "2023-08-01",
        //     "dateFin": "2023-08-31",
        //     "userId": 25,
        //     "numRecibo": null
        // }
        $user = User::where([
            ["estado", "=", 1],
            ["id", "=", $request->userId]
        ])->first();

        $response["mora30_60"] = mora30_60Query($request);
        $response["mora60_90"] = mora60_90Query($request);

        $response["recuperacionMensual"] = newrecuperacionQuery($user, $request->dateIni, $request->dateFin);
        $response["cartera"] = carteraQuery($request);
        $response["recuperacion"] = RecuperacionRecibosMensualQuery($request);


        $response["clientesNuevos"] = clienteNuevo($request);
        $response["incentivos"] = incentivosQuery($request);
        $response["incentivosSupervisor"] = incentivoSupervisorQuery($request);
        $response["clientesInactivos"] = clientesInactivosQuery($request);
        $response["clientesReactivados"] = clientesReactivadosQuery($request);
        $response["ventasMeta"] = ventasMetaQuery($request);
        $response["productosVendidos"] = productosVendidosPorUsuario($user, $request);

        return response()->json($response, 200);
    }

    function resumenDashboardAdmin(Request $request)
    {
        $response = [];

        // $user = User::where([
        //     ["estado", "=", 1],
        //     ["id", "=", $request->userId]
        // ])->first();

        // Recuperacion mensual
        $users = User::where([
            ["estado", "=", 1]
        ])->get();

        $totalMetas = 0;
        $totalAbonos = 0;
        $contadorUsers = 0;
        foreach ($users as $usuario) {
            $responserNewrecuperacionQuery = newrecuperacionQuery($usuario, $request->dateIni, $request->dateFin);

            if ($responserNewrecuperacionQuery["recuperacionTotal"] > 0) {
                $totalMetas += $responserNewrecuperacionQuery["recuperacionTotal"];
                $totalAbonos += $responserNewrecuperacionQuery["abonosTotalLastMount"];
                $contadorUsers++;
            }
        }

        $response["recuperacionMensual"] = [
            "abonosTotalLastMount" => $totalAbonos,
            "recuperacionTotal" => $totalMetas,
            "contadorUsers" => $contadorUsers,
            "recuperacionPorcentaje" => decimal(($totalAbonos / $totalMetas) * 100),
        ];
        // Fin Recuperacion mensual

        $response["recuperacion"] = RecuperacionRecibosMensualQuery($request);

        $response["incentivosSupervisor"] = incentivoSupervisorQuery($request);

        // productos Vendidos 
        $usersActive = User::where([
            ["estado", "=", 1]
        ])->get();

        $contadorProductosVendidos = 0;
        foreach ($usersActive as $user) {
            $responseProductosVendidosPorUsuario = productosVendidosPorUsuario($user, $request);
            $contadorProductosVendidos += $responseProductosVendidosPorUsuario["totalProductos"];
        }

        $response["productosVendidos"] = ["totalProductos" => $contadorProductosVendidos];
        // Fin productos Vendidos 

        $dataRequest = (object) [
            "allDates" => false,
            "dateFin" => $request->dateFin,
            "dateIni" => $request->dateIni,
            "status_pagado" => 0,
            "userId" => 0,
            "allNumber" => true,
            'allUsers' => false,
        ];

        $response["clientesNuevos"] = clienteNuevo($dataRequest);
        $response["clientesInactivos"] = clientesInactivosQuery($dataRequest);
        $response["clientesReactivados"] = clientesReactivadosQuery($dataRequest);

        // Cartera y ventas
        $contadorCartera = 0;
        $contadorVentas = [
            "total" => 0,
            "meta_monto" => 0,
            "meta" => 0,
        ];

        $contadorIncentivos = [
            "porcentaje20" => 0,
            "total" => 0,
        ];
        $mora30_60List = [];
        $mora60_90List = [];
        foreach ($usersActive as $user) {
            $dataRequest->userId = $user->id;

            $responseCarteraQuery = carteraQuery($dataRequest);
            $contadorCartera += $responseCarteraQuery["total"];

            $responseVentasMetaQuery = ventasMetaQuery($dataRequest);
            if (!in_array($user->id, [20, 21, 23, 24, 32])) {
                $contadorVentas["meta_monto"] += $responseVentasMetaQuery["meta_monto"];
                $contadorVentas["total"] += $responseVentasMetaQuery["total"];
            }

            $responseIncentivo = incentivosQuery($dataRequest);
            if (!in_array($user->id, [20, 21, 23, 24, 25, 32])) {
                $contadorIncentivos["total"] += $responseIncentivo["total"];
            }

            $mora30_60List = mora30_60Query($dataRequest)["factura"];
            if (count($mora30_60List) > 0) {
                foreach ($mora30_60List as  $mora30_60) {
                    $response["mora30_60"]["factura"][] = $mora30_60;
                }
            }

            $mora60_90List = mora60_90Query($dataRequest)["factura"];
            if (count($mora60_90List) > 0) {
                foreach ($mora60_90List as  $mora60_90) {
                    $response["mora60_90"]["factura"][] = $mora60_90;
                }
            }
        }

        $response["cartera"] = ["total" => $contadorCartera];

        $contadorVentas["meta"] = decimal(($contadorVentas["total"] / $contadorVentas["meta_monto"]) * 100);
        $response["ventasMeta"] = $contadorVentas;

        // Fin Cartera y ventas 

        $contadorIncentivos["porcentaje20"] = decimal($contadorIncentivos["total"] * 0.20);
        $response["incentivos"] = $contadorIncentivos;

        return response()->json($response, 200);
    }

    function clienteInactivoNotas(Request $request)
    {
        // Validar los datos de entrada
        $validation = Validator::make($request->all(), [
            'cliente_id' => 'required|numeric',
            'tipos' => 'required|numeric',
        ]);

        if ($validation->fails()) {
            return response()->json([$validation->errors()], 400);
        }

        // Obtener el cliente por ID
        $cliente = ClientesInactivosNotas::where("cliente_id", $request->cliente_id)->first();

        if ($cliente) {
            // Si el cliente existe, actualizar el tipo
            $cliente->tipos = $request->tipos;
            $cliente->save();

            $response = [
                'status' => 'success',
                'message' => 'Cliente actualizado con éxito.',
                'cliente' => $cliente,
            ];
        } else {
            // Si el cliente no existe, crear un nuevo registro
            $cliente = ClientesInactivosNotas::create([
                'cliente_id' => $request->cliente_id,
                'tipos' => $request->tipos,
            ]);

            $response = [
                'status' => 'success',
                'message' => ' creado con éxito.',
                'cliente' => $cliente,
            ];
        }

        return response()->json($response, 200);
    }
}
