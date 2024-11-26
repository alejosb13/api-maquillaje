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
        width: 98%;
        display: block;
        height: 88%;
        border: 2px solid #000;
        border-top-left-radius: 30px;
        border-top-right-radius: 30px;
        padding: 10px;
    }

    .border-total {
        width: 98%;
        display: block;
        height: 75%;
        border: 2px solid #000;
        border-top-left-radius: 30px;
        border-top-right-radius: 30px;
        padding: 10px;
    }

    .seccion_supeior {
        display: flex;
        justify-content: space-between;
        width: 100%;
        margin-top: 15px;
        border-bottom: 2px solid #000;
        padding-bottom: 20px
    }

    .left {
        display: inline-block;
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
        border-bottom: 1px solid
    }

    .detail table td {
        font-size: 14px;
    }

    .footer {
        display: flex;
        justify-content: center;
        margin-top: 75px;
        width: 100%;
        text-align: center;

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
        float: left;
        display: block;
        width: 90px;
        height: 70px;
        z-index: 9999;
    }

    .total {
        display: block;
        width: 98%;
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
        width: 300px;
    }

    .page-break {
        page-break-after: always;
    }
</style>

<body>

    @foreach($productos as $key => $page)

    <img class="logo" src="lib/img/logo_png.png" style="{{ $key > 0 ?  'margin-top: 15px' : '' }}" alt="">
    <h5 style="text-align: center;">M&R Profesional <br> ALTAMIRA DE DONDE FUE EL BDF 1C A LAGO 1C ARRIBA CONTIGUO A ETIRROL <br> Teléfonos: 84220028-88071569-81562408</h5>

    <div class="{{ ($key +1) == count($productos) ?  'border-total' : 'border' }}">
        <div class="seccion_supeior">
            <div class="left">
                <span class="direccion"><b>Nombre Completo:</b> {{$data->cliente->nombreCompleto}}</span>
                <span class="direccion"><b>Nombre salon:</b> {{$data->cliente->nombreEmpresa}}</span>
                <span class="direccion"><b>Cedula:</b> {{$data->cliente->cedula}}</span>
                <span class="direccion"><b>Dirección:</b> {{ $data->cliente->departamento ? ucwords(strtolower($data->cliente->departamento->nombre)) :""}} - {{$data->cliente->municipio?ucwords(strtolower($data->cliente->municipio->nombre)):"" }} - {{ $data->cliente->direccion_casa }}</span>
                <span class="direccion"><b>Dirección salon:</b> {{ $data->cliente->departamento ? ucwords(strtolower($data->cliente->departamento->nombre)) :""}} - {{$data->cliente->municipio?ucwords(strtolower($data->cliente->municipio->nombre)):"" }} - {{$data->cliente->direccion_negocio}}</span>
                <span class="direccion"><b>Teléfono:</b> {{$data->cliente->celular}}</span>
                <span class="direccion"><b>Teléfono salon:</b> {{$data->cliente->telefono}}</span>
            </div>
            <div class="right">
                <span><b>factura:</b> #{{$data->id}}</span>
                <span><b>Fecha:</b> {{ date("d/m/Y", strtotime($data->created_at)) }}</span>
                <span><b>Fecha vencimiento:</b> {{ date("d/m/Y", strtotime($data->fecha_vencimiento)) }}</span>
                <span><b>Tipo Operacion:</b> {{ ($data->tipo_venta == 1)? 'Credito' : 'Contado'}}</span>
                <span><b>Estado:</b> {{ ($data->status_pagado == 0)? 'En proceso' : 'Finalizado'}}</span>
                <span><b>Zona:</b> {{ $data->cliente->zona ? ucwords(strtolower($data->cliente->zona->nombre)) : ''}}</span>
                <!-- <span><b>Vendedor:</b> {{ $data->user_data->name .' '. $data->user_data->apellido }}</span> -->
            </div>
        </div>
        <div class="detail">
            <table style="width: 100%">
                <thead>
                    <tr>
                        <th>Descripcion</th>
                        <th>Cantidad</th>
                        <th>Precio</th>
                    </tr>
                </thead>
                <tbody>

                    @foreach($productos[$key] as $product)
                    @if ($product->is_gift)
                    <tr>
                        <td>{{ $product->descripcion }}</td>
                        <td>{{ ($product->cantidad_regalada > 1) ? $product->cantidad_regalada.' Uds' : $product->cantidad_regalada.' Ud' }}</td>
                        <td>Regalo</td>
                    </tr>
                    @else
                    <tr>
                        <td>{{ $product->descripcion }}</td>
                        <td>{{ ($product->cantidad > 1) ? $product->cantidad.' Uds' : $product->cantidad.' Ud' }}</td>
                        <td>{{ bcdiv($product->precio, 1, 2) }} C$</td>
                    </tr>
                    @endif

                    @endforeach
                </tbody>
            </table>
        </div>

    </div>
    @endforeach

    <div class="total">
        <span>Total</span>
        <span class="monto">{{ bcdiv($data->monto, 1, 2) }} C$</span>
    </div>

    <div class="footer">

        <div class="firmas">
            <span>Firma Entrega</span>
        </div>
        <div class="firmas">
            <span>Firma Vendedor</span>
        </div>
        <div class="firmas">
            <span>Firma Recibo</span>
        </div>
    </div>
</body>

</html>