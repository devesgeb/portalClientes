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

// ── Balance Diario & Contabilidad
$routes->get('/balance-diario', 'BalanceDiario::index');
$routes->get('/historial-balances', 'ContabilidadController::historialBalances');

// ── Cuentas por Cobrar (API JSON)
$routes->get('/cuentas-cobrar/pendientes', 'BalanceDiario::pendientesCobrar');
$routes->post('/cuentas-cobrar/sincronizar', 'BalanceDiario::sincronizarCobrar');
$routes->delete('/cuentas-cobrar/eliminar', 'BalanceDiario::eliminarCobrar');
$routes->get('/cuentas-cobrar/verificar-documento', 'BalanceDiario::verificarDocumento');

// ── Cuentas por Pagar (API JSON)
$routes->get('/cuentas-pagar/pendientes', 'BalanceDiario::pendientesPagar');
$routes->post('/cuentas-pagar/sincronizar', 'BalanceDiario::sincronizarPagar');
$routes->delete('/cuentas-pagar/eliminar', 'BalanceDiario::eliminarPagar');

// ── Panel de Administración
$routes->get('/admin', 'AdminController::index');
$routes->get('/admin/', 'AdminController::index');

// ── Administración / Cuentas Bancarias / Efectivo
$routes->get('/administracion/cuentas-bancarias',                  'CuentasBancariasController::index');
$routes->get('/administracion/cuentas-bancarias/listar',           'CuentasBancariasController::listar');
$routes->get('/administracion/cuentas-bancarias/obtener/(:num)',    'CuentasBancariasController::obtener/$1');
$routes->post('/administracion/cuentas-bancarias/guardar',         'CuentasBancariasController::guardar');
$routes->post('/administracion/cuentas-bancarias/eliminar',        'CuentasBancariasController::eliminar');
$routes->post('/administracion/cuentas-bancarias/eliminar/(:num)',   'CuentasBancariasController::eliminar/$1');

// ── Usuarios
$routes->get('/usuarios',              'UsuariosController::index');
$routes->get('/usuarios/lista',        'UsuariosController::lista');
$routes->post('/usuarios/guardar',     'UsuariosController::guardar');
$routes->delete('/usuarios/eliminar/(:num)', 'UsuariosController::eliminar/$1');
$routes->patch('/usuarios/toggle-estado/(:num)', 'UsuariosController::toggleEstado/$1');

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

// Autocomplete clientes / proveedores & inventario
$routes->get('/clientes/buscar', 'BalanceDiario::buscarClientes');
$routes->post('/clientes/buscar-por-ruts', 'BalanceDiario::buscarClientesPorRuts');
$routes->get('/proveedores/buscar', 'BalanceDiario::buscarProveedores');
$routes->get('/inventario/productos', 'BalanceDiario::inventarioProductos');

// Cuentas por Pagar & Abonos (Balance Diario)
$routes->get('/cuentas-pagar/pendientes', 'BalanceDiario::pendientesPagar');
$routes->post('/cuentas-pagar/sincronizar', 'BalanceDiario::sincronizarPagar');
$routes->delete('/cuentas-pagar/eliminar', 'BalanceDiario::eliminarPagar');
$routes->get('/cuentas-pagar/verificar-documento', 'BalanceDiario::verificarDocumentoPagar');
$routes->get('/cuentas-pagar/abonos', 'BalanceDiario::listarAbonos');
$routes->post('/cuentas-pagar/abonos', 'BalanceDiario::registrarAbono');
$routes->delete('/cuentas-pagar/abonos/(:num)', 'BalanceDiario::eliminarAbono/$1');

// Cuentas por Cobrar
$routes->get('/cuentas-cobrar/pendientes', 'BalanceDiario::pendientesCobrar');
$routes->get('/cuentas-cobrar/verificar-documento', 'BalanceDiario::verificarDocumento');
$routes->delete('/cuentas-cobrar/eliminar', 'BalanceDiario::eliminarCobrar');

// ── Webhooks Tu Pana & Simulador ───────────────────────────────
$routes->get('/webhook-test', 'WebhookReceiver::index');
$routes->get('/webhook-test/buscar-dtes', 'WebhookReceiver::buscarDtes');
$routes->get('/webhook-test/logs', 'WebhookReceiver::getLogs');
$routes->delete('/webhook-test/logs', 'WebhookReceiver::clearLogs');
$routes->post('/webhook/tupana', 'WebhookReceiver::receive');

// ── Cobranza / Documentos Ventas (Facto API Ventas) ──────────────
$routes->get('/cobranza/documentos-impago',          'WebhookReceiver::documentosImpago');
$routes->get('/cobranza/facturas-facto',             'FactoController::facturasFacto');
$routes->get('/facturacion/importar/ventas',          'FactoController::facturasFacto');
$routes->get('/cobranza/facto/buscar-dtes',           'FactoController::buscarDtes');
$routes->post('/cobranza/facto/actualizar-estado-pago', 'FactoController::actualizarEstadoPago');
$routes->post('/cobranza/facto/actualizar-estado-masivo', 'FactoController::actualizarEstadoMasivo');
$routes->post('/cobranza/facto/importar-a-cobrar',     'FactoController::importarACobrar');
$routes->post('/cobranza/facto/marcar-pagadas-desde-cobrar', 'FactoController::marcarPagadasDesdeCobrar');
$routes->post('/cuentas-cobrar/marcar-pagadas-desde-cobrar', 'FactoController::marcarPagadasDesdeCobrar');
$routes->post('/cobranza/facto/actualizar-estado-flujo', 'FactoController::actualizarEstadoFlujo');
$routes->post('/cobranza/facto/ingresar-manual', 'FactoController::ingresarManual');
$routes->get('/ejecutar-migracion-db-prod', 'FactoController::ejecutarMigracionDb');

// ── Cobranza / Manejo de Caja
$routes->get('/cobranza/manejo-caja',          'ManejoCajaController::index');
$routes->get('/cobranza/manejo-caja/listar',   'ManejoCajaController::listar');
$routes->post('/cobranza/manejo-caja/guardar', 'ManejoCajaController::guardar');
$routes->post('/cobranza/manejo-caja/ajustar-saldo', 'ManejoCajaController::ajustarSaldo');

// ── Facturación / Documentos Proveedores (Facto API Compras) ───────
$routes->get('/facturacion/importar/proveedores',            'FactoProveedorController::index');
$routes->get('/facturacion/proveedores/buscar-dtes',          'FactoProveedorController::buscarDtes');
$routes->post('/facturacion/proveedores/actualizar-estado-pago', 'FactoProveedorController::actualizarEstadoPago');
$routes->post('/facturacion/proveedores/actualizar-estado-masivo', 'FactoProveedorController::actualizarEstadoMasivo');
$routes->post('/facturacion/proveedores/importar-a-pagar',    'FactoProveedorController::importarAPagar');

// ── Logística ──────────────────────────────────────────────────
$routes->get('/hoja-de-ruta',          'LogisticaController::hojaDeRuta');
$routes->get('/despachos-agendados',   'LogisticaController::despachosAgendados');
$routes->get('/funcionarios',          'LogisticaController::funcionarios');
$routes->get('/logistica/clientes',    'LogisticaController::clientes');

// API Funcionarios
$routes->get('/logistica/api/funcionarios',                   'LogisticaController::apiFuncionarios');
$routes->post('/logistica/api/funcionarios',                  'LogisticaController::crearFuncionario');
$routes->put('/logistica/api/funcionarios/(:num)',            'LogisticaController::actualizarFuncionario/$1');
$routes->delete('/logistica/api/funcionarios/(:num)',         'LogisticaController::eliminarFuncionario/$1');
$routes->get('/logistica/api/funcionarios/(:num)/eventos',   'LogisticaController::apiEventos/$1');
$routes->post('/logistica/api/funcionarios/(:num)/eventos',  'LogisticaController::crearEvento/$1');
$routes->delete('/logistica/api/eventos/(:num)',              'LogisticaController::eliminarEvento/$1');

// ── Bodega & Productos ───────────────
$routes->get('/productos',                         'BodegaController::factoProductos');
$routes->get('/bodega/lista-productos',            'BodegaController::listarProductos');
$routes->get('/bodega/producto/(:any)',             'BodegaController::getProducto/$1');
$routes->patch('/bodega/producto/(:any)',           'BodegaController::actualizarProducto/$1');
$routes->get('/bodega/reservas/(:any)',             'BodegaController::getReservas/$1');
$routes->post('/bodega/reservas',                   'BodegaController::guardarReserva');
$routes->post('/bodega/reservas/descontar',          'BodegaController::descontarReserva');
$routes->delete('/bodega/reservas/(:num)',          'BodegaController::eliminarReserva/$1');
$routes->delete('/bodega/producto/(:any)',          'BodegaController::eliminarProducto/$1');
$routes->get('/carga-masiva-productos',            'BodegaController::cargaMasiva');
$routes->post('/bodega/importar-productos',        'BodegaController::importarProductos');
$routes->post('/bodega/importar-lista-precios',    'BodegaController::importarListaPrecios');
$routes->get('/bodega/plantilla-productos',        'BodegaController::plantillaProductos');
$routes->get('/bodega/plantilla-lista-precios',    'BodegaController::plantillaListaPrecios');
$routes->get('/bodega/stats',                      'BodegaController::stats');

// ── Prototipo Facto Productos, Precios y Stock ──
$routes->get('/bodega/facto-productos',            'BodegaController::factoProductos');
$routes->get('/bodega/facto-productos/listar',     'BodegaController::listarFactoProductos');
$routes->get('/bodega/facto-productos/exportar-pdf', 'BodegaController::exportarCatalogoPdf');


// ── Cron Sincronización Facto API ──
$routes->get('/cron/sync-facto',                   'CronController::syncFacto');
$routes->cli('/cron/sync-facto',                   'CronController::syncFacto');
$routes->get('/cron/sync-facto-productos',         'CronController::syncFactoProductos');
$routes->cli('/cron/sync-facto-productos',         'CronController::syncFactoProductos');

// ── Webhook Receptor & Monitor (WooCommerce / Facto) ──
$routes->get('/webhook/monitor',                   'WebhookController::monitor');
$routes->post('/webhook/receiver',                 'WebhookController::receiver');
$routes->get('/webhook/logs-json',                 'WebhookController::getLogsJson');
$routes->post('/webhook/test-simular',             'WebhookController::testSimular');
$routes->delete('/webhook/limpiar-logs',           'WebhookController::limpiarLogs');