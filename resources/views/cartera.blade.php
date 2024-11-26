<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>pdf</title>
</head>
<style>
    body {
        position: relative;
    }

    .content-titulo {
        display: flex;
        flex-direction: column;
        text-align: center;
        margin-left: -40px;
    }

    h4 {
        line-height: 1;
    }

    .border {
        width: 99%;
        display: block;
        height: 84%;
        border: 2px solid #000;
        border-top-left-radius: 30px;
        border-top-right-radius: 30px;
        padding: 10px
    }

    .seccion_supeior {
        display: flex;
        justify-content: space-between;
        width: 100%;
        margin-top: 15px;
        border-bottom: 2px solid #000;
        padding-bottom: 15px
    }

    .left {
        width: 100%;
        display: inline-block;
        text-align: center;
    }

    .left span {
        display: block;

    }

    .right {
        display: inline-block;
        float: right;
    }

    .right span {
        display: block;
        width: 220px;
    }

    .detail {
        width: 100%;
        margin: 5px;
    }

    .detail table th {
        text-align: left;
        font-size: 12px;
        border-bottom: 1px solid;
        padding-bottom: 3px;
    }

    .detail table td {
        font-size: 12px;

    }

    .footer {
        display: flex;
        justify-content: space-between;
        margin-top: 75px;
        width: 100%
    }

    .firmas {
        width: 150px;
        display: inline-block;
        border-top: 1px solid #000;
        margin: 0 40px;
        text-align: center;
    }

    .firmas span {
        display: block;
        font-size: 15px
    }

    .logo {
        position: absolute;
        float: left;
        display: block;
        width: 90px;
        height: 70px;
    }

    .total {
        display: block;
        width: 99%;
        border: 2px solid #000;
        border-bottom-left-radius: 30px;
        border-bottom-right-radius: 30px;
        padding: 10px
    }

    .total .monto {
        float: right;
    }

    .item {
        display: block;
        width: 95%;
        border: 2px solid #000;
        padding: 10px
    }

    .item .monto {
        float: right;
    }

    .direccion {
        width: 400px;
    }
</style>

<body>




    @foreach($data as $key => $item)
    <h6 style="float: right">Pagina {{ $key + 1 }} de {{ count($data) }} </h6>
    <img class="logo" src="lib/img/logo_png.png" style="margin-top: 5px" alt="">
    <h5 style="margin-left: 100px;text-align: center;">M&R Profesional <br> ALTAMIRA DE DONDE FUE EL BDF 1C A LAGO 1C ARRIBA CONTIGUO A ETIRROL <br> Teléfonos: 84220028-88071569-81562408</h5>
    </div>
    <div class="border">
        <div class="seccion_supeior">
            <div class="left">
                <span><b>Vendedor:</b> {{ $fullname }}</span>
            </div>

            {{-- <div class="right">
                </div> --}}
        </div>

        <div class="detail">
            <table style="width: 100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>NOMBRE CLIENTE</th>
                        <th>NOMBRE EMPRESA</th>
                        <th>MONTO FACTURA</th>
                        <th>SALDO</th>
                        <!-- <th>VENDEDOR</th> -->
                        <th>FECHA DE VENCIMIENTO</th>
                        <th>ULTIMO PAGO</th>
                        <th>UBICACIÓN</th>
                        <th>DIRECCIÓN CLIENTE</th>
                        <th>DIRECCIÓN EMPRESA</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($item as $factura)
                    <tr>
                        <td>{{ $factura->id }}</td>
                        <td>{{ $factura->cliente->nombreCompleto }}</td>
                        <td>{{ $factura->cliente->nombreEmpresa }}</td>
                        <td>${{ number_format((float) $factura->monto ,2,".","") }}</td>
                        <td>${{ number_format((float) $factura->saldo_restante ,2,".","") }}</td>
                        <!-- <td>{{ $factura->user->name.' '.$factura->user->apellido}}</td> -->
                        <td>{{ date("d/m/Y", strtotime($factura->fecha_vencimiento))}}</td>
                        <td>{{ isset($factura->cliente->factura_historial->created_at) ? date("d/m/Y", strtotime($factura->cliente->factura_historial->created_at)) : 'No posee pagos' }}</td>
                        <td>
                            @if (isset($factura->cliente->departamento) && $factura->cliente->departamento)
                            {{ ucwords(strtolower($factura->cliente->departamento->nombre)) }}
                            @else
                            -
                            @endif
                            -
                            @if (isset($factura->cliente->municipio) && $factura->cliente->municipio)
                            {{ ucwords(strtolower($factura->cliente->municipio->nombre)) }}
                            @else
                            -
                            @endif
                        </td>
                        <td>
                            @if (isset($factura->cliente->direccion_casa) && $factura->cliente->direccion_casa)
                            {{ $factura->cliente->direccion_casa }}
                            @else
                            --
                            @endif
                        </td>
                        <td>
                            @if (isset($factura->cliente->direccion_negocio) && $factura->cliente->direccion_negocio)
                            {{ $factura->cliente->direccion_negocio }}
                            @else
                            --
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>


    {{-- <div class="page-break"></div> --}}
    @endforeach
    <div class="total">
        <span>Total</span>
        <span class="monto">${{ bcdiv($total, 1, 2) }}</span>
    </div>
</body>

</html>