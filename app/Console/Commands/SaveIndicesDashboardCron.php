<?php

namespace App\Console\Commands;

use App\Models\IndicesDashboard;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaveIndicesDashboardCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'save:indice';
    // protected $signature = 'meta:recuperacion';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cron que guarda en la DB los datos del dashboard';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $this->ejecutarActualizacionAdmin();
        $this->ejecutarActualizacionVendedores();
        $this->ejecutarActualizacionSupervisor();
        
        // $this->info('Successfully sent daily quote to everyone.');
        $this->info(response()->json(["status"=>"Successfully sent daily quote to everyone."], 200));

        // return json_encode(["status"=>true]);
    }

    private function ejecutarActualizacionSupervisor()
    {
        $inicioMesActual =  Carbon::now()->firstOfMonth()->toDateString();
        $finMesActual =  Carbon::now()->lastOfMonth()->toDateString();

        // { ejemplo de payload
        // "link": null,
        // "allUsers": false,
        // "status_pagado": 0,
        // "allNumber": true,
        // "allDates": false,
        // "userId": 25,
        // "dateIni": "2023-08-01",
        // "dateFin": "2023-08-31"
        //   }

        $payloadAdmin = (object) [
            "link" => null,
            "allDates" => false,
            "status_pagado" => 0,
            "allNumber" => true,
            "allUsers" => false,
            "userId" => 25,
            "dateFin" => $finMesActual,
            "dateIni" => $inicioMesActual,
        ];

        $resumen =  $this->resumenDashboardAdmin($payloadAdmin);
        // Log::info(json_encode($resumen));

        $usuarios =  User::select(DB::raw('users.*,model_has_roles.*,roles.name AS role_name '))
            ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where([
                ['users.estado', '=', 1],
                ['model_has_roles.role_id', '=', 4],
            ])
            ->get();

        foreach ($usuarios as $usuario) {
            $dataIndice = [
                // 'user_id' => $usuario->id,
                "cartera_total" => decimal($resumen["cartera"]["total"]),
                "ventas_meta_porcentaje" => $resumen["ventasMeta"]["meta"],
                "ventas_meta_monto" => decimal($resumen["ventasMeta"]["meta_monto"]),
                "ventas_meta_total" => decimal($resumen["ventasMeta"]["total"]),
                "recuperacionmensual_porcentaje" => decimal($resumen["recuperacionMensual"]["recuperacionPorcentaje"]),
                "recuperacionmensual_total" => decimal($resumen["recuperacionMensual"]["recuperacionTotal"]),
                "recuperacionmensual_abonos" => decimal($resumen["recuperacionMensual"]["abonosTotalLastMount"]),
                "recuperacion_total" => $resumen["recuperacion"]["total"],
                "mora30_60" => $resumen["mora30_60"],
                "mora60_90" => $resumen["mora60_90"],
                "clientes_nuevos" => $resumen["clientesNuevos"],
                "incentivos" => decimal($resumen["incentivos"]["porcentaje20"]),
                "incentivos_supervisor" => $resumen["incentivosSupervisor"],
                "clientes_inactivos" => $resumen["clientesInactivos"],
                "clientes_reactivados" => $resumen["clientesReactivados"],
                "productos_vendidos" => $resumen["productosVendidos"],
            ];
            IndicesDashboard::where('user_id', $usuario->id)->update($dataIndice);
            // IndicesDashboard::create($dataIndice);
            // Log::info(json_encode($dataIndice));
        }
    }
    private function ejecutarActualizacionAdmin()
    {
        $inicioMesActual =  Carbon::now()->firstOfMonth()->toDateString();
        $finMesActual =  Carbon::now()->lastOfMonth()->toDateString();

        // { ejemplo de payload
        // "link": null,
        // "allUsers": false,
        // "status_pagado": 0,
        // "allNumber": true,
        // "allDates": false,
        // "userId": 25,
        // "dateIni": "2023-08-01",
        // "dateFin": "2023-08-31"
        //   }

        $payloadAdmin = (object) [
            "link" => null,
            "allDates" => false,
            "status_pagado" => 0,
            "allNumber" => true,
            "allUsers" => false,
            "userId" => 25,
            "dateFin" => $finMesActual,
            "dateIni" => $inicioMesActual,
        ];

        $resumen =  $this->resumenDashboardAdmin($payloadAdmin);
        // Log::info(json_encode($resumen));

        $usuarios =  User::select(DB::raw('users.*,model_has_roles.*,roles.name AS role_name '))
            ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where([
                ['users.estado', '=', 1],
                ['model_has_roles.role_id', '=', 2],
            ])
            ->get();

        foreach ($usuarios as $usuario) {
            $dataIndice = [
                // 'user_id'=> $usuario->id,
                "cartera_total" => decimal($resumen["cartera"]["total"]),
                "ventas_meta_porcentaje" => $resumen["ventasMeta"]["meta"],
                "ventas_meta_monto" => decimal($resumen["ventasMeta"]["meta_monto"]),
                "ventas_meta_total" => decimal($resumen["ventasMeta"]["total"]),
                "recuperacionmensual_porcentaje" => decimal($resumen["recuperacionMensual"]["recuperacionPorcentaje"]),
                "recuperacionmensual_total" => decimal($resumen["recuperacionMensual"]["recuperacionTotal"]),
                "recuperacionmensual_abonos" => decimal($resumen["recuperacionMensual"]["abonosTotalLastMount"]),
                "recuperacion_total" => $resumen["recuperacion"]["total"],
                "mora30_60" => $resumen["mora30_60"],
                "mora60_90" => $resumen["mora60_90"],
                "clientes_nuevos" => $resumen["clientesNuevos"],
                "incentivos" => decimal($resumen["incentivos"]["porcentaje20"]),
                "incentivos_supervisor" => $resumen["incentivosSupervisor"],
                "clientes_inactivos" => $resumen["clientesInactivos"],
                "clientes_reactivados" => $resumen["clientesReactivados"],
                "productos_vendidos" => $resumen["productosVendidos"],
            ];
            IndicesDashboard::where('user_id', $usuario->id)->update($dataIndice);
            // IndicesDashboard::create($dataIndice);
            // Log::info(json_encode($dataIndice));
        }
    }

    private function ejecutarActualizacionVendedores()
    {

        $inicioMesActual =  Carbon::now()->firstOfMonth()->toDateString();
        $finMesActual =  Carbon::now()->lastOfMonth()->toDateString();

        $usuarios =  User::select(DB::raw('users.*,model_has_roles.*,roles.name AS role_name '))
            ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where([
                ['users.estado', '=', 1],
                ['model_has_roles.role_id', '=', 3],
            ])
            ->get();

        // { ejemplo de payload
        //     allDates: false;
        //     allNumber: true;
        //     allUsers: false;
        //     dateFin: "2023-08-31";
        //     dateIni: "2023-08-01";
        //     link: null;
        //     status_pagado: 0;
        //     userId: 25;
        //   }

        foreach ($usuarios as $usuario) {

            $payload = (object) [
                "allDates" => false,
                "allNumber" => true,
                "allUsers" => false,
                "dateFin" => $finMesActual,
                "dateIni" => $inicioMesActual,
                "link" => null,
                "status_pagado" => 0,
                "userId" => $usuario->id,
            ];

            $resumen =  $this->resumenDashboard($payload);
            // Log::info(json_encode($resumen));
            // Log::info($resumen["cartera"]["total"]);
            $dataIndice = [
                "cartera_total" => decimal($resumen["cartera"]["total"]),
                "ventas_meta_porcentaje" => $resumen["ventasMeta"]["meta"],
                "ventas_meta_monto" => decimal($resumen["ventasMeta"]["meta_monto"]),
                "ventas_meta_total" => decimal($resumen["ventasMeta"]["total"]),
                "recuperacionmensual_porcentaje" => decimal($resumen["recuperacionMensual"]["recuperacionPorcentaje"]),
                "recuperacionmensual_total" => decimal($resumen["recuperacionMensual"]["recuperacionTotal"]),
                "recuperacionmensual_abonos" => decimal($resumen["recuperacionMensual"]["abonosTotalLastMount"]),
                "recuperacion_total" => $resumen["recuperacion"]["total"],
                "mora30_60" => $resumen["mora30_60"],
                "mora60_90" => $resumen["mora60_90"],
                "clientes_nuevos" => $resumen["clientesNuevos"],
                "incentivos" => decimal($resumen["incentivos"]["porcentaje20"]),
                "incentivos_supervisor" => $resumen["incentivosSupervisor"],
                "clientes_inactivos" => $resumen["clientesInactivos"],
                "clientes_reactivados" => $resumen["clientesReactivados"],
                "productos_vendidos" => $resumen["productosVendidos"],
            ];
            IndicesDashboard::where('user_id', $usuario->id)->update($dataIndice);
            // Log::info(json_encode($dataIndice));
        }
    }

    private function resumenDashboardAdmin($request)
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
        $mora30_60ListTotal = [];
        $mora60_90ListTotal = [];
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
                    $mora30_60ListTotal[] = $mora30_60;
                }
            }

            $mora60_90List = mora60_90Query($dataRequest)["factura"];
            if (count($mora60_90List) > 0) {
                foreach ($mora60_90List as  $mora60_90) {
                    $mora60_90ListTotal[] = $mora60_90;
                }
            }
        }

        $response["mora30_60"] = $this->calcularTotalSaldo($mora30_60ListTotal);
        $response["mora60_90"] = $this->calcularTotalSaldo($mora60_90ListTotal);

        $response["cartera"] = ["total" => $contadorCartera];
        
        $metaVentas =  $contadorVentas["meta_monto"]==0?0 : decimal(($contadorVentas["total"] / $contadorVentas["meta_monto"]) * 100);
        $contadorVentas["meta"] = $metaVentas;
        $response["ventasMeta"] = $contadorVentas;

        // Fin Cartera y ventas 

        $contadorIncentivos["porcentaje20"] = decimal($contadorIncentivos["total"] * 0.20);
        $response["incentivos"] = $contadorIncentivos;

        return $response;
    }

    private function resumenDashboard($request)
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

        $response["clientesNuevos"] = count(clienteNuevo($request));
        $response["clientesInactivos"] = count(clientesInactivosQuery($request));
        $response["clientesReactivados"] = count(clientesReactivadosQuery($request));

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
