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
        border-bottom: 1px solid;
        font-size: 12.5px;
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
        position: absolute;
        float: left;
        display: block;
        width: 70px;
        height: 70px;
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
</style>
{{-- <div class="page-break"></div> --}}

<body>

    @foreach($data as $key => $page)
    <h6 style="float: right">Pagina {{ $key + 1 }} de {{ count($data) }} <br>Total {{ $total }} </h6>
    <img class="logo" src="lib/img/logo_png.png" style="margin-top: 15px" alt="">
    <h5 style="text-align: center;">M&R Profesional <br> ALTAMIRA DE DONDE FUE EL BDF 1C A LAGO 1C ARRIBA CONTIGUO A ETIRROL <br> Teléfonos: 84220028-88071569-81562408</h5>
    </div>
    <div class="border">


        <div class="detail">
            <table style="width: 100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>NOMBRE</th>
                        <th>EMPRESA</th>
                        <th>USUARIO</th>
                        <th>CEDULA</th>
                        <th>DÍAS COBRO</th>
                        <th>DIRECCION DE CASA</th>
                        <th>DIRECCION DE NEGOCIO</th>
                        <th>CATEGORIA</th>
                        <th>FRECUENCIA</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data[$key] as $cliente)
                    <tr>
                        <td>{{ $cliente->id }}</td>
                        <td>{{ $cliente->nombreCompleto }}</td>
                        <td>{{ $cliente->nombreEmpresa }}</td>
                        <td>{{ $cliente->user_id ? $cliente->user->name." ".  $cliente->user->apellido : "-" }}</td>
                        <td>{{ $cliente->cedula }}</td>
                        <td>{{ $cliente->dias_cobro }}</td>
                        <td>{{ $cliente->direccion_casa }}</td>
                        <td>{{ $cliente->direccion_negocio }}</td>
                        <td>{{ $cliente->categoria->descripcion }}</td>
                        <td>{{ $cliente->frecuencia ? $cliente->frecuencia->descripcion : "-"}}</td>

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