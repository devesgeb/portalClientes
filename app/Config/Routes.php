<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(true); // IMPORTANTE: Debe ser TRUE para rutas automáticas

$routes->get('/', 'Home::index');
$routes->get('/intranet', 'Home::index');

// Para la validación del formulario
$routes->post('/home/validaUser', 'Home::validaUser');
// O también puedes usar:
$routes->post('/validaUser', 'Home::validaUser');

// Dashboard después del login
$routes->get('/dashboard', 'Home::dashboard');

// Logout
$routes->get('/logout', 'Home::logout');

$routes->get('/test', function () {
    return "Test funciona!";
});

// Ruta de prueba para Home controller
$routes->get('/home', 'Home::index');
$routes->get('/home/index', 'Home::index');

// ── Balance Diario
$routes->get('/balance-diario', 'BalanceDiario::index');

// ── Cuentas por Cobrar (API JSON)
$routes->get('/cuentas-cobrar/pendientes', 'BalanceDiario::pendientesCobrar');
$routes->post('/cuentas-cobrar/sincronizar', 'BalanceDiario::sincronizarCobrar');
$routes->delete('/cuentas-cobrar/eliminar', 'BalanceDiario::eliminarCobrar');
// Verificar si N° documento existe
$routes->get('/cuentas-cobrar/verificar-documento', 'BalanceDiario::verificarDocumento');

// ── Cuentas por Pagar (API JSON)
$routes->get('/cuentas-pagar/pendientes', 'BalanceDiario::pendientesPagar');
$routes->post('/cuentas-pagar/sincronizar', 'BalanceDiario::sincronizarPagar');
$routes->delete('/cuentas-pagar/eliminar', 'BalanceDiario::eliminarPagar');
// Verificar si N° documento existe en Pagar
$routes->get('/cuentas-pagar/verificar-documento', 'BalanceDiario::verificarDocumentoPagar');

$routes->get('/home/validaUser', function () {
    return "GET a validaUser funcionando";
});
$routes->post('/home/validaUser', 'Home::validaUser');

// ── Panel de Administración
$routes->get('/admin', 'AdminController::index');
$routes->get('/admin/', 'AdminController::index');
// ── Cargar Clientes / Proveedores
$routes->get('/cargar-entidad', 'CargarEntidadController::index');
$routes->post('/importar-clientes', 'CargarEntidadController::importarClientes');
$routes->post('/importar-proveedores', 'CargarEntidadController::importarProveedores');

// ── Buscar Clientes / Proveedores
$routes->get('/buscar-entidad', 'BuscarEntidadController::index');
$routes->get('/buscar-entidad/buscar', 'BuscarEntidadController::buscar');
$routes->get('/buscar-entidad/detalle', 'BuscarEntidadController::detalle');
$routes->put('/buscar-entidad/actualizar', 'BuscarEntidadController::actualizar');
$routes->delete('/buscar-entidad/eliminar', 'BuscarEntidadController::eliminar');

// Autocomplete clientes / proveedores
$routes->get('/clientes/buscar', 'BalanceDiario::buscarClientes');
$routes->get('/proveedores/buscar', 'BalanceDiario::buscarProveedores');
