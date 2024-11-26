<?php

namespace App\Http\Controllers;

use App\Exports\ClientExport;
use App\Http\Controllers\Traits\AbonoQueryTrait;
use App\Models\Factura;
use App\Models\Producto;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Factura_Detalle;
use App\Models\TazaCambioFactura;
use App\Models\Categoria;
use App\Models\FacturaHistorial;
use App\Models\RegalosFacturados;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Maatwebsite\Excel\Facades\Excel;

class PdfController extends Controller
{
    use AbonoQueryTrait;

    public function facturaPago($id, Request $request)
    {
        $response = [];
        $regaloList = [];
        $status = 400;
        $facturaEstado = 1; // Activo

        if (is_numeric($id)) {

            $factura =  Factura::with('cliente')->where([
                ['id', '=', $id],
                // ['estado', '=', $facturaEstado],
            ])->first();

            /// datos del vendedor

            $factura->user_data =  User::where([
                ['id', '=', $factura->user_id],
                // ['estado', '=', $facturaEstado],
            ])->first();

            $factura_detalle = Factura_Detalle::where([
                ['estado', '=', 1],
                ['factura_id', '=', $factura->id],
            ])->get();

            if (count($factura_detalle) > 0) {
                foreach ($factura_detalle as $productoDetalle) {
                    $producto = Producto::find($productoDetalle["producto_id"]);
                    // dd($productoDetalle["id"]);
                    $productoDetalle["marca"]       = $producto->marca;
                    $productoDetalle["modelo"]      = $producto->modelo;
                    // $productoDetalle["stock"]       = $producto->stock;
                    // $productoDetalle["precio"]      = $producto->precio;
                    $productoDetalle["linea"]       = $producto->linea;
                    $productoDetalle["descripcion"] = $producto->descripcion;
                    // $productoDetalle["estado"]      = $producto->estado;

                    foreach ($productoDetalle->regaloFacturado as $regaloF) {
                        $regaloF->regalo;
                        $regaloF->detalle_regalo =  Producto::where([
                            ['id', '=', $regaloF->regalo->id_producto_regalo],
                        ])->first();
                    }
                    array_push($regaloList, ...$productoDetalle->regaloFacturado);
                }

                // dd(json_encode($regaloList));
            }

            $taza = TazaCambioFactura::where("factura_id", $factura->id)->first();
            if (!is_null($taza)) { // si la factura tiene taza de cambio utilizo esa taza para convertir los montos

                // dd("1");
                $factura->monto = decimal($factura->monto) * decimal($taza->monto);
                $factura->saldo_restante = decimal($factura->saldo_restante) * decimal($taza->monto);

                $detalleFactura = [];
                foreach ($factura_detalle  as $detalle) {
                    $detalle->precio = decimal($detalle->precio) * decimal($taza->monto);
                    $detalle->precio_unidad = decimal($detalle->precio_unidad) * decimal($taza->monto);

                    array_push($detalleFactura, $detalle);
                }
                $factura->factura_detalle = $detalleFactura;
            } else {
                // dd(json_encode($factura_detalle));
                // dd(convertTazaCambio(1));
                $factura->monto = convertTazaCambio($factura->monto);
                $factura->saldo_restante = convertTazaCambio($factura->saldo_restante);

                $detalleFactura = [];
                foreach ($factura_detalle  as $detalle) {
                    $detalle->precio = convertTazaCambio($detalle->precio);
                    $detalle->precio_unidad = convertTazaCambio($detalle->precio_unidad);

                    array_push($detalleFactura, $detalle);
                }
                $factura->factura_detalle = $detalleFactura;
            }

            // print_r(json_encode($factura));
            // dd(json_encode($factura));
            if ($factura) {
                $response = $factura;
                $status = 200;
            } else {
                $response[] = "La factura no existe o fue eliminado.";
            }
        } else {
            $response[] = "El Valor de Id debe ser numerico.";
        }


        $data = [
            'data' => $response,
            'regalos' => $regaloList
        ];

        $archivo = PDF::loadView('pdf', $data);
        $pdf = PDF::loadView('pdf', $data)->output();

        Storage::disk('public')->put('factura.pdf', $pdf);


        return $archivo->download('factura_' . $response->id . '.pdf');
    }

    public function facturaPagonew($id)
    {
        $response = [];
        $regaloList = [];
        $status = 400;
        $facturaEstado = 1; // Activo

        if (is_numeric($id)) {

            $factura =  Factura::with('cliente', 'cliente.zona', 'cliente.departamento', 'cliente.municipio')->where([
                ['id', '=', $id],
                // ['estado', '=', $facturaEstado],
            ])->first();

            /// datos del vendedor

            $factura->user_data =  User::where([
                ['id', '=', $factura->user_id],
                // ['estado', '=', $facturaEstado],
            ])->first();

            $factura_detalle = Factura_Detalle::where([
                ['factura_detalles.estado', '=', 1],
                ['factura_detalles.factura_id', '=', $factura->id],
            ])
                ->join('productos', 'productos.id', '=', 'factura_detalles.producto_id')
                ->select(DB::raw('productos.*,factura_detalles.*,factura_detalles.id AS factura_detalle_id '))
                ->get();

            $taza = TazaCambioFactura::where("factura_id", $factura->id)->first();
            if (!is_null($taza)) { // si la factura tiene taza de cambio utilizo esa taza para convertir los montos

                // dd("1");
                $factura->monto = decimal($factura->monto) * decimal($taza->monto);
                $factura->saldo_restante = decimal($factura->saldo_restante) * decimal($taza->monto);

                $detalleFactura = [];
                foreach ($factura_detalle  as $detalle) {
                    $detalle->precio = decimal($detalle->precio) * decimal($taza->monto);
                    $detalle->precio_unidad = decimal($detalle->precio_unidad) * decimal($taza->monto);

                    array_push($detalleFactura, $detalle);
                }
                $factura->factura_detalle = $detalleFactura;
            } else {
                // dd(json_encode($factura_detalle));
                // dd(convertTazaCambio(1));
                $factura->monto = convertTazaCambio($factura->monto);
                $factura->saldo_restante = convertTazaCambio($factura->saldo_restante);

                $detalleFactura = [];
                foreach ($factura_detalle  as $detalle) {
                    $detalle->precio = convertTazaCambio($detalle->precio);
                    $detalle->precio_unidad = convertTazaCambio($detalle->precio_unidad);

                    array_push($detalleFactura, $detalle);
                }
                $factura->factura_detalle = $detalleFactura;
            }

            // print_r(json_encode($factura));
            // dd(json_encode($factura));
            if ($factura) {
                $response = $factura;
                $status = 200;
            } else {
                $response[] = "La factura no existe o fue eliminado.";
            }
        } else {
            $response[] = "El Valor de Id debe ser numerico.";
        }

        // dd(json_encode($factura->factura_detalle));

        if (count($factura->factura_detalle) > 0) {
            foreach ($factura->factura_detalle as $productoDetalle) {

                $productoDetalle->is_gift = false;
                $regalo_producto = RegalosFacturados::where([
                    // ['regalos_facturados.estado', '=', 1],
                    ['regalos_facturados.factura_detalle_id', '=', $productoDetalle->factura_detalle_id],
                ])
                    ->join('producto_para_regalos', 'producto_para_regalos.id', '=', 'regalos_facturados.regalo_id')
                    ->join('productos', 'productos.id', '=', 'producto_para_regalos.id_producto_regalo')
                    ->get();
                // print_r(json_encode($regalo_producto));
                if (count($regalo_producto) > 0) {
                    foreach ($regalo_producto as $regaloF) {
                        // detalle_regalo ->descripcion
                        // cantidad_regalada

                        $objRegalo = (object)[
                            "descripcion" => $regaloF->descripcion,
                            "cantidad_regalada" => $regaloF->cantidad_regalada,
                            "is_gift" => true,
                        ];
                        // "id": 4921,
                        // "marca": "Colortrak",
                        // "modelo": "Colortrak",
                        // "stock": 22,
                        // "precio": 2469.6,
                        // "linea": "Brochas",
                        // "descripcion": "Kit de tres brochas multicolor",
                        // "estado": 1,
                        // "created_at": "2023-09-05T23:19:06.000000Z",
                        // "updated_at": "2023-09-05T23:29:34.000000Z",
                        // "producto_id": 29,
                        // "factura_id": 3138,
                        // "cantidad": 4,
                        // "precio_unidad": 617.4,
                        // "factura_detalle_id": 4921

                        $regaloF->regalo;
                        $regaloF->detalle_regalo = Producto::where([
                            ['id', '=', $regaloF->regalo->factura_detalle_id],
                        ])->first();
                        array_push($regaloList, $objRegalo);
                    }
                }
            }
        }

        if (count($regaloList) > 0) {
            $factura->factura_detalle = array_merge($factura->factura_detalle, $regaloList, $factura->factura_detalle);
        }

        // $data['productos'] = array_chunk(json_decode(json_encode($factura->factura_detalle)), 6);
        // dd(json_encode($factura));

        $data = [
            'data' =>  $factura,
            'productos' => array_chunk(json_decode(json_encode($factura->factura_detalle)), 30),
        ];
        // dd(json_encode( $data));
        $archivo = PDF::loadView('pdf', $data);
        $pdf = PDF::loadView('pdf', $data)->output();

        Storage::disk('public')->put('factura.pdf', $pdf);


        return $archivo->download('factura_' . $response->id . '.pdf');
    }

    public function estadoCuenta(Request $request)
    {
        $all_datos = queryEstadoCuenta($request->id);
        $response['cliente'] = Cliente::with('departamento', 'zona', 'municipio')->find($request->id);

        $response['estado_cuenta'] = array_chunk($all_datos['estado_cuenta'], 30);

        $data = [
            'data' => $response

        ];


        $archivo = PDF::loadView('estado_cuenta', $data);
        $pdf = $archivo->output();

        Storage::disk('public')->put('estado_cuenta.blade.pdf', $pdf);


        return $archivo->download('estado_cuenta_' . $request->id . '.pdf');
    }

    public function SendMail($id)
    {


        $response = [];
        $status = 400;
        $facturaEstado = 1; // Activo

        if (is_numeric($id)) {

            // if($request->input("estado") != null) $facturaEstado = $request->input("estado");
            // dd($productoEstado);

            $factura =  Factura::with('factura_detalle', 'cliente', 'factura_historial')->where([
                ['id', '=', $id],
                // ['estado', '=', $facturaEstado],
            ])->first();

            if (count($factura->factura_detalle) > 0) {
                foreach ($factura->factura_detalle as $key => $productoDetalle) {
                    $producto = Producto::find($productoDetalle["producto_id"]);
                    // dd($productoDetalle["id"]);
                    $productoDetalle["marca"]       = $producto->marca;
                    $productoDetalle["modelo"]      = $producto->modelo;
                    // $productoDetalle["stock"]       = $producto->stock;
                    // $productoDetalle["precio"]      = $producto->precio;
                    $productoDetalle["linea"]       = $producto->linea;
                    $productoDetalle["descripcion"] = $producto->descripcion;
                    // $productoDetalle["estado"]      = $producto->estado;
                }
            }

            if (count($factura->factura_historial) > 0) {
                foreach ($factura->factura_historial as $key => $itemHistorial) {
                    $user = User::find($itemHistorial["user_id"]);

                    $itemHistorial["name"]      = $user->name;
                    $itemHistorial["apellido"]  = $user->apellido;
                }
            }



            if ($factura) {
                $response = $factura;
                $status = 200;
            } else {
                $response[] = "La factura no existe o fue eliminado.";
            }
        } else {
            $response[] = "El Valor de Id debe ser numerico.";
        }


        $data = [
            'data' => $response
        ];

        $archivo = PDF::loadView('pdf', $data);
        $pdf = PDF::loadView('pdf', $data)->output();

        Storage::disk('public')->put('factura.pdf', $pdf);

        $msg = $id;




        /*  Mail::to('rjov41@gmail.com')->send($correo); */

        // Mail::to('rjov41@gmail.com')->queue(new PdfMail($msg));

        return 'Mail enviado';
    }

    function generar(Request $request)
    {
        dd($request);
        // aqui colocar lo mismo que en facturaPago
    }

    function cartera(Request $request)
    {
        // dd([$request->dateFin, $request->allDates, $request->roleName]);

        $fullName = 'Todos';
        $data = carteraQuery($request);

        $data['facturas'] = array_chunk(json_decode(json_encode($data['factura'])), 11);

        if ($request->userId != 0) {
            $datosCliente =  User::find($request->userId);
            $data['fullname'] = $datosCliente->name . ' ' . $datosCliente->apellido;
        } else {
            $data['fullname'] = $fullName;
        }


        $data = [
            'data' => $data['facturas'],
            'fullname' => $data['fullname'],
            'total' => $data['total']
        ];



        $archivo = PDF::loadView('cartera', $data);
        $pdf = PDF::loadView('cartera', $data)->output();

        Storage::disk('public')->put('cartera.pdf', $pdf);


        return $archivo->download('cartera.pdf');
    }

    public function inventario()
    {

        $data["productos"] = Producto::where([
            ['estado', '=', 1],
        ])->get();

        $data['productos'] = array_chunk(json_decode(json_encode($data['productos'])), 39);


        $data = [
            'data' => $data['productos'],
            'total' => count($data['productos'])
        ];
        // dd(json_encode($data));


        $archivo = PDF::loadView('inventario_producto', $data);
        $pdf = PDF::loadView('inventario_producto', $data)->output();

        Storage::disk('public')->put('inventario_producto.pdf', $pdf);


        return $archivo->download('inventario_producto.pdf');
    }

    public function mora60a90(Request $request)
    {
        $dataQuery = mora60_90Query($request);

        $data['facturas'] = array_chunk(json_decode(json_encode($dataQuery['factura'])), 30);


        $data = [
            'data' => $data['facturas'],
            'total' => count($dataQuery['factura'])
        ];
        // dd(json_encode($data));


        $archivo = PDF::loadView('facturasmora60a90', $data);
        $pdf = PDF::loadView('facturasmora60a90', $data)->output();

        Storage::disk('public')->put('facturasmora60a90.pdf', $pdf);


        return $archivo->download('facturasmora60a90.pdf');
    }

    public function clientesInactivosPDF(Request $request)
    {
        $dataQuery = clientesInactivosQuery($request);

        $data['clientes'] = array_chunk(json_decode(json_encode($dataQuery)), 13);


        $data = [
            'data' => $data['clientes'],
            'total' => count($dataQuery)
        ];
        // dd(json_encode($data));


        $archivo = PDF::loadView('clientes_inactivos', $data);
        $pdf = PDF::loadView('clientes_inactivos', $data)->output();

        Storage::disk('public')->put('clientes_inactivos.pdf', $pdf);


        return $archivo->download('clientes_inactivos.pdf');
    }

    public function productosVendidos(Request $request)
    {

        $dataQuery = productosVendidos($request);

        $data['productos'] = array_chunk(json_decode(json_encode($dataQuery['productos'])), 34);


        $data = [
            'data' => $data['productos'],
            'total' => $dataQuery['totalProductos']
        ];
        // dd(json_encode($data));


        $archivo = PDF::loadView('productos_vendidos', $data);
        $pdf = PDF::loadView('productos_vendidos', $data)->output();

        Storage::disk('public')->put('productos_vendidos.pdf', $pdf);


        return $archivo->download('inventario_producto.pdf');
    }

    public function productosVendidosUsuario(Request $request)
    {
        $user = User::select("*")
            // ->where('estado', 1)
            ->where('id', $request->userId)
            ->first();

        $dataQuery = productosVendidosPorUsuario($user, $request);
        $data['productos'] = array_chunk(json_decode(json_encode($dataQuery['productos'])), 34);
        $data['user'] = $dataQuery['user'];

        // $model_has_roles = DB::table('model_has_roles')->where('model_id', $user->id)->first();
        // $role = Role::find($request['role_id']);
        // if ($model_has_roles->role_id == 4) {
        // $dataQueryIncentivos = incentivoSupervisorQuery($request);
        // } else {
        $dataQueryIncentivos = incentivosQuery($request);
        // }

        // dd(json_encode($dataQueryIncentivos));
        // dd($request->all());
        // dd(json_encode($dataQueryIncentivos["porcentaje20"]));
        // dd(json_encode($dataQuery['productos']));
        // dd(json_encode( $dataQuery['user']));
        $data['incentivos'] = $dataQueryIncentivos["porcentaje20"];


        // $data = [
        //     'data' => $data['productos'],
        //     'total' => $dataQuery['totalProductos']
        // ];
        // // dd(json_encode($data));


        $archivo = PDF::loadView('ventas_productos', $data);
        $pdf = PDF::loadView('ventas_productos', $data)->output();

        Storage::disk('public')->put('ventas_productos.pdf', $pdf);


        return $archivo->download('ventas_productos.pdf');
    }

    public function estadoFinanzaPDF(Request $request)
    {

        $ventas_listado = [];
        $ventas_totalMetas = 0;
        $ventas_total = 0;
        $costo_listado = [];
        $costo_total_porcentaje = 0;
        $costo_total = 0;
        $utilidad_bruta_total = 0;
        $gasto_general_total = 0;
        $gasto_total = 0;
        $gasto_total_porcentaje = 0;
        $incentivos_vendedor_total = 0;
        $incentivos_supervisor_total = 0;
        $incentivos_total = 0;
        $utilidad_neta_total = 0;
        $utilidad_neta_total_porcentaje = 0;

        $users = User::where([
            ["estado", "=", 1],
        ])->get();

        $dataRequest = (object) [
            "allDates" => false,
            "dateFin" => $request->dateFin,
            "dateIni" => $request->dateIni,
            "status_pagado" => 0,
            "userId" => 0,
            "allNumber" => true,
            'allUsers' => false,
            'estado' => 1,
            'disablePaginate' => 1,
        ];

        $resultCostosProductosVendidos = ListadoCostosProductosVendidos($dataRequest);
        $costo_listado[] = $resultCostosProductosVendidos["totalProductos"];

        // $response["costo_listado"][] = $resultCostosProductosVendidos["productos"];
        $costo_total = $resultCostosProductosVendidos["costoTotal"];

        // dd(json_encode($resultCostosProductosVendidos["totalProductos"]));
        // foreach ($resultCostosProductosVendidos["productos"] as $costo) {
        //     if ($costo->inversion) {
        //         $costo_total += ($costo->inversion->costo * $costo->cantidad);
        //     } else {
        //         if ($costo->costo_opcional) {
        //             $costo_total += ($costo->costo_opcional->costo * $costo->cantidad);
        //         }
        //     }
        // }

        $responseGastos = ListadoGastos($dataRequest);
        $response["gasto_general_total"] = $responseGastos["total_monto"];

        foreach ($users as $user) {
            $dataRequest->userId = $user->id;

            if (!in_array($dataRequest->userId, [20, 21, 23, 24, 25, 32])) {
                $responseIncentivo = incentivosQuery($dataRequest);
                $incentivos_vendedor_total += $responseIncentivo["total"];
            }

            $listadoVentasUser = ventasMes($dataRequest, $user);
            $ventas_totalMetas += $listadoVentasUser['meta'];
            $ventas_total += $listadoVentasUser['totalVentas'];
            $ventas_listado[] = $listadoVentasUser;
        }


        $incentivosSupervisor = incentivoSupervisorQuery($dataRequest);
        $incentivos_supervisor_total = decimal($incentivosSupervisor["totalFacturaVendedores2Porciento"] + $incentivosSupervisor["totalRecuperacionVendedores"]);
        $incentivos_vendedor_total  = decimal($incentivos_vendedor_total  * 0.20);
        $incentivos_total  = decimal($incentivos_vendedor_total  + $incentivos_supervisor_total);


        $gasto_total  = decimal($incentivos_total + $response["gasto_general_total"]);
        $gasto_total_porcentaje  =  $ventas_total ? decimal($gasto_total / $ventas_total * 100) : 0;

        $costo_total_porcentaje  = $ventas_total ? decimal($costo_total / $ventas_total * 100) : 0;

        $utilidad_bruta_total = decimal($ventas_total - $costo_total);
        $utilidad_neta_total = decimal($utilidad_bruta_total - $gasto_total);
        $utilidad_neta_total_porcentaje  = $ventas_total ? decimal($utilidad_neta_total / $ventas_total * 100) : 0;
        // dd($utilidad_bruta_total);
        $archivo = PDF::loadView('estado_finanza', compact(
            'ventas_listado',
            'ventas_totalMetas',
            'ventas_total',
            'costo_listado',
            'costo_total_porcentaje',
            'costo_total',
            'utilidad_bruta_total',
            'gasto_general_total',
            'gasto_total',
            'gasto_total_porcentaje',
            'incentivos_vendedor_total',
            'incentivos_supervisor_total',
            'incentivos_total',
            'utilidad_neta_total',
            'utilidad_neta_total_porcentaje',
            'dataRequest'
        ));
        $pdf = PDF::loadView('estado_finanza', compact(
            'ventas_listado',
            'ventas_totalMetas',
            'ventas_total',
            'costo_listado',
            'costo_total_porcentaje',
            'costo_total',
            'utilidad_bruta_total',
            'gasto_general_total',
            'gasto_total',
            'gasto_total_porcentaje',
            'incentivos_vendedor_total',
            'incentivos_supervisor_total',
            'incentivos_total',
            'utilidad_neta_total',
            'utilidad_neta_total_porcentaje',
            'dataRequest'
        ))->output();

        Storage::disk('public')->put('estado_finanza.pdf', $pdf);


        return $archivo->download('estado_finanza.pdf');
    }

    public function productosVendidosSupervisor(Request $request)
    {
        // $users = User::where([
        //     ["estado", "=", 1]
        // ])->get();
        $user = User::select("*")
            // ->where('estado', 1)
            ->where('id', $request->userId)
            ->first();

        $responsequery = productosVendidosPorUsuario($user, $request, true);
        // dd(json_encode($responsequery));

        $data['productos'] = array_chunk(json_decode(json_encode($responsequery['productos'])), 34);
        $data['user'] = $user;

        $dataQueryIncentivos = incentivoSupervisorQuery($request);


        $data['incentivos'] = $dataQueryIncentivos["totalFacturaVendedores2Porciento"] + $dataQueryIncentivos["totalRecuperacionVendedores"];

        $archivo = PDF::loadView('ventas_productos_supervisor', $data);
        $pdf = PDF::loadView('ventas_productos_supervisor', $data)->output();

        Storage::disk('public')->put('ventas_productos_supervisor.pdf', $pdf);


        return $archivo->download('ventas_productos_supervisor.pdf');
    }

    public function registro_cliente(Request $request)
    {

        $response = $this->registroClienteQuery($request);

        // print_r(json_encode($response));
        // $response['data'] =
        $data = [
            'data' =>  array_chunk(json_decode(json_encode($response)), 18),
            'cantidad' => count($response),
        ];

        // dd(json_encode($data));

        $archivo = PDF::loadView('registro_clientes', $data);
        $pdf = $archivo->output();

        Storage::disk('public')->put('registro_clientes.blade.pdf', $pdf);

        return $archivo->download('registro_clientes_' . $request->id . '.pdf');
    }

    public function registro_cliente_csv(Request $request)
    {
        $dataCSV = [];
        $fileName = "registro_clientes_" . Carbon::now('utc')->toDateTimeString() . ".csv";
        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );
        $columns = array(
            'Codigo_Cliente',
            'Nombre_Completo',
            'Zona',
            'Departamento',
            'Municipio',
            'Dirección',
            'Dirección_Oficina',
            'Celular',
            'Saldo_Actual',
            'Ultima_Fecha_de_Pago',
            'Dias_de_Cobro',
        );

        $response = $this->registroClienteQuery($request, false);
        foreach ($response as $cliente) {

            // $diasCobro = explode(",", $cliente->dias_cobro);

            // $strCobrosHtml = "";
            // foreach ($diasCobro as $dia) {
            //     $strCobrosHtml .= "- " . ucwords($dia) . "<br>";
            // }
            // $cliente->dias_cobro = $strCobrosHtml;


            $dataCSV[] = array(
                'Codigo_Cliente' => $cliente->id,
                'Nombre_Completo' => $cliente->nombreCompleto,
                'Zona' => isset($cliente->zona) && $cliente->zona ? $cliente->zona->nombre : "-",
                'Departamento' => isset($cliente->departamento) && $cliente->departamento ? $cliente->departamento->nombre : "-",
                'Municipio' => isset($cliente->municipio) && $cliente->municipio ? $cliente->municipio->nombre : "-",
                'Dirección' => $cliente->direccion_casa,
                'Dirección_Oficina' => $cliente->direccion_casa,
                'Celular' => $cliente->celular,
                'Saldo_Actual' => $cliente->saldo,
                'Ultima_Fecha_de_Pago' => ($cliente->ultimoAbono) ? Carbon::parse($cliente->ultimoAbono->created_at)->format('j-m-Y') : "No posee abonos",
                'Dias_de_Cobro' => $cliente->dias_cobro,
            );
        }

        $callback = function () use ($dataCSV, $columns) {
            $file = fopen('php://output', 'w');
            //si no quieren que el csv muestre el titulo de columnas omitan la siguiente línea.
            fputcsv($file, $columns);
            foreach ($dataCSV as $item) {
                fputcsv($file, $item);
            }
            fclose($file);
        };

        //Esto hace que Laravel exponga el archivo como descarga
        return response()->stream($callback, 200, $headers);
    }

    public function registro_cliente_excell(Request $request)
    {
        $fileName = "registro_clientes_" . Carbon::now('utc')->toDateTimeString() . ".xlsx";

        $columns = array(
            'Codigo_Cliente',
            'Nombre_Completo',
            'Zona',
            'Departamento',
            'Municipio',
            'Dirección',
            'Celular',
            'Saldo_Actual',
            'Ultima_Fecha_de_Pago',
            'UltimaFactura',
            'Dias_de_Cobro',
        );

        $response = $this->registroClienteQuery($request, false);
        $dataExcell = [
            $columns
        ];
        foreach ($response as $cliente) {
            $dataExcell[] = array(
                $cliente->id,
                $cliente->nombreCompleto,
                isset($cliente->zona) && $cliente->zona ? ucwords(strtolower($cliente->zona->nombre)) : "-",
                isset($cliente->departamento) && $cliente->departamento ? ucwords(strtolower($cliente->departamento->nombre)) : "-",
                isset($cliente->municipio) && $cliente->municipio ? ucwords(strtolower($cliente->municipio->nombre)) : "-",
                $cliente->direccion_negocio,
                $cliente->celular,
                $cliente->saldo,
                ($cliente->ultimoAbono) ? Carbon::parse($cliente->ultimoAbono->created_at)->format('j-m-Y') : "No posee abonos",
                $cliente->ultimaFactura ? Carbon::parse($cliente->ultimaFactura->fecha_vencimiento)->format('j-m-Y') : "Sin Facturas",
                $cliente->dias_cobro,
            );
        }
        $export = new ClientExport([
            $dataExcell
        ]);
        // dd($export);
        return Excel::download($export, $fileName);
    }

    public function abonos_excell(Request $request)
    {
        $fileName = "abonos_" . Carbon::now('utc')->toDateTimeString() . ".xlsx";

        $columns = array(
            "#",
            "Recibo",
            "Monto",
            "Método pago",
            "Detalle pago",
            "Autorización",
            "Fecha",
            "Cliente",
            "Usuario",
        );

        $abonos = $this->AbonoListQuery($request);
        $dataExcell = [
            $columns
        ];


        foreach ($abonos as $abono) {
            $dataExcell[] = array(
                $abono->id,
                $abono->recibo_historial->numero,
                $abono->precio,
                $abono->metodo_pago ? $abono->metodo_pago->tipoPago : "--",
                $abono->metodo_pago ? $abono->metodo_pago->detalle : "--",
                $abono->metodo_pago->autorizacion ? $abono->metodo_pago->autorizacion : "--",
                Carbon::parse($abono->created_at)->format('j-m-Y'),
                $abono->cliente->nombreCompleto,
                $abono->usuario->name,

            );
        }
        $export = new ClientExport([
            $dataExcell
        ]);
        // dd($export);
        return Excel::download($export, $fileName);
    }

    private function registroClienteQuery($request, $formatDiasCobro = true)
    {
        $parametros = [["estado", 1]];

        // DB::enableQueryLog();

        $clientes =  Cliente::with('departamento', 'zona', 'municipio')->where($parametros);

        // ** Filtrado por userID
        $clientes->when($request->userId && $request->userId != 0, function ($q) use ($request) {
            $query = $q;
            // vendedor
            // supervisor
            // administrador

            $user = User::select("*")
                // ->where('estado', 1)
                ->where('id', $request->userId)
                ->first();

            if (!$user) {
                return $query;
            }

            return $query->where('user_id', $user->id);
        });

        $clientes->when($request->diasCobros, function ($q) use ($request) {
            $query = $q;
            $dias = explode(",", $request->diasCobros);
            $condicionDiasCobro = [];
            foreach ($dias as $dia) {
                array_push($condicionDiasCobro, ['dias_cobro', 'LIKE', '%' . $dia . '%', "or"]);
            }
            return $query->where($condicionDiasCobro);
        });

        $clientes->when($request->categoriaId && $request->categoriaId != 0, function ($q) use ($request) {
            $query = $q;

            $categoria = Categoria::select("*")
                ->where('estado', 1)
                ->where('id', $request->categoriaId)
                ->first();

            if (!$categoria) {
                return $query;
            }

            return $query->where('categoria_id', $categoria->id);
        });

        // filtrados para campos numericos
        $clientes->when($request->filter && is_numeric($request->filter), function ($q) use ($request) {
            $query = $q;
            // id de recibos 
            $filterSinNumeral = str_replace("#", "", $request->filter);

            $query = $query->where('id', 'LIKE', '%' . $filterSinNumeral . '%');

            return $query;
        }); // Fin Filtrado


        // ** Filtrado para string
        $clientes->when($request->filter && !is_numeric($request->filter), function ($q) use ($request) {
            $query = $q;

            // nombre cliente y empresa
            $query = $query->Where('nombreCompleto', 'LIKE', '%' . $request->filter . '%')
                ->orWhere('nombreEmpresa', 'LIKE', '%' . $request->filter . '%')
                ->orWhere('direccion_casa', 'LIKE', '%' . $request->filter . '%');

            return $query;
        }); // Fin Filtrado por cliente

        $clientes = $clientes->get();

        // dd(DB::getQueryLog());


        if (count($clientes) > 0) {
            foreach ($clientes as $cliente) {
                // dd($cliente->frecuencias);
                // validarStatusPagadoGlobal($cliente->id);
                $cliente->frecuencia = $cliente->frecuencia;
                $cliente->categoria = $cliente->categoria;
                $cliente->facturas = $cliente->facturas;
                $cliente->usuario = $cliente->usuario;
                // $cliente->saldo = calcularDeudaFacturasGlobal($cliente->id);

                $saldoCliente = calcularDeudaFacturasGlobal($cliente->id);

                if ($saldoCliente > 0) {
                    $cliente->saldo = number_format(-(float) $saldoCliente, 2);
                }

                if ($saldoCliente == 0) {
                    $cliente->saldo = $saldoCliente;
                }

                if ($saldoCliente < 0) {
                    // $cliente->saldo = number_format((float) str_replace("-", "", $saldoCliente), 2);
                    $saldo_sin_guion = str_replace("-", "", $saldoCliente);
                    $cliente->saldo = decimal(filter_var($saldo_sin_guion, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
                }

                if ($formatDiasCobro) {
                    $diasCobro = explode(",", $cliente->dias_cobro);

                    $strCobrosHtml = "";
                    foreach ($diasCobro as $dia) {
                        $strCobrosHtml .= "- " . ucwords($dia) . "<br>";
                    }
                    $cliente->dias_cobro = $strCobrosHtml;
                }

                $cliente->ultimoAbono = FacturaHistorial::where(
                    [
                        ["cliente_id", $cliente->id],
                    ]
                )->orderBy('created_at', 'desc')->first();

                $ultimaFactura = Factura::where(
                    [
                        ["cliente_id", $cliente->id],
                        ["status", 1],
                        // ["status_pagado", 0],
                    ]
                )->orderBy('created_at', 'desc')->first();
                $cliente->ultimaFactura = $ultimaFactura;
            }
            // dd(json_encode($clientes));

            // $response[] = $clientes;
        }

        return $clientes;
    }
}
