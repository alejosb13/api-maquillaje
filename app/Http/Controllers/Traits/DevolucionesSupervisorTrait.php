<?php

namespace App\Http\Controllers\Traits;

use App\Models\Factura;
use App\Models\DevolucionSupervisorFactura;
use App\Models\Factura_Detalle;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

trait DevolucionesSupervisorTrait
{
    public function devolucionesFacturasAndProductos($request)
    {
        // $excludedFacturaIds = DevolucionSupervisorFactura::where('estado', 1)
        //     ->pluck('factura_id')
        //     ->toArray();

        // $excludedProductosIds = DevolucionSupervisorFacturaProducto::where('estado', 1)
        //     ->pluck('factura_detalle_id')
        //     ->toArray();
        $excludedFacturaIds = [];
        $excludedProductosIds = [];
        $facturasDeduccionSupervisor = DevolucionSupervisorFactura::where('estado', 1)->with(['producto_deduccion'])->get();

        // $facturaDetalleIds = DevolucionSupervisorFactura::whereHas('producto_deduccion', function ($query) {
        //     $query->where('estado', 1);
        // })->pluck('factura_detalle_id')->toArray();

        // dd(print($facturasDeduccionSupervisor));

        foreach ($facturasDeduccionSupervisor as $facturaDeduccion) {
            if ($facturaDeduccion->origen == "pd") {
                foreach ($facturaDeduccion->producto_deduccion as $producto_deducido) {
                    array_push($excludedProductosIds, $producto_deducido->factura_detalle_id);
                }
            } else {
                array_push($excludedFacturaIds, $facturaDeduccion->factura_id);
            }
        }

        // $query = "SELECT
        //         dp_facturas.factura_id,
        //         dp_facturas.user_id,
        //         dp_facturas.cliente_id,
        //         dp_facturas.monto,
        //         dp_facturas.saldo_restante,
        //         dp_facturas.fecha_vencimiento,
        //         dp_facturas.iva,
        //         dp_facturas.tipo_venta,
        //         dp_facturas.status_pagado,
        //         dp_facturas.despachado,
        //         dp_facturas.entregado,
        //         dp_facturas.status,
        //         dp_facturas.created_at,
        //         dp_facturas.updated_at,
        //         dp_facturas.monto_devueltos
        //     FROM (
        //         SELECT
        //             f.id AS factura_id,
        //             f.user_id,
        //             f.cliente_id,
        //             f.monto,
        //             f.saldo_restante,
        //             f.fecha_vencimiento,
        //             f.iva,
        //             f.tipo_venta,
        //             f.status_pagado,
        //             f.despachado,
        //             f.entregado,
        //             f.status,
        //             f.created_at,
        //             f.updated_at,
        //             SUM(dp.cantidad * fd.precio_unidad) AS monto_devueltos
        //         FROM
        //             devolucion_productos dp
        //         JOIN
        //             factura_detalles fd ON dp.factura_detalle_id = fd.id
        //         JOIN
        //             facturas f ON fd.factura_id = f.id
        //         GROUP BY f.id

        //         UNION ALL

        //         SELECT
        //             df.factura_id,
        //             f.user_id,
        //             f.cliente_id,
        //             f.monto,
        //             f.saldo_restante,
        //             f.fecha_vencimiento,
        //             f.iva,
        //             f.tipo_venta,
        //             f.status_pagado,
        //             f.despachado,
        //             f.entregado,
        //             f.status,
        //             f.created_at,
        //             f.updated_at,
        //             f.monto AS monto_devueltos
        //         FROM
        //             devolucion_facturas df
        //         JOIN
        //             facturas f ON df.factura_id = f.id
        //     ) dp_facturas";


        // $devoluciones = DB::select($query);
        // return $devoluciones ;

        $dateIni = empty($request->dateIni) || !isset($request->dateIni) ? Carbon::now()->startOfMonth() : Carbon::parse($request->dateIni);
        $dateFin = empty($request->dateFin) || !isset($request->dateFin) ? Carbon::now()->endOfMonth() : Carbon::parse($request->dateFin);
        $beforeDate =  Carbon::parse($request->dateIni)->startOfMonth();
        // Devoluciones de productos
        $productos_devueltos = Factura::select([
            'facturas.id as factura_id',
            'facturas.user_id',
            'facturas.cliente_id',
            'facturas.monto',
            'facturas.saldo_restante',
            'facturas.fecha_vencimiento',
            'facturas.iva',
            'facturas.tipo_venta',
            'facturas.status_pagado',
            'facturas.despachado',
            'facturas.entregado',
            'facturas.status',
            'facturas.created_at',
            'facturas.updated_at',
            DB::raw('SUM(devolucion_productos.cantidad * factura_detalles.precio_unidad) AS monto_devueltos'),
            DB::raw(" GROUP_CONCAT(factura_detalles.id SEPARATOR ',') AS ids_agrupados "),
            DB::raw("'pd' AS origen"), // Agrega la columna origen
            DB::raw('MIN(devolucion_productos.created_at) AS devolucion_created_at') 
        ])
            ->join('factura_detalles', 'factura_detalles.factura_id', '=', 'facturas.id')
            ->join('devolucion_productos', 'devolucion_productos.factura_detalle_id', '=', 'factura_detalles.id')
            ->where('devolucion_productos.estado', "1")
            ->when($beforeDate && $beforeDate != "false", function ($query) use ($beforeDate) {
                return $query->whereDate('facturas.created_at', '<', $beforeDate->toDateString() . " 00:00:00");
            })
            ->when($beforeDate && $beforeDate != "false", function ($query) use ($dateIni, $dateFin) {
                return $query->whereBetween('devolucion_productos.created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
            })
            ->when(count($excludedProductosIds) > 0, function ($query) use ($excludedProductosIds) {
                return $query->whereNotIn('devolucion_productos.factura_detalle_id', $excludedProductosIds);
            })
            ->groupBy('facturas.id');
        // ->when(count($excludedFacturaIds) > 0, function ($query) use ($excludedFacturaIds) {
        //     return $query->whereNotIn('facturas.id', $excludedFacturaIds);
        // });

        // Devoluciones de facturas completas
        $facturas_devueltas = Factura::select([
            'facturas.id as factura_id',
            'facturas.user_id',
            'facturas.cliente_id',
            'facturas.monto',
            'facturas.saldo_restante',
            'facturas.fecha_vencimiento',
            'facturas.iva',
            'facturas.tipo_venta',
            'facturas.status_pagado',
            'facturas.despachado',
            'facturas.entregado',
            'facturas.status',
            'facturas.created_at',
            'facturas.updated_at',
            DB::raw('facturas.monto AS monto_devueltos'),
            DB::raw("GROUP_CONCAT(factura_detalles.id SEPARATOR ',') AS ids_agrupados "),
            DB::raw("'fd' AS origen"), // Agrega la columna origen
            DB::raw('MIN(devolucion_facturas.created_at) AS devolucion_created_at')
        ])
            ->join('factura_detalles', 'factura_detalles.factura_id', '=', 'facturas.id')
            ->join('devolucion_facturas', 'devolucion_facturas.factura_id', '=', 'facturas.id')
            ->when($beforeDate && $beforeDate != "false", function ($query) use ($beforeDate) {
                return $query->whereDate('facturas.created_at', '<', $beforeDate->toDateString() . " 00:00:00");
            })
            ->when($beforeDate && $beforeDate != "false", function ($query) use ($dateIni, $dateFin) {
                return $query->whereBetween('devolucion_facturas.created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
            })
            ->when(count($excludedFacturaIds) > 0, function ($query) use ($excludedFacturaIds) {
                return $query->whereNotIn('facturas.id', $excludedFacturaIds);
            })->groupBy('facturas.id');

        // Unión de ambos resultados
        $returns = $productos_devueltos->unionAll($facturas_devueltas)->orderBy('created_at', 'desc');


        if ($request->disablePaginate == 0) {
            $returns = $returns->paginate(15);
        } else {
            $returns = $returns->get();
        }

        foreach ($returns as $dataResponse) {
            $dataResponse->factura = Factura::with(['cliente'])->where('id', $dataResponse->factura_id)->first();

            // if(){
            $dataRequest = (object) [
                'disablePaginate' => 1,
            ];

            $facturas_Detalles_id = explode(",", $dataResponse->ids_agrupados);
            $dataResponse->productos = $this->devolucionesProductos($dataRequest, $facturas_Detalles_id);
            // $dataResponse->productos = Factura_Detalle::whereIn('id', $facturas_Detalles_id)->get();

            // }
        }

        return  $returns;
    }

    public function devolucionesProductos($request, $facturaId)
    {
        // SELECT 
        //     fd.*
        // FROM 
        //     factura_detalles fd
        // INNER JOIN 
        //     devolucion_productos dp ON fd.id = dp.factura_detalle_id
        // WHERE 
        //    fd.factura_id = 5298

        // UNION ALL

        // SELECT 
        //     fd.*
        // FROM 
        //     factura_detalles fd
        // inner JOIN 
        //    devolucion_facturas df ON fd.factura_id = df.factura_id
        // WHERE 
        //     fd.factura_id = 5298

        // Consulta para obtener detalles de productos devueltos
        $productosDevueltosQuery = Factura_Detalle::select('factura_detalles.producto_id', 'factura_detalles.created_at', 'factura_detalles.estado', 'factura_detalles.id', 'factura_detalles.precio', 'factura_detalles.precio_unidad', 'factura_detalles.updated_at', 'productos.descripcion', 'devolucion_productos.cantidad')
            ->join('devolucion_productos', 'factura_detalles.id', '=', 'devolucion_productos.factura_detalle_id')
            ->join('productos', 'productos.id', '=', 'factura_detalles.producto_id')
            ->whereIn('factura_detalles.id', $facturaId);

        // Consulta para obtener todos los detalles de la factura si ha sido devuelta completamente
        $facturaCompletamenteDevueltaQuery = Factura_Detalle::select('factura_detalles.producto_id', 'factura_detalles.created_at', 'factura_detalles.estado', 'factura_detalles.id', 'factura_detalles.precio', 'factura_detalles.precio_unidad', 'factura_detalles.updated_at', 'productos.descripcion', 'factura_detalles.cantidad')
            ->join('devolucion_facturas', 'factura_detalles.factura_id', '=', 'devolucion_facturas.factura_id')
            ->join('productos', 'productos.id', '=', 'factura_detalles.producto_id')
            ->whereIn('factura_detalles.id', $facturaId);

        // Unión de ambos resultados
        $returns = $productosDevueltosQuery->unionAll($facturaCompletamenteDevueltaQuery);

        if ($request->disablePaginate == 0) {
            $returns = $returns->paginate(15);
        } else {
            $returns = $returns->get();
        }

        return  $returns;
    }

    public function deduccionesSupervisor($request)
    {
        // dd(var_dump($request->all()));
        $dateIni = empty($request->dateIni) || !isset($request->dateIni) ? Carbon::now()->startOfMonth() : Carbon::parse($request->dateIni);
        $dateFin = empty($request->dateFin) || !isset($request->dateFin) ? Carbon::now()->endOfMonth() : Carbon::parse($request->dateFin);
        $total = 0;
        // dd([$request->all(), $dateIni->toDateString() , $dateFin ]);
        $DevolucionSupervisorFacturaQuery =  DevolucionSupervisorFactura::query()->with([
            'factura',
            // 'producto_deduccion.factura_detalle',
            'producto_deduccion.factura_detalle.producto'
        ]);
        // $facturas =  Factura::query()->with(['facturas']);
        // ** Filtrado por rango de fechas 
        $DevolucionSupervisorFacturaQuery->when(isset($request->allDates) && ($request->allDates == "false" || !$request->allDates), function ($q) use ($dateIni, $dateFin) {
            return $q->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
        });
        $DevolucionSupervisorFacturaQuery->when($request->estado == 1, function ($q) use ($request) {
            return $q->where('estado', $request->estado);
        });
        // $facturas =  Factura::query();
        $DevolucionSupervisorFacturaQuery = $DevolucionSupervisorFacturaQuery->orderBy('created_at', 'desc');

        $total = $DevolucionSupervisorFacturaQuery->sum('monto_devueltos');

        $sql = $DevolucionSupervisorFacturaQuery->toSql();
        $bindings = $DevolucionSupervisorFacturaQuery->getBindings();


        if ($request->disablePaginate == 0) {
            $DevolucionSupervisorFacturaQuery = $DevolucionSupervisorFacturaQuery->paginate(15);
        } else {
            $DevolucionSupervisorFacturaQuery = $DevolucionSupervisorFacturaQuery->get();
        }
        // dd($total);

        // print_r(vsprintf(str_replace('?', '%s', $sql), $bindings));
        // dd(vsprintf(str_replace('?', '%s', $sql), $bindings));

        return ["paginacion" => $DevolucionSupervisorFacturaQuery, "total" => $total];
    }
}
