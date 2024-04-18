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
        margin: 0px;
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
        font-size: 13px;
        border-bottom: 1px solid;
        padding-bottom: 3px;
    }

    .detail table th {
        font-size: 13px;

    }

    .detail table tbody th {
        font-size: 13px;
        font-weight: 400;
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
    <h5 style="float: right;">Fecha {{date("m/Y", strtotime($dataRequest->dateIni))}}</h5>
    <img class="logo" src="lib/img/logo_png.png" style="margin-top: 5px" alt="">
    <h5 style="margin-left: 100px;text-align: center;">M&R Profesional <br> ALTAMIRA DE DONDE FUE EL BDF 1C A LAGO 1C ARRIBA CONTIGUO A ETIRROL <br> Teléfonos: 84220028-88071569-81562408</h5>
    </div>
    <div class="border">
        <div class="detail">
            <table style="width: 100%">
                <thead>
                    <tr>
                        <th>FINANZA</th>
                        <th>MONTO</th>
                        <th>%</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <th scope="row">Ventas</th>
                        <th scope="row">
                            ${{ $ventas_total }}
                        </th>
                        <th scope="row">-</th>
                    </tr>
                    <tr>
                        <th scope="row">Costo</th>
                        <th scope="row">
                            ${{ $costo_total }}
                        </th>
                        <th scope="row">{{ $costo_total_porcentaje }}%</th>
                    </tr>
                    <tr>
                        <th scope="row">Utilidad bruta</th>
                        <th scope="row">
                            ${{ $utilidad_bruta_total }}
                        </th>
                        <th scope="row">-</th>
                    </tr>
                    <tr>
                        <th scope="row">Incentivo Vendedor</th>
                        <th scope="row">
                            ${{ $incentivos_vendedor_total }}
                        </th>
                        <th scope="row">-</th>
                    </tr>
                    <tr>
                        <th scope="row">Incentivo Supervisor</th>
                        <th scope="row">
                            ${{ $incentivos_supervisor_total  }}
                        </th>
                        <th scope="row">-</th>
                    </tr>
                    <tr>
                        <th scope="row">Gasto</th>
                        <th scope="row">
                            ${{ $gasto_total  }}
                        </th>
                        <th scope="row">{{ $gasto_total_porcentaje }}%</th>
                    </tr>
                    <tr>
                        <th scope="row">Utilidad neta</th>
                        <th scope="row">
                            ${{ $utilidad_neta_total }}
                        </th>
                        <th scope="row">{{$utilidad_neta_total_porcentaje }}%</th>
                    </tr>

                </tbody>
            </table>
        </div>
    </div>
</body>

</html>