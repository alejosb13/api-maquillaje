<?php

namespace App\Http\Controllers;

use App\Models\IndicesDashboard;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function refresIndice(Request $request)
    {
        $response = ["mensaje" => "Error"];
        $status = 400;

        $inicioMesActual =  Carbon::now()->firstOfMonth()->toDateString();
        $finMesActual =  Carbon::now()->lastOfMonth()->toDateString();

        $payload = (object) [
            "allDates" => false,
            "allNumber" => true,
            "allUsers" => false,
            "dateFin" => $finMesActual,
            "dateIni" => $inicioMesActual,
            "link" => null,
            "status_pagado" => 0,
            "userId" => (int) $request->userId,
        ];

        // administrador|vendedor|supervisor
        if ($request->roleName == "administrador") {
            $resumen =  $this->dashboardAdmin($payload);
        }

        if ($request->roleName == "vendedor") {
            $resumen =  $this->dashboardVendedor($payload);
        }

        if ($request->roleName == "supervisor") {
            $resumen =  $this->dashboardSupervisor($payload);
        }

        $status = 200;
        $response["mensaje"] = "indice actualizado";

        $response["data"] = $this->ejecutarActualizacionIndices($payload, $resumen);
        return response()->json($response, $status);
        // return response()->json($resumen, $status);
    }

    private function ejecutarActualizacionIndices($payload, $resumen)
    {

        $dataIndice = [
            "cartera_total" => decimal($resumen["cartera"]["total"]),
            "ventas_meta_porcentaje" => $resumen["ventasMeta"]["meta"],
            "ventas_meta_monto" => decimal($resumen["ventasMeta"]["meta_monto"]),
            "ventas_meta_total" => decimal($resumen["ventasMeta"]["total"]),
            "recuperacionmensual_porcentaje" => decimal($resumen["recuperacionMensual"]["recuperacionPorcentaje"]),
            "recuperacionmensual_total" => decimal($resumen["recuperacionMensual"]["recuperacionTotal"]),
            "recuperacionmensual_abonos" => decimal($resumen["recuperacionMensual"]["abonosTotalLastMount"]),
            "recuperacion_total" => decimal($resumen["recuperacion"]["total"]),
            "mora30_60" => decimal($resumen["mora30_60"]),
            "mora60_90" => decimal($resumen["mora60_90"]),
            "clientes_nuevos" => $resumen["clientesNuevos"],
            "incentivos" => decimal($resumen["incentivos"]["porcentaje20"]),
            "incentivos_supervisor" => decimal($resumen["incentivosSupervisor"]),
            "clientes_inactivos" => $resumen["clientesInactivos"],
            "clientes_reactivados" => $resumen["clientesReactivados"],
            "productos_vendidos" => $resumen["productosVendidos"],
            "ventas_mes_total" => decimal($resumen["ventas_mes_total"]),
            "ventas_mes_meta" => decimal($resumen["ventas_mes_meta"]),
            "ventas_mes_porcentaje" => decimal($resumen["ventas_mes_porcentaje"]),
        ];

        if (IndicesDashboard::where('user_id', $payload->userId)->exists()) { // si existe en la tabla hago modificacion de lo contrario agrego
            IndicesDashboard::where('user_id', $payload->userId)->update($dataIndice);
        } else {
            $dataIndice['user_id'] = $payload->userId;
            IndicesDashboard::create($dataIndice);
        }

        return $dataIndice;
    }

    private function dashboardVendedor($request)
    {
        $response = [];

        $user = User::where([
            ["estado", "=", 1],
            ["id", "=", $request->userId]
        ])->first();

        $mora30_60 = mora30_60Query($request);
        $mora60_90 = mora60_90Query($request);

        $response["mora30_60"] = $this->calcularTotalSaldo($mora30_60["factura"]);
        $response["mora60_90"] = $this->calcularTotalSaldo($mora60_90["factura"]);

        $response["recuperacionMensual"] = newrecuperacionQuery($user, $request->dateIni, $request->dateFin);
        $response["cartera"] = carteraQuery($request);
        $response["recuperacion"] = RecuperacionRecibosMensualQuery($request);


        $incentivosSupervisor = incentivoSupervisorQuery($request);
        $response["incentivosSupervisor"] = $incentivosSupervisor["totalFacturaVendedores2Porciento"] + $incentivosSupervisor["totalRecuperacionVendedores"];

        $response["ventasMeta"] = ventasMetaQuery($request);

        $productosVendidos = productosVendidosPorUsuario($user, $request);
        $response["productosVendidos"] = $productosVendidos["totalProductos"];

        $response["incentivos"] = incentivosQuery($request);

        $responseVentasMes = ventasMes($request, $user);
        $response["ventas_mes_total"] = $responseVentasMes['totalVentas'];
        $response["ventas_mes_meta"] = $responseVentasMes['meta'];
        if ($response["ventas_mes_meta"] > 0) {
            $response["ventas_mes_porcentaje"] = decimal(($response["ventas_mes_total"] / $response["ventas_mes_meta"]) * 100);
        } else {
            $response["ventas_mes_porcentaje"] = 0;
        }

        $response["clientesNuevos"] = count(clienteNuevo($request));
        $response["clientesInactivos"] = count(clientesInactivosQuery($request));
        $response["clientesReactivados"] = count(clientesReactivadosQuery($request));

        return $response;
    }

    private function dashboardAdmin($request)
    {
        $response = [];

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

        $incentivosSupervisor = incentivoSupervisorQuery($request);
        $response["incentivosSupervisor"] = $incentivosSupervisor["totalFacturaVendedores2Porciento"] + $incentivosSupervisor["totalRecuperacionVendedores"];

        // productos Vendidos 
        $usersActive = User::where([
            ["estado", "=", 1]
        ])->get();

        $contadorProductosVendidos = 0;
        foreach ($usersActive as $user) {
            $responseProductosVendidosPorUsuario = productosVendidosPorUsuario($user, $request);
            $contadorProductosVendidos += $responseProductosVendidosPorUsuario["totalProductos"];
        }

        $response["productosVendidos"] = $contadorProductosVendidos;

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

        $response["clientesNuevos"] = count(clienteNuevo($dataRequest));
        $response["clientesInactivos"] = count(clientesInactivosQuery($dataRequest));
        $response["clientesReactivados"] = count(clientesReactivadosQuery($dataRequest));

        // Ventas
        $contadorVentas = [
            "total" => 0,
            "meta_monto" => 0,
            "meta" => 0,
        ];
        $responseVentasMetaQuery = ventasMetaQuery($request);
        $contadorVentas["meta_monto"] = $responseVentasMetaQuery["meta_monto"];
        $contadorVentas["total"] = $responseVentasMetaQuery["total"];

        $metaVentas =  $contadorVentas["meta_monto"] == 0 ? 0 : decimal(($contadorVentas["total"] / $contadorVentas["meta_monto"]) * 100);
        $contadorVentas["meta"] = $metaVentas;
        $response["ventasMeta"] = $contadorVentas;

        // Cartera 
        $contadorCartera = 0;
        $contadorIncentivos = [
            "porcentaje20" => 0,
            "total" => 0,
        ];
        $mora30_60List = [];
        $mora60_90List = [];
        $mora30_60ListTotal = [];
        $mora60_90ListTotal = [];

        $response['ventas_mes_total'] = 0;
        $response['ventas_mes_meta'] = 0;
        $response['ventas_mes_porcentaje'] = 0;

        foreach ($usersActive as $user) {
            $dataRequest->userId = $user->id;

            $responseCarteraQuery = carteraQuery($dataRequest);
            $contadorCartera += $responseCarteraQuery["total"];

            $responseIncentivo = incentivosQuery($dataRequest);
            if (!in_array($user->id, [20, 21, 23, 24, 25, 32])) {
                $contadorIncentivos["total"] += $responseIncentivo["total"];
            }

            $mora30_60List = mora30_60Query($dataRequest)["factura"];
            if (count($mora30_60List) > 0) {
                foreach ($mora30_60List as  $mora30_60) {
                    $mora30_60ListTotal[] = $mora30_60;
                }
            }

            $mora60_90List = mora60_90Query($dataRequest)["factura"];
            if (count($mora60_90List) > 0) {
                foreach ($mora60_90List as  $mora60_90) {
                    $mora60_90ListTotal[] = $mora60_90;
                }
            }

            $responseVentasMes = ventasMes($dataRequest, $user);
            $response["ventas_mes_total"] += $responseVentasMes['totalVentas'];
            $response["ventas_mes_meta"] += $responseVentasMes['meta'];
        }

        if ($response["ventas_mes_meta"] > 0) {
            $response["ventas_mes_porcentaje"] = decimal(($response["ventas_mes_total"] / $response["ventas_mes_meta"]) * 100);
        } else {
            $response["ventas_mes_porcentaje"] = 0;
        }

        $response["mora30_60"] = $this->calcularTotalSaldo($mora30_60ListTotal);
        $response["mora60_90"] = $this->calcularTotalSaldo($mora60_90ListTotal);

        $response["cartera"] = ["total" => $contadorCartera];
        // Fin Cartera y ventas 

        $contadorIncentivos["porcentaje20"] = decimal($contadorIncentivos["total"] * 0.20);
        $response["incentivos"] = $contadorIncentivos;

        return $response;
    }

    private function dashboardSupervisor($request)
    {
        $response = [];

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

        $incentivosSupervisor = incentivoSupervisorQuery($request);
        $response["incentivosSupervisor"] = $incentivosSupervisor["totalFacturaVendedores2Porciento"] + $incentivosSupervisor["totalRecuperacionVendedores"];

        // productos Vendidos 
        $usersActive = User::where([
            ["estado", "=", 1]
        ])->get();

        $contadorProductosVendidos = 0;
        foreach ($usersActive as $user) {
            $responseProductosVendidosPorUsuario = productosVendidosPorUsuario($user, $request);
            $contadorProductosVendidos += $responseProductosVendidosPorUsuario["totalProductos"];
        }

        $response["productosVendidos"] = $contadorProductosVendidos;

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

        $response["clientesNuevos"] = count(clienteNuevo($dataRequest));
        $response["clientesInactivos"] = count(clientesInactivosQuery($dataRequest));
        $response["clientesReactivados"] = count(clientesReactivadosQuery($dataRequest));

        // Ventas
        $contadorVentas = [
            "total" => 0,
            "meta_monto" => 0,
            "meta" => 0,
        ];
        $responseVentasMetaQuery = ventasMetaQuery($request);
        $contadorVentas["meta_monto"] = $responseVentasMetaQuery["meta_monto"];
        $contadorVentas["total"] = $responseVentasMetaQuery["total"];

        $metaVentas =  $contadorVentas["meta_monto"] == 0 ? 0 : decimal(($contadorVentas["total"] / $contadorVentas["meta_monto"]) * 100);
        $contadorVentas["meta"] = $metaVentas;
        $response["ventasMeta"] = $contadorVentas;

        // Cartera 
        $contadorCartera = 0;
        $contadorIncentivos = [
            "porcentaje20" => 0,
            "total" => 0,
        ];
        $mora30_60List = [];
        $mora60_90List = [];
        $mora30_60ListTotal = [];
        $mora60_90ListTotal = [];

        $response['ventas_mes_total'] = 0;
        $response['ventas_mes_meta'] = 0;
        $response['ventas_mes_porcentaje'] = 0;

        foreach ($usersActive as $user) {
            $dataRequest->userId = $user->id;

            $responseCarteraQuery = carteraQuery($dataRequest);
            $contadorCartera += $responseCarteraQuery["total"];

            $responseIncentivo = incentivosQuery($dataRequest);
            if (!in_array($user->id, [20, 21, 23, 24, 25, 32])) {
                $contadorIncentivos["total"] += $responseIncentivo["total"];
            }

            $mora30_60List = mora30_60Query($dataRequest)["factura"];
            if (count($mora30_60List) > 0) {
                foreach ($mora30_60List as  $mora30_60) {
                    $mora30_60ListTotal[] = $mora30_60;
                }
            }

            $mora60_90List = mora60_90Query($dataRequest)["factura"];
            if (count($mora60_90List) > 0) {
                foreach ($mora60_90List as  $mora60_90) {
                    $mora60_90ListTotal[] = $mora60_90;
                }
            }

            $responseVentasMes = ventasMes($dataRequest, $user);
            $response["ventas_mes_total"] += $responseVentasMes['totalVentas'];
            $response["ventas_mes_meta"] += $responseVentasMes['meta'];
        }

        $response["ventas_mes_porcentaje"] = decimal(($response["ventas_mes_total"] / $response["ventas_mes_meta"]) * 100);

        $response["mora30_60"] = $this->calcularTotalSaldo($mora30_60ListTotal);
        $response["mora60_90"] = $this->calcularTotalSaldo($mora60_90ListTotal);

        $response["cartera"] = ["total" => $contadorCartera];

        $contadorIncentivos["porcentaje20"] = decimal($contadorIncentivos["total"] * 0.20);
        $response["incentivos"] = $contadorIncentivos;

        return $response;
    }

    private function calcularTotalSaldo($facturas)
    {
        // Log::info(json_encode($facturas));

        $total = 0;

        if (count($facturas) > 0) {
            foreach ($facturas as $factura) {
                $total += $factura->saldo_restante;
            }
        }

        return $total;
    }
}
