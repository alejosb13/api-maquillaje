<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>pdf</title>
</head>
<style>
    body{
        position: relative;
    }
    .content-titulo{
        display: flex;
        flex-direction: column;
        text-align: center;
        margin-left: -40px;
    }
    h4{
        line-height: 1;
    }
    .border{
        width: 95%;
        display: block;
        height: 70%;
        border: 2px solid #000;
        border-top-left-radius: 30px;
        border-top-right-radius: 30px;
        padding: 10px
    }
    .seccion_supeior{
        display: flex;
        justify-content: space-between;
        width: 100%;
        margin-top: 15px;
        border-bottom: 2px solid #000;
        padding-bottom: 15px
    }
    .left{
        display: inline-block;
    }
    .left span{
        display: block;

    }
    .right{
        display: inline-block;
        float: right;
    }
    .right span{
        display: block;
        width: 220px;
    }
    .detail{
        width: 100%;
        margin: 5px;
    }
    .detail table th{
        text-align: left;
    }
    .detail table td{
        font-size: 11;
    }
    .footer{
        display: flex;
        justify-content: space-between;
        margin-top: 75px;
        width: 100%
    }

    .firmas{
        width: 150px;
        display: inline-block;
        border-top: 1px solid #000;
        margin: 0 40px;
        text-align: center;
    }
    .firmas span{
        display: block;
        font-size: 15px
    }
    .logo{
        position: absolute;
        float: left;
        display: block;
        width: 90px;
        height: 70px;
    }
    .total{
        display: block;
        width: 95%;
        border: 2px solid #000;
        border-bottom-left-radius: 30px;
        border-bottom-right-radius: 30px;
        padding: 10px
    }
    .total .monto{
        float: right;
    }
    .item{
        display: block;
        width: 95%;
        border: 2px solid #000;
        padding: 10px
    }
    .item .monto{
        float: right;
    }
    .direccion{
        width: 400px;
    }
</style>
<body>

    <img class="logo" src="lib/img/logo_png.png" alt="">
    <div class="content-titulo">
        {{-- <h5>IMPORTACIONES CLIO NICARAGUA <br> ALTAMIRA DE DONDE FUE EL BDF 1C A LAGO 1C ARRIBA CONTIGUO A ETIRROL <br> 81562409784214465</h5> --}}
        <h5>M&R Profesional <br> ALTAMIRA DE DONDE FUE EL BDF 1C A LAGO 1C ARRIBA CONTIGUO A ETIRROL <br> Teléfonos: 84220028-88071569-81562408</h5>
    </div>
    <div class="border">
        <div class="seccion_supeior">
            <div class="left">
                <span class="direccion"><b>Nombre Completo:</b> {{$data->cliente->nombreCompleto}}</span>
                <span class="direccion"><b>Nombre salon:</b> {{$data->cliente->nombreEmpresa}}</span>
                <span class="direccion"><b>Cedula:</b> {{$data->cliente->cedula}}</span>
                {{-- <span><b>Dirección:</b> {{$data->cliente->direccion_casa}}</span>
                <span><b>Dirección salon:</b> {{$data->cliente->direccion_negocio}}</span> --}}
                <span class="direccion"><b>Dirección:</b> {{$data->cliente->direccion_casa}}</span>
                <span class="direccion"><b>Dirección salon:</b> {{$data->cliente->direccion_negocio}}</span>
                <span class="direccion"><b>Teléfono:</b> {{$data->cliente->celular}}</span>
                <span class="direccion"><b>Teléfono salon:</b> {{$data->cliente->telefono}}</span>
            </div>
            <div class="right">
                <span><b>factura:</b> #{{$data->id}}</span>
                <span><b>Fecha:</b> {{ date("d/m/Y", strtotime($data->created_at)) }}</span>
                <span><b>Fecha vencimiento:</b> {{ date("d/m/Y", strtotime($data->fecha_vencimiento)) }}</span>
                <span><b>Tipo Operacion:</b> {{ ($data->tipo_venta == 1)? 'Credito' : 'Contado'}}</span>
                <span><b>Estado:</b> {{ ($data->status_pagado == 0)? 'En proceso' : 'Finalizado'}}</span>
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
                    @foreach($data->factura_detalle as $producto)
                    <tr>
                        <td>{{ $producto->descripcion }}</td>
                        <td>{{ ($producto->cantidad > 1) ? $producto->cantidad.' Uds' : $producto->cantidad.' Ud' }}</td>
                        <td>{{ bcdiv($producto->precio, 1, 2) }} C$</td>
                    </tr>
                    @endforeach
                    @foreach($regalos as $regalo)
                    <tr>
                        <td>{{ $regalo->detalle_regalo->descripcion }}</td>
                        <td>{{ ($regalo->cantidad_regalada > 1) ? $regalo->cantidad_regalada.' Uds' : $regalo->cantidad_regalada.' Ud' }}</td>
                        <td>Regalo</td>
                    </tr>
                    @endforeach
                    {{-- <tr>
                        <td colspan="2">Total</td>
                        <td>{{ $data->monto }}.00 C$</td>
                    </tr> --}}
                </tbody>
            </table>
            <table>
                <tbody>

                </tbody>
            </table>
        </div>
    </div>
    <!-- <div class="item">
        <span>Diferencia</span>
        <span class="monto">$50.00</span>
    </div>
    <div class="item">
        <span>Abonado</span>
        <span class="monto">$105.00</span>
    </div> -->
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
