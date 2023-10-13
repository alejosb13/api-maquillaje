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

    .detail table td .simbolo-precio{
        font-size: 12px;
        font-weight: 900;
    }

    .detail table {
        border-collapse: collapse;
    }

    .detail table tr {
        border-bottom: 1px solid black;
    }
    
    .detail table tr .separador {
        border-left: 1px solid black;
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
        float: left;
        display: block;
        width: 95px;
        height: 60px;
        z-index: 9999;
    }

    .total {
        display: block;
        width: 95%;
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
        width: 200px;
    }

    .page-break {
        page-break-after: always;
    }

    tbody tr td {
        margin-bottom: 5px;
    }
</style>
{{-- <div class="page-break"></div> --}}

<body>

    @foreach($data as $key => $page)
    <img class="logo" src="lib/img/logo_png.png" alt="">
    <h5 style="text-align: right;">Inventario Página {{ $key + 1 }} / {{ count($data)}} </h5>
    <h5 style="text-align: right;">Fecha: {{ date("d/m/Y") }}</h5>

    <div class="border">
        <div class="detail">
            <table style="width: 100%">
                <thead>
                    <tr>
                        <th>Marca</th>
                        <th>Stock</th>
                        <th style="min-width:70px;">Precio</th>
                        <th>Descripción</th>
                        <th>Observación</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data[$key] as $producto)
                    <tr>
                        <td>{{ $producto->marca }}</td>
                        <td>{{ $producto->stock }}</td>
                        <td>{{ bcdiv($producto->precio, 1, 2) }} <span class="simbolo-precio">C$</span></td>
                        <td>{{ $producto->descripcion }}</td>
                        <td class="separador"> </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- <div class="page-break"></div> --}}
    @endforeach

</body>

</html>