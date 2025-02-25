<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\AbonoQueryTrait;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Departamento;
use App\Models\Factura;
use App\Models\Factura_Detalle;
use App\Models\FacturaHistorial;
use App\Models\MetaHistorial;
use App\Models\MetodoPago;
use App\Models\Municipio;
use App\Models\Producto;
use App\Models\Recibo;
use App\Models\ReciboHistorial;
use App\Models\User;
use App\Models\Zona;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListadosPaginasController extends Controller
{
    use AbonoQueryTrait;

    public function facturasList(Request $request)
    {
        $response = [];
        $status = 200;
        // $facturaEstado = 1; // Activo
        $parametros = [];

        // if ($request['roleName'] == "vendedor") { // si es vendedor solo devuelvo sus facturas
        //     // vendedor
        //     // supervisor
        //     // administrador

        //     $parametros[] = ["user_id", $request['userId']];
        // }

        if (!is_null($request['estado'])) $parametros[] = ["status", $request['estado']];
        if (!is_null($request['tipo_venta'])) $parametros[] = ["tipo_venta", $request['tipo_venta']];
        if (!is_null($request['status_pagado'])) $parametros[] = ["status_pagado", $request['status_pagado']];
        if (!is_null($request['status_entrega'])) $parametros[] = ["entregado", $request['status_entrega']];
        if (!is_null($request['despachado'])) $parametros[] = ["despachado", $request['despachado']];
        if (!is_null($request['userId']) && $request['userId'] != 0) $parametros[] = ["user_id", $request['userId']];
        if (!is_null($request['clienteId']) && $request['clienteId'] != 0) $parametros[] = ["cliente_id", $request['clienteId']];
        if (!is_null($request['created_at'])) {
            $created_at = Carbon::parse($request['created_at']);
            $parametros[] = ["created_at", '>=', $created_at . " 00:00:00"];
        }

        // DB::enableQueryLog();
        $facturas =  Factura::where($parametros);

        // ** Filtrado por cliente
        $facturas->when($request['filter'] && !is_numeric($request['filter']), function ($q) use ($request) {
            $clientesId = [];

            $clientes = Cliente::select('id')
                ->orWhere('nombreCompleto', 'LIKE', '%' . $request['filter'] . '%')
                ->orWhere('nombreEmpresa', 'LIKE', '%' . $request['filter'] . '%')
                ->get();

            if (count($clientes) > 0) {
                foreach ($clientes as $cliente) {
                    $clientesId[] = $cliente->id;
                }
            }

            return $q->wherein('cliente_id', $clientesId);
        }); // Fin Filtrado por cliente

        // ** Filtrado por Factura
        $facturas->when($request['filter'] && is_numeric($request['filter']), function ($q) use ($request) {
            return $q->where('id', 'LIKE', '%' . $request['filter'] . '%');
        }); // Fin Filtrado por Factura

        // dd(json_encode($facturas));

        if ($request->disablePaginate == 0) {
            $facturas = $facturas->orderBy('created_at', 'desc')->paginate(15);
        } else {
            $facturas = $facturas->get();
        }
        // dd(DB::getQueryLog());

        if (count($facturas) > 0) {
            foreach ($facturas as $factura) {
                $factura->user;
                $factura->cliente->factura_historial;
                $factura->factura_detalle = $factura->factura_detalle()->where([
                    ['estado', '=', 1],
                ])->get();
            }
        }

        $response = $facturas;


        return response()->json($response, $status);
    }

    public function metasHistoricoList(Request $request)
    {
        $response = [];
        $status = 200;
        // $facturaEstado = 1; // Activo
        $parametros = [["estado", 1]];


        // if ($request['estado']) $parametros[] = ["estado", $request['estado']];
        if ($request['userId'] && $request['userId'] != 0) $parametros[] = ["user_id", $request['userId']];

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

        // DB::enableQueryLog();
        $metas =  MetaHistorial::where($parametros);

        // ** Filtrado por rango de fechas Meta 
        $metas->when($request->allDates && $request->allDates == "false", function ($q) use ($dateIni, $dateFin) {
            return $q->whereBetween('fecha_asignacion', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
        });

        // ** Filtrado por Meta
        $metas->when($request['filter'] && is_numeric($request['filter']), function ($q) use ($request) {
            return $q->where('id', 'LIKE', '%' . $request['filter'] . '%');
        });

        // dd(json_encode($facturas));
        $metas = $metas->orderBy('fecha_asignacion', 'desc')->paginate(15);
        // dd(DB::getQueryLog());

        if (count($metas) > 0) {
            foreach ($metas as $meta) {
                $meta->user;
            }
        }

        $response = $metas;


        return response()->json($response, $status);
    }

    public function recibosCreditosList(Request $request)
    {
        $response = [];
        $status = 200;
        // $facturaEstado = 1; // Activo
        $parametros = [["estado", 1]];

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

        // DB::enableQueryLog();

        $recibos =  ReciboHistorial::where($parametros);

        // ** Filtrado por rango de fechas 
        $recibos->when($request->allDates && $request->allDates == "false", function ($q) use ($dateIni, $dateFin) {
            return $q->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
        });

        // ** Filtrado por userID
        $recibos->when($request->userId && $request->userId != 0, function ($q) use ($request) {
            $query = $q;
            // vendedor
            // supervisor
            // administrador

            $user = User::select("*")
                ->where('estado', 1)
                ->where('id', $request->userId)
                ->first();

            if (!$user) {
                return $query;
            }

            $recibo = Recibo::select("*")
                ->where('estado', 1)
                ->where('user_id', $user->id)
                ->first();

            if (!$recibo) {
                return $query;
            } else {
                return $query->where('recibo_id', $recibo->id);
            }
        });

        // ** Filtrado por rango de fechas 
        $recibos->when($request->allDates && $request->allDates == "false", function ($q) use ($dateIni, $dateFin) {
            return $q->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
        });

        // ** Filtrado por numero de recibo
        $recibos->when($request->numeroRecibo, function ($q) use ($request) {
            return $q->where('numero', 'LIKE', '%' . $request->numeroRecibo . '%');
        });

        // filtrados para campos numericos
        $recibos->when($request->filter && is_numeric($request->filter), function ($q) use ($request) {
            $query = $q;
            // id de recibos 
            $filterSinNumeral = str_replace("#", "", $request->filter);
            $query = $query->where('id', 'LIKE', '%' . $filterSinNumeral . '%');

            // precio y id de abonos 
            $abonosId = [];
            $abonos = FacturaHistorial::select("*")
                ->where('estado', 1)
                ->where('id', 'LIKE', '%' . $filterSinNumeral . '%')
                ->where('precio', 'LIKE', '%' . $filterSinNumeral . '%')
                ->get();

            if (count($abonos) > 0) {
                foreach ($abonos as $abono) {
                    $abonosId[] = $abono->id;
                }

                $query = $query->wherein('factura_historial_id', $abonosId, "or");
            }

            return $query;
        }); // Fin Filtrado


        // ** Filtrado para string
        $recibos->when($request->filter && !is_numeric($request->filter), function ($q) use ($request) {
            $query = $q;

            // nombre cliente
            $clientesId = [];
            $clientes = Cliente::select("*")
                ->where('estado', 1)
                ->where('nombreCompleto', 'LIKE', '%' . $request->filter . '%')
                ->get();

            if (count($clientes) > 0) {
                foreach ($clientes as $cliente) {
                    $clientesId[] = $cliente->id;
                }

                $abonosId = [];
                $abonos = FacturaHistorial::select("*")
                    ->where('estado', 1)
                    ->wherein('cliente_id', $clientesId)
                    ->get();

                if (count($abonos) > 0) {
                    foreach ($abonos as $abono) {
                        $abonosId[] = $abono->id;
                    }

                    $query = $query->wherein('factura_historial_id', $abonosId);
                }
            }

            return $query;
        }); // Fin Filtrado por cliente


        // dd($condicionesNumericas);
        // ['factura_historial','cliente','nombreCompleto'],
        // ['recibo','user','name'],
        // ['recibo','user','apellido'],

        // dd(json_encode($facturas));

        $recibos = $recibos->orderBy('created_at', 'desc')->paginate(15);
        // dd(DB::getQueryLog());
        // dd(DB::getQueryLog());

        if (count($recibos) > 0) {
            foreach ($recibos as $recibo) {
                $recibo->recibo->user;
                $recibo->factura_historial->cliente;
            }
        }

        $response = $recibos;


        return response()->json($response, $status);
    }

    public function abonosCreditosList(Request $request)
    {
        $response = [];
        $status = 200;


        $response = $this->AbonoListQuery($request);


        return response()->json($response, $status);
    }

    public function FacturaDetailClientList(Request $request)
    {
        $response = [];
        $status = 200;
        // $facturaEstado = 1; // Activo
        $parametros = [["estado", 1]];

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

        // DB::enableQueryLog();

        $facturaDetalle =  Factura_Detalle::where($parametros);

        // ** Filtrado por rango de fechas 
        $facturaDetalle->when($request->allDates && $request->allDates == "false", function ($q) use ($dateIni, $dateFin) {
            return $q->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
        });

        //** facturas del cliente
        $facturaDetalle->when($request->clienteId, function ($q) use ($request) {
            $query = $q;

            // nombre cliente
            $facturaId = [];
            $facturas = Factura::select("*")
                ->where('status', 1)
                ->where('cliente_id', $request->clienteId)
                ->get();

            if (count($facturas) > 0) {
                foreach ($facturas as $factura) {
                    $facturaId[] = $factura->id;
                }
            }

            return $query->wherein('factura_id', $facturaId);
        }); // Fin Filtrado por cliente

        $facturaDetalle = $facturaDetalle->orderBy('created_at', 'desc')->paginate(15);

        if (count($facturaDetalle) > 0) {
            foreach ($facturaDetalle as $productoVendido) {
                $productoVendido->factura->cliente;
                $productoVendido->producto;
            }
        }

        $response = $facturaDetalle;


        return response()->json($response, $status);
    }

    public function clientesList(Request $request)
    {
        $response = [];
        $status = 200;
        // $facturaEstado = 1; // Activo
        $parametros = [["estado", 1]];

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

        // DB::enableQueryLog();

        $clientes =  Cliente::query();

        $clientes->with('zona', 'municipio', 'departamento');
        // ** Filtrado por Estado 
        $clientes->when(isset($request->estado) && $request->estado != 2, function ($q) use ($request) {
            return $q->where('estado', $request->estado);
        });

        // ** Filtrado por rango de fechas 
        $clientes->when($request->allDates && $request->allDates == "false", function ($q) use ($dateIni, $dateFin) {
            return $q->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
        });

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

            // print_r( $request->userId);
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


        $clientes->addSelect([
            'saldo' => function ($query) {
                $query->selectRaw('
                     ROUND(COALESCE(
                        (SELECT SUM(facturas.monto)
                         FROM facturas
                         WHERE facturas.cliente_id = clientes.id
                           AND facturas.status = 1
                           AND facturas.tipo_venta = 1), 0),2
                    )
                    -
                     ROUND(
                    COALESCE(
                        (SELECT SUM(historial.precio)
                         FROM factura_historials as historial
                         WHERE historial.cliente_id = clientes.id
                           AND historial.estado = 1), 0),2
                           )
                ');
            }
        ]);
        $clientes->when(isset($request->saldoFil) && $request->saldoFil != 2, function ($q) use ($request) {
            if ($request->saldoFil == 1) {
                return $q->whereRaw('
                    ROUND(
                        COALESCE(
                            (SELECT SUM(facturas.monto)
                            FROM facturas
                            WHERE facturas.cliente_id = clientes.id
                            AND facturas.status = 1
                            AND facturas.tipo_venta = 1), 0)
                        -
                        COALESCE(
                            (SELECT SUM(historial.precio)
                            FROM factura_historials as historial
                            WHERE historial.cliente_id = clientes.id
                            AND historial.estado = 1), 0), 2
                    ) > 0');
            } else {
                return $q->whereRaw('
                    ROUND(
                        COALESCE(
                            (SELECT SUM(facturas.monto)
                            FROM facturas
                            WHERE facturas.cliente_id = clientes.id
                            AND facturas.status = 1
                            AND facturas.tipo_venta = 1), 0)
                        -
                        COALESCE(
                            (SELECT SUM(historial.precio)
                            FROM factura_historials as historial
                            WHERE historial.cliente_id = clientes.id
                            AND historial.estado = 1), 0), 2
                    ) <= 0');
            }
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

        $clientes->when(isset($request->zona_id) && $request->zona_id != 0, function ($q) use ($request) {
            $query = $q;
            return $query->where('zona_id', $request->zona_id);
        });

        $clientes->when(isset($request->departamento_id) && $request->departamento_id != 0, function ($q) use ($request) {
            $query = $q;
            return $query->where('departamento_id', $request->departamento_id);
        });

        $clientes->when(isset($request->municipio_id) && $request->municipio_id != 0, function ($q) use ($request) {
            $query = $q;
            return $query->where('municipio_id', $request->municipio_id);
        });

        // ** Filtrado por rango de fechas 
        // $clientes->when($request->allDates && $request->allDates == "false", function ($q) use ($dateIni, $dateFin) {
        //     return $q->whereBetween('created_at', [$dateIni->toDateString() . " 00:00:00",  $dateFin->toDateString() . " 23:59:59"]);
        // });

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

            $ZonasId = [];
            $Zonas = Zona::select('id')
                ->where('nombre', 'LIKE', '%' . $request['filter'] . '%')
                ->get();

            if (count($Zonas) > 0) {
                foreach ($Zonas as $Zona) {
                    $ZonasId[] = $Zona->id;
                }
            }
            // if (count($ZonasId) > 0) {
            //     // $query = $q->orWhereIn('zona_id', $ZonasId);
            // }

            $DepartamentosId = [];
            $Departamentos = Departamento::select('id')
                ->where('nombre', 'LIKE', '%' . $request['filter'] . '%')
                ->get();

            if (count($Departamentos) > 0) {
                foreach ($Departamentos as $Departamento) {
                    $DepartamentosId[] = $Departamento->id;
                }
            }
            // if (count($DepartamentosId) > 0) {
            //     // $query = $q->WhereIn('departamento_id', $DepartamentosId);
            // }

            $municipiosId = [];
            $Municipios = Municipio::select('id')
                ->where('nombre', 'LIKE', '%' . $request['filter'] . '%')
                ->get();

            if (count($Municipios) > 0) {
                foreach ($Municipios as $Municipio) {
                    $municipiosId[] = $Municipio->id;
                }
            }
            // if (count($municipiosId) > 0) {
            //     // $query = $q->WhereIn('municipio_id', $municipiosId);
            // }



            $query = $query->where(
                [
                    ['nombreCompleto', 'LIKE', '%' . $request->filter . '%', "or"],
                    ['nombreEmpresa', 'LIKE', '%' . $request->filter . '%', "or"],
                    ['direccion_casa', 'LIKE', '%' . $request->filter . '%', "or"],
                ]
            );
            //     ->where('nombreEmpresa', 'LIKE', '%' . $request->filter . '%',"or")
            //     ->where('direccion_casa', 'LIKE', '%' . $request->filter . '%',"or");

            $query = $query->orWhere(function ($q) use ($DepartamentosId, $municipiosId, $ZonasId) {
                if (!empty($DepartamentosId)) {
                    $q->whereIn('departamento_id', $DepartamentosId);
                }

                if (!empty($municipiosId)) {
                    $q->whereIn('municipio_id', $municipiosId);
                }

                if (!empty($ZonasId)) {
                    $q->orWhereIn('zona_id', $ZonasId);
                }
            });

            return $query;
        }); // Fin Filtrado por cliente

        if ($request->disablePaginate == 0) {
            $clientes = $clientes->orderBy('created_at', 'desc')->paginate(15);
        } else {
            $clientes = $clientes->get();
        }

        // dd(DB::getQueryLog());

        if (count($clientes) > 0) {
            foreach ($clientes as $cliente) {
                // dd($cliente->frecuencias);
                // validarStatusPagadoGlobal($cliente->id);
                $clientes->frecuencia = $cliente->frecuencia;
                $clientes->categoria = $cliente->categoria;
                $clientes->facturas = $cliente->facturas;
                $clientes->usuario = $cliente->usuario;
                // dd($cliente)

                // $cliente->saldo = decimal(filter_var($cliente->saldo, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
                // if ($cliente->saldo < 0) {
                    // $cliente->saldo = number_format((float) str_replace("-", "", $saldoCliente), 2);
                    // $saldo_sin_guion = str_replace("-", "", $cliente->saldo);
                    // $cliente->saldo = decimal(filter_var($saldo_sin_guion, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
                // }

                // if($request->saldoFil && $request->saldoFil != 2){
                // $saldoCliente = calcularDeudaFacturasGlobal($cliente->id);

                //     if ($saldoCliente > 0) {
                //         $cliente->saldo2 = number_format(-(float) $saldoCliente, 2);
                //     }

                //     if ($saldoCliente == 0) {
                //         $cliente->saldo2 = $saldoCliente;
                //     }

                //     if ($saldoCliente < 0) {
                //         // $cliente->saldo = number_format((float) str_replace("-", "", $saldoCliente), 2);
                // $saldo_sin_guion = str_replace("-", "", $saldoCliente);
                // $cliente->saldo2 = decimal(filter_var($saldo_sin_guion, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
                //     }
                // }

                // dd($cliente->saldo);
            }

            $response[] = $clientes;
        }

        $response = $clientes;


        return response()->json($response, $status);
    }

    public function ProductosList(Request $request)
    {
        // dd($request->all());
        $response = [];
        $status = 200;

        // DB::enableQueryLog();

        $Productos =  Producto::query();

        $Productos->when($request->estado, function ($q) use ($request) {
            return $q->where('estado', $request->estado);
        });

        // ** Filtrado para string
        $Productos->when($request->filter && !is_numeric($request->filter), function ($q) use ($request) {
            $query = $q;
            $query = $query->where(
                [
                    ['descripcion', 'LIKE', '%' . $request->filter . '%', "or"],
                ]
            );


            return $query;
        }); // Fin Filtrado por cliente

        // ** Filtrado para string
        $Productos->when($request->filter && is_numeric($request->filter), function ($q) use ($request) {
            $query = $q;

            $query = $query->where(
                [
                    ['id', 'LIKE', '%' . $request->filter, "or"],
                ]
            );
            return $query;
        }); // Fin Filtrado por cliente


        if ($request->disablePaginate == 0) {
            $Productos = $Productos->orderBy('created_at', 'desc')->paginate(15);
        } else {
            $Productos = $Productos->orderBy('created_at', 'desc')->get();
        }

        // dd(DB::getQueryLog());

        if (count($Productos) > 0) {
            $response = $Productos;
        }

        return response()->json($response, $status);
    }

    public function UsuariosList(Request $request)
    {
        // dd($request->all());
        $response = [];
        $status = 200;

        // DB::enableQueryLog();

        $Usuarios =  User::query();

        $Usuarios->when(isset($request->estado), function ($q) use ($request) {
            return $q->where('estado', $request->estado);
        });

        if ($request->disablePaginate == 0) {
            $Usuarios = $Usuarios->orderBy('created_at', 'desc')->paginate(15);
        } else {
            $Usuarios = $Usuarios->orderBy('created_at', 'desc')->get();
        }

        if (count($Usuarios) > 0) {
            foreach ($Usuarios as $Usuario) {

                if (isset($request->factura) && $request->factura == 1) $Usuario->factura;

                if (isset($request->recibo) && $request->recibo == 1) {
                    if ($Usuario->recibo != null) {
                        $Usuario->ultimo_recibo = ReciboHistorial::where(
                            [
                                ["recibo_id", $Usuario->recibo->id],
                                // ["estado", 1],
                            ]
                        )->orderBy('created_at', 'desc')->first();
                    }
                }

                if (isset($request->meta) && $request->meta == 1) $Usuario->meta;

                if (isset($request->recibosRangosSinTerminar) && $request->recibosRangosSinTerminar == 1) {
                    $Usuario->recibosRangosSinTerminar = $Usuario->RecibosRangosSinTerminar()->where([
                        ["estado", 1],
                    ])->get();
                }

                $role_id = DB::table('model_has_roles')->where('model_id', $Usuario->id)->first();
                $Usuario->role_id = $role_id->role_id;
            }

            $response = $Usuarios;
        }

        return response()->json($response, $status);
    }

    public function CategoriaList(Request $request)
    {
        // dd($request->all());
        $response = [];
        $status = 200;

        // DB::enableQueryLog();

        $Categorias =  Categoria::query();

        $Categorias->when(isset($request->estado), function ($q) use ($request) {
            return $q->where('estado', $request->estado);
        });

        if ($request->disablePaginate == 0) {
            $Categorias = $Categorias->orderBy('created_at', 'desc')->paginate(15);
        } else {
            $Categorias = $Categorias->orderBy('created_at', 'desc')->get();
        }

        if (count($Categorias) > 0) {
            $response = $Categorias;
        }

        return response()->json($response, $status);
    }
}
