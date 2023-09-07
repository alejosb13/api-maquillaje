<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DevolucionFacturaController;
use App\Http\Controllers\DevolucionProductoController;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\FacturaDetallesController;
use App\Http\Controllers\FacturaHistorial;
use App\Http\Controllers\FrecuenciaController;
use App\Http\Controllers\FrecuenciasFacturasController;
use App\Http\Controllers\ListadosPaginasController;
use App\Http\Controllers\LogisticaController;
use App\Http\Controllers\MetasController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\ProductosController;
use App\Http\Controllers\ReciboController;
use App\Http\Controllers\ReciboHistorialContadoController;
use App\Http\Controllers\ReciboHistorialController;
use App\Http\Controllers\RegalosController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ScriptController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


//register new user

Route::post('/create-account', [AuthenticationController::class, 'createAccount']);
//login user
Route::post('/signin', [AuthenticationController::class, 'signin']);

//using middleware
Route::group(['middleware' => ['auth:sanctum', 'role:administrador|vendedor|supervisor']], function () {
    Route::post('/sign-out', [AuthenticationController::class, 'signout']);
    Route::get('/profile', function (Request $request) {
        return auth()->user();
    });
});

// Route::middleware(
//     [
//         'auth:sanctum',     // autenticate
//         'role:admin'   // role
//     ]
//     )
//     ->prefix('auth')->group(function () {

// });



Route::get('xlsx/registroclientes', [PdfController::class, 'registro_cliente_excell']);
Route::get('csv/registroclientes', [PdfController::class, 'registro_cliente_csv']);
Route::get('pdf/registroclientes', [PdfController::class, 'registro_cliente']);
Route::post('pdf/productos_vendidos_usuario', [PdfController::class, 'productosVendidosUsuario']);
Route::post('pdf/productos_vendidos_supervisor', [PdfController::class, 'productosVendidosSupervisor']);
Route::get('pdf/productos_vendidos', [PdfController::class, 'productosVendidos']);
Route::get('pdf/{id}', [PdfController::class, 'facturaPagonew']);
Route::get('pdf/estado_cuenta/{id}', [PdfController::class, 'estadoCuenta']);
Route::post('pdf/cartera', [PdfController::class, 'cartera']);
Route::get('pdf/productos/inventario', [PdfController::class, 'inventario']);
// Route::post('pdf', [PdfController::class,'generar']);


Route::resource('devolucion-factura', DevolucionFacturaController::class);
Route::resource('devoluciones-producto', DevolucionProductoController::class);

// Route::get('mail/{id}', [PdfController::class, 'SendMail']);

Route::get('script/AsignarPrecioPorUnidadGlobal', [ScriptController::class, 'AsignarPrecioPorUnidadGlobal']);
Route::get('script/validarStatusPagadoGlobal', [ScriptController::class, 'validarStatusPagadoGlobal']);
Route::get('script/actualizarPrecioFactura/{id}', [ScriptController::class, 'ActualizarPrecioFactura']);
Route::get('script/validar-meta-recuperacion', [ScriptController::class, 'validarMetaRecuperacion']);



Route::group(['middleware' => ['auth:sanctum', 'role:administrador|vendedor|supervisor', 'cierre']], function () {
    Route::post('logistica/cartera-date', [LogisticaController::class, 'carteraDate']);
    Route::post('logistica/recibo-date', [LogisticaController::class, 'reciboDate']);
    Route::post('logistica/mora-30-60', [LogisticaController::class, 'Mora30A60']);
    Route::post('logistica/mora-60-90', [LogisticaController::class, 'Mora60A90']);
    Route::post('logistica/cliente-new', [LogisticaController::class, 'clienteDate']);
    Route::post('logistica/incentivo', [LogisticaController::class, 'incentivo']);
    Route::post('logistica/incentivo-supervisor', [LogisticaController::class, 'incentivoSupervisor']);
    Route::post('logistica/cliente-inactivo', [LogisticaController::class, 'clienteInactivo']);
    Route::post('logistica/estado-de-cuenta', [LogisticaController::class, 'estadoCuenta']);
    Route::get('logistica/producto-logistica', [LogisticaController::class, 'productoLogistica']);
    Route::post('logistica/clientes-reactivados', [LogisticaController::class, 'clientesReactivados']);
    Route::post('logistica/ventas', [LogisticaController::class, 'ventasDate']);
    Route::post('logistica/recuperacion', [LogisticaController::class, 'recuperacion']);
    Route::post('logistica/productos-vendidos', [LogisticaController::class, 'productosVendidos']);
    Route::post('logistica/resumen-dashboard', [LogisticaController::class, 'resumenDashboard']);
    Route::post('logistica/resumen-dashboard-admin', [LogisticaController::class, 'resumenDashboardAdmin']);
    
    Route::get('resumen/dashboard/user/{id}', [DashboardController::class, 'show']);

    Route::get('cliente/factura/{id}',  [ClienteController::class, 'clienteToFactura']);
    Route::get('cliente/abono/{id}',  [ClienteController::class, 'calcularAbono']);
    Route::get('cliente/deuda/{id}',  [ClienteController::class, 'calcularDeudaVendedorCliente']);
    Route::get('cliente/deuda',  [ClienteController::class, 'calcularDeudaVendedorTodosClientes']);
    Route::get('cliente/deuda/user/{id}',  [ClienteController::class, 'calcularDeudaVendedorTodosClientesPorUsuario']);
    Route::get('cliente/usuario/{id}',  [ClienteController::class, 'clientesVendedor']);
    Route::resource('cliente', ClienteController::class);

    Route::resource('roles', RoleController::class);

    Route::resource('usuarios', UsuarioController::class);

    Route::put('update-password/{id}',  [UsuarioController::class, 'updatePassword']);

    Route::resource('categorias', CategoriaController::class);

    Route::resource('frecuencias', FrecuenciaController::class);
    Route::resource('frecuencias-factura', FrecuenciasFacturasController::class);

    Route::resource('productos', ProductosController::class);

    Route::resource('factura-detalle', FacturaDetallesController::class);

    Route::resource('facturas', FacturaController::class);
    Route::put('facturas/despachar/{id}', [FacturaController::class, 'despachar']);
    Route::put('facturas/entregada/{id}', [FacturaController::class, 'entregada']);

    Route::resource('abonos', FacturaHistorial::class);

    Route::resource('recibos', ReciboController::class);
    Route::resource('recibos/historial/contado', ReciboHistorialContadoController::class);
    Route::resource('recibos/historial/credito', ReciboHistorialController::class);
    Route::get('recibos/number/{id}', [ReciboController::class, 'getNumeroRecibo']);

    Route::resource('metas', MetasController::class);
    Route::put('metas-historial/{id}', [MetasController::class, 'editarMetaHistorial']);
    Route::delete('metas-historial/{id}', [MetasController::class, 'eliminarMetaHistorial']);
    Route::post('metas-historial/new', [MetasController::class, 'crearMetaHistorial']);

    Route::get('regalos/detalle/{id}', [RegalosController::class, 'regaloXdetalle']);
    Route::get('regalos/factura/{id}', [RegalosController::class, 'regalosXFactura']);
    Route::resource('regalos', RegalosController::class);


    // Route::post('configuracion/migracion', [ConfiguracionController::class, 'migracion']);
    Route::post('configuracion/migracion', [ConfiguracionController::class, 'migracionNew']);
    Route::post('configuracion/taza-cambio', [ConfiguracionController::class, 'saveTazaCambio']);
    Route::get('configuracion/taza-cambio', [ConfiguracionController::class, 'getTazaCambio']);
    Route::get('configuracion/cierre', [ConfiguracionController::class, 'getCierraConfig']);
    Route::put('configuracion/cierre', [ConfiguracionController::class, 'updateCierraConfig']);

    Route::patch('configuracion/taza-cambio/factura', [ConfiguracionController::class, 'updateTazaCambioFactura']);
    Route::post('configuracion/taza-cambio/factura', [ConfiguracionController::class, 'saveTazaCambioFactura']);
    Route::get('configuracion/taza-cambio/factura/{id}', [ConfiguracionController::class, 'getTazaCambioFactura']);
});


Route::get('list/facturas', [ListadosPaginasController::class, 'facturasList']);
Route::get('list/metas', [ListadosPaginasController::class, 'metasHistoricoList']);
Route::get('list/recibos', [ListadosPaginasController::class, 'recibosCreditosList']);
Route::get('list/abonos', [ListadosPaginasController::class, 'abonosCreditosList']);
Route::get('list/clientes', [ListadosPaginasController::class, 'clientesList']);
Route::get('list/productos-clientes', [ListadosPaginasController::class, 'FacturaDetailClientList']);

Route::get('configuracion/crons', function () {
    Artisan::call('meta:recuperacion');
    // echo Artisan::output();
});
Route::get('configuracion/crons-5-min', function () {
    Artisan::call('save:indice');
    echo Artisan::output();
});
Route::get('configuracion/clear-cache', function () {
    echo Artisan::call('config:clear');
    echo Artisan::call('config:cache');
    echo Artisan::call('cache:clear');
    echo Artisan::call('route:clear');
});


// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
