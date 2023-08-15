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
        padding-bottom: 40px
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
        font-size: 11.5px;
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
        /* position: absolute; */
        float: left;
        display: block;
        width: 70px;
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
{{-- <div class="page-break"></div> --}}

<body>

    @foreach($productos as $key => $page)

    <img class="logo" src="lib/img/logo_png.png" style="{{ $key > 0 ?  'margin-top: 15px' : '' }}" alt="">
    <h5 style="text-align: center;">M&R Profesional <br> ALTAMIRA DE DONDE FUE EL BDF 1C A LAGO 1C ARRIBA CONTIGUO A ETIRROL <br> Teléfonos: 84220028-88071569-81562408</h5>

    <div class="{{ ($key +1) == count($productos) ?  'border-total' : 'border' }}">
        <div class="seccion_supeior">
            <div class="left">
                <span class="direccion"><b>Proveedor:</b> {{ $user->name .' '. $user->apellido }}</span>
                <span class="direccion"><b>Cedula:</b> {{$user->cedula}}</span>
                <span class="direccion"><b>Domicilio:</b> {{$user->domicilio}}</span>
            </div>
            <div class="right">
                <span><b>Fecha:</b> {{ date("d/m/Y", strtotime(now())) }}</span>
                <span><b>Celular:</b> {{$user->celular}}</span>
            </div>
        </div>
        <div class="detail">
            <table style="width: 100%">
                <thead>
                    <tr>
                        <th>Descripcion</th>
                        <th style="text-align: center;">Cantidad</th>
                        <!-- <th>Precio</th> -->
                    </tr>
                </thead>
                <tbody>

                    @foreach($productos[$key] as $historico)
                    <tr>
                        <td>{{ $historico->descripcion }}</td>
                        <td style="text-align: center;">{{ $historico->cantidad }}</td>
                        <!-- <td>${{ $historico->precio }}</td> -->
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>
    @endforeach

    <div class="total">
        <span>Total</span>
        <span class="monto">${{ $incentivos }}</span>
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