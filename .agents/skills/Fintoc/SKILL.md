---
name: Integración Fintoc — Conciliación Bancaria Santander
description: Estrategia completa para implementar la vista de conciliación bancaria usando Fintoc como puente con Banco Santander Chile. Incluye arquitectura, rutas, modelos, widget JS y algoritmo de conciliación.
---

# Skill: Integración Fintoc — Conciliación Bancaria

## Contexto del proyecto

Este portal usa **CodeIgniter 4** en `c:\xampp\htdocs\Portal`. La base de datos principal es `admin` (MySQL). El diseño usa el sidebar compartido (`partials/sidebar.php`) y el sistema de rutas en `app/Config/Routes.php`.

Las facturas impagas están en `admin.tbl_cuentasCobrar` (modelo `CuentasCobrarModel.php`).

---

## Cuándo usar esto

- Cuando el usuario pida implementar la vista de **conciliación bancaria**
- Cuando se mencione **Fintoc**, **Santander**, o **Open Banking**
- Cuando se quiera conectar movimientos bancarios con facturas impagas
 c cx x  
---

## Prerrequisitos (verificar antes de codificar)

1. El usuario debe tener una cuenta en [fintoc.com](https://fintoc.com) (sandbox gratuito disponible)
2. Necesita: **`FINTOC_PUBLIC_KEY`** (para el widget) y **`FINTOC_SECRET_KEY`** (para el backend)
3. El servidor XAMPP debe poder hacer llamadas salientes a `api.fintoc.com` (puerto 443)
4. `institutionId` para Santander Chile = **`cl_banco_santander`**
5. `holderType` para empresa = **`business`**; para persona = **`individual`**

---

## Arquitectura de la solución

### Flujo completo

```
[Frontend]                          [Backend (CI4)]             [Fintoc API]
                                                                
1. Clic "Conectar Santander"
2. POST /fintoc/create-intent  →  FintocController::createIntent → POST /link_intents
                               ←  { widget_token }
3. Fintoc.create({...token}).open()
4. Usuario autentica en Santander (widget Fintoc)
5. onSuccess(exchangeToken)
6. POST /fintoc/vincular       →  FintocController::vincular    → GET /exchange?token=...
                               ←  link guardado en DB           → guarda link_token en tbl_fintoc_links
7. GET /fintoc/movimientos     →  FintocController::movimientos → GET /links/{id}/accounts/{acc_id}/movements
                               ←  JSON movimientos
8. POST /fintoc/conciliar      →  FintocController::conciliar   → actualiza tbl_conciliaciones
```

### Tablas nuevas en DB `admin`

```sql
-- Vincular cuenta bancaria al portal
CREATE TABLE tbl_fintoc_links (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL,
    link_token   VARCHAR(200) NOT NULL,
    link_id      VARCHAR(100) NOT NULL,
    account_id   VARCHAR(100) NOT NULL,
    institution  VARCHAR(100) DEFAULT 'cl_banco_santander',
    holder_name  VARCHAR(200) NULL,
    created_at   DATETIME NULL,
    updated_at   DATETIME NULL
) ENGINE=InnoDB;

-- Registro de matches movimiento ↔ factura
CREATE TABLE tbl_conciliaciones (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    movimiento_id       VARCHAR(100) NOT NULL,   -- ID de Fintoc
    factura_id          INT UNSIGNED NOT NULL,   -- FK tbl_cuentasCobrar
    monto               DECIMAL(15,2) NOT NULL,
    fecha_movimiento    DATE NOT NULL,
    descripcion_banco   VARCHAR(500) NULL,
    estado              ENUM('pendiente','confirmado','rechazado') DEFAULT 'pendiente',
    user_id             INT UNSIGNED NULL,
    created_at          DATETIME NULL,
    updated_at          DATETIME NULL
) ENGINE=InnoDB;
```

---

## Archivos a crear

| Archivo | Ruta completa |
|---------|--------------|
| Controller | `app/Controllers/FintocController.php` |
| Model - Links | `app/Models/FintocLinkModel.php` |
| Model - Conciliaciones | `app/Models/ConciliacionModel.php` |
| View | `app/Views/conciliacion.php` |

---

## Instrucciones de implementación paso a paso

### Paso 1 — Credenciales de Fintoc

Guardar las API keys en `app/Config/` como constantes o como variables de entorno en `.env`:

```ini
# .env (en la raíz del proyecto CI4)
FINTOC_PUBLIC_KEY  = pk_live_XXXXXXXXXXXX
FINTOC_SECRET_KEY  = sk_live_XXXXXXXXXXXX
FINTOC_WEBHOOK_URL = https://tu-dominio.cl/fintoc/webhook
```

Para leer en PHP:
```php
$secretKey = env('FINTOC_SECRET_KEY');
$publicKey  = env('FINTOC_PUBLIC_KEY');
```

### Paso 2 — FintocController.php

```php
<?php
namespace App\Controllers;

use App\Models\FintocLinkModel;
use App\Models\ConciliacionModel;
use App\Models\CuentasCobrarModel;
use CodeIgniter\HTTP\ResponseInterface;

class FintocController extends BaseController
{
    private string $apiBase   = 'https://api.fintoc.com/v1';
    private string $secretKey;
    private string $publicKey;

    public function __construct()
    {
        $this->secretKey = env('FINTOC_SECRET_KEY', '');
        $this->publicKey = env('FINTOC_PUBLIC_KEY', '');
        helper(['url', 'form']);
    }

    /** GET /conciliacion — Vista principal */
    public function index(): string
    {
        $session  = session();
        $loginModel = new \App\Models\LoginModel();
        $usuario    = $loginModel->obtenerPorId($session->get('is_logued_in'));

        $linkModel  = new FintocLinkModel();
        $linkActivo = $linkModel->where('user_id', $usuario['id'])->first();

        return view('conciliacion', [
            'title'      => 'Conciliación Bancaria',
            'activePage' => 'conciliacion',
            'usuario'    => $usuario,
            'publicKey'  => $this->publicKey,
            'linkActivo' => $linkActivo,
        ]);
    }

    /** POST /fintoc/vincular — Recibe exchangeToken del widget y lo canjea */
    public function vincular(): ResponseInterface
    {
        $exchangeToken = $this->request->getPost('exchange_token');
        if (!$exchangeToken) {
            return $this->response->setJSON(['error' => 'token requerido'])->setStatusCode(400);
        }

        // Canjear por link permanente
        $resp = $this->fintocGet("/exchange?token={$exchangeToken}");
        if (!isset($resp['id'])) {
            return $this->response->setJSON(['error' => 'no se pudo vincular'])->setStatusCode(502);
        }

        // Guardar link en DB
        $session   = session();
        $linkModel = new FintocLinkModel();
        $linkModel->save([
            'user_id'   => $session->get('is_logued_in'),
            'link_token'=> $resp['token'],
            'link_id'   => $resp['id'],
            'account_id'=> $resp['accounts'][0]['id'] ?? '',
            'holder_name'=> $resp['accounts'][0]['holder_name'] ?? '',
        ]);

        return $this->response->setJSON(['ok' => true, 'link_id' => $resp['id']]);
    }

    /** GET /fintoc/movimientos?desde=2024-01-01&hasta=2024-03-31 */
    public function movimientos(): ResponseInterface
    {
        $session   = session();
        $linkModel = new FintocLinkModel();
        $link      = $linkModel->where('user_id', $session->get('is_logued_in'))->first();

        if (!$link) {
            return $this->response->setJSON(['error' => 'sin cuenta vinculada'])->setStatusCode(404);
        }

        $desde = $this->request->getGet('desde') ?? date('Y-m-01');
        $hasta = $this->request->getGet('hasta') ?? date('Y-m-d');

        $endpoint = "/links/{$link['link_id']}/accounts/{$link['account_id']}/movements"
                  . "?since={$desde}&until={$hasta}";

        $movimientos = $this->fintocGet($endpoint);
        return $this->response->setJSON($movimientos);
    }

    /** POST /fintoc/conciliar — Guarda un match movimiento↔factura */
    public function conciliar(): ResponseInterface
    {
        $data = $this->request->getJSON(true);
        $concModel = new ConciliacionModel();
        $concModel->save([
            'movimiento_id'    => $data['movimiento_id'],
            'factura_id'       => $data['factura_id'],
            'monto'            => $data['monto'],
            'fecha_movimiento' => $data['fecha'],
            'descripcion_banco'=> $data['descripcion'] ?? '',
            'estado'           => 'confirmado',
            'user_id'          => session()->get('is_logued_in'),
        ]);
        return $this->response->setJSON(['ok' => true]);
    }

    /** Llamada HTTP a la API de Fintoc (GET) */
    private function fintocGet(string $endpoint): array
    {
        $ch = curl_init($this->apiBase . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => $this->secretKey . ':',
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
        return json_decode($body, true) ?? [];
    }
}
```

### Paso 3 — Rutas en Routes.php

```php
// ── Conciliación Bancaria (Fintoc)
$routes->get('/conciliacion',           'FintocController::index');
$routes->post('/fintoc/vincular',       'FintocController::vincular');
$routes->get('/fintoc/movimientos',     'FintocController::movimientos');
$routes->post('/fintoc/conciliar',      'FintocController::conciliar');
$routes->post('/fintoc/webhook',        'FintocController::webhook');
```

### Paso 4 — Widget JS en la vista `conciliacion.php`

```html
<script src="https://js.fintoc.com/v1/"></script>
<script>
var fintocWidget = null;
window.onload = function () {
    fintocWidget = Fintoc.create({
        publicKey:     "<?= esc($publicKey) ?>",
        product:       "movements",
        country:       "cl",
        institutionId: "cl_banco_santander",
        holderType:    "business",
        onSuccess: function (exchangeToken) {
            vincularCuenta(exchangeToken);
        },
        onExit: function () { console.log('Widget cerrado'); },
    });
};

function abrirFintoc() { fintocWidget && fintocWidget.open(); }

function vincularCuenta(token) {
    fetch("<?= site_url('fintoc/vincular') ?>", {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'exchange_token=' + encodeURIComponent(token)
    })
    .then(r => r.json())
    .then(d => { if (d.ok) location.reload(); });
}
</script>
```

### Paso 5 — Algoritmo de auto-conciliación (JS)

```javascript
function autoConciliar(movimientos, facturas) {
    const matches = [];
    movimientos.forEach(function (mov) {
        if (mov.type !== 'credit') return;  // solo créditos (cobros)
        const monto  = Math.abs(mov.amount) / 100;  // Fintoc usa centavos
        const candidatos = facturas.filter(f =>
            Math.abs(f.impago - monto) <= 100 &&   // tolerancia $100
            new Date(f.fecha) <= new Date(mov.post_date)
        );
        if (candidatos.length === 1) {
            matches.push({ mov, factura: candidatos[0], confianza: 'alta' });
        } else if (candidatos.length > 1) {
            matches.push({ mov, candidatos, confianza: 'manual' });
        }
    });
    return matches;
}
```

---

## Agregar al menú lateral (sidebar.php)

En `app/Views/partials/sidebar.php`, dentro del grupo `$contabOpen`, agregar:

```php
$contabOpen = ['balance-diario', 'registro-gastos', 'pagos-mensuales', 'historial-balances', 'conciliacion'];
```

Y en el HTML del submenu de Contabilidad:
```html
<a class="sub-link<?= $activePage==='conciliacion' ? ' active' : '' ?>"
   href="<?= base_url('index.php/conciliacion') ?>">
    <i class="bi bi-bank2 me-2"></i>Conciliación bancaria
</a>
```

---

## Lo que NO hacer

- ❌ Nunca exponer `FINTOC_SECRET_KEY` en el HTML/JS del frontend
- ❌ No almacenar credenciales Santander — Fintoc las maneja
- ❌ No omitir `SSL_VERIFYPEER => true` en producción
- ❌ No hardcodear los API keys en el código — usar `.env`

---

## Testing con sandbox de Fintoc

Credenciales de prueba Santander Chile (empresa):
- **RUT empresa:** 76354771-K
- **RUT representante:** 11111111-1
- **Clave:** cualquiera

Ver más en: https://docs.fintoc.com/docs/data-aggregation-test-your-integration
