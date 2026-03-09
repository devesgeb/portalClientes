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
$routes->post('/cuentas-cobrar/guardar', 'BalanceDiario::guardar');
$routes->post('/cuentas-cobrar/sincronizar', 'BalanceDiario::sincronizar');
$routes->delete('/cuentas-cobrar/eliminar', 'BalanceDiario::eliminarCobrar');
$routes->get('/cuentas-cobrar/pendientes', 'BalanceDiario::pendientes');
$routes->get('/home/validaUser', function () {
    return "GET a validaUser funcionando";
});
$routes->post('/home/validaUser', 'Home::validaUser');