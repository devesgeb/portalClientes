# Análisis Arquitectónico: Unificación de Documentos (Cobrar y Pagar)

La propuesta de consolidar las tablas separadas (`tbl_documentos_cobrar` y `tbl_documentos_pagar`) en un esquema centralizado es **excelente y altamente escalable**. Este es el patrón de diseño ERP estándar de la industria, conocido como _Catálogo Central de Documentos_ o _Glosa Unificada_.

Al tener la información provista por Excel (ERP Facto), tener una única tabla receptora facilita el proceso de importación y evita duplicar la lógica de negocio (como rutinas de lectura de Excel, procesamiento de fechas, etc.).

A continuación, analizamos la propuesta y agregamos recomendaciones clave.

---

## 1. La Propuesta del Usuario (Evaluación)

### ✅ `tbl_docTributarios` (Concentrador unificado)
**Veredicto:** Muy acertado.
Tener facturas, boletas, notas de crédito tanto de ventas (clientes) como de compras (proveedores) en una sola tabla permite calcular saldos netos directamente en la BD de forma muy veloz sin hacer uniones complejas.

**Requisito indispensable:** Debes agregar un campo abstracto o usar una tabla intermedia para saber a quién pertenece el documento. Dado que un documento es de "Venta" (Cliente) o de "Compra" (Proveedor), este campo definirá la naturaleza contable del mismo.

### ✅ `tbl_estados` (Pagado / Impago / Parcial)
**Veredicto:** Aprobado condicional.
Tener una tabla de estados es útil si los estados son dinámicos o complejos. Sin embargo, en el mundo de cuentas por pagar/cobrar, la "Pagado/Impago" responde mejor a una fórmula matemática (Monto - Pagado = Saldo). El estado real es una consecuencia de eso. 

Aun así, es buena idea para categorizar visualmente si un documento pasó a cobranza judicial, fue anulado, o fue repactado.

### ✅ `tbl_tipoDocumentos` (Factura, Boleta, NC + Códigos SII)
**Veredicto:** Excelente.
Tener los códigos del SII (Ej: 33 = Factura Electrónica, 39 = Boleta Electrónica, 61 = Nota de Crédito) es vital para escalar, cruzar información directa del Registro de Compras y Ventas (RCV) del SII, y facilitar las conciliaciones.

---

## 2. Recomendaciones: ¿Qué le falta o qué se le debe agregar?

Para que este diseño sea robusto a prueba del futuro, sugiero agregar los siguientes conceptos a la tabla principal `tbl_docTributarios`:

### A. Campo `tipo_movimiento` (Ingreso/Egreso) o `naturaleza`
Si metes compras y ventas en la misma tabla, necesitas saber si ese documento suma o resta plata a tu favor.
Por ejemplo:
- `movimiento = 'VENTA'` (Cuentas por Cobrar)
- `movimiento = 'COMPRA'` (Cuentas por Pagar)

Con esto, sacar el balance diario es tan simple como sumar `Total Ventas - Total Compras` en un solo query.

### B. Llave Foránea hacia la Entidad (El Rutero)
¿A quién le asocias la factura? Como tenemos `tbl_clientes` y `tbl_proveedores` por separado, tienes dos opciones arquitectónicas:

*   **Opción A (Foráneas Separadas):** `cliente_id` y `proveedor_id` en `tbl_docTributarios` en donde uno siempre será `NULL` y el otro tendrá el ID de la empresa.
*   **Opción B (Entidad Única - RUT):** Mantener un campo `rut_entidad` en `tbl_docTributarios` que sirva como enlace lógico entre el documento y las tablas `tbl_clientes` / `tbl_proveedores`. Quien define la relación es el `tipo_movimiento`.

### C. Manejo del Signo Matemático según Tipo de Documento SII
Si tienes Facturas (Suma la deuda) y Notas de Crédito (Resta la deuda), la tabla `tbl_tipoDocumentos` no solo debe tener el código del SII, sino un multiplicador o factor de impacto.
*   Código 33 (Factura): `signo = 1`
*   Código 61 (Nota de Crédito): `signo = -1`

Al calcular la deuda total, multiplicas el total del documento por ese campo `signo`. Esto te evitará tener que hacer complejas condiciones lógicas ("si es NC, restar, sino sumar") al nivel del código PHP.

### D. ¿Y los pagos? (`tbl_pagos`)
Si en un futuro un cliente te paga 1 factura con 3 transferencias diferentes de abono parcial, necesitarás el detalle. A corto plazo, guardar `pagado` e `impago` en el documento sirve. Pero a largo plazo, la **escalabilidad real** requiere una tabla de movimientos de pagos que se asocie al documento, dejando que la tabla de documentos solo se almacene el "Total" inicial. *(Esto lo dejo a tu criterio según las necesidades y plazos del proyecto actual)*.

---

## 3. Propuesta Final de Esquema Relacional

```sql
-- Catálogos Maestros (De Soporte)
1. tbl_tipoDocumentos
   - id, codigo_sii (ej: 33), nombre, signo_contable (1 o -1), estado

2. tbl_estados
   - id, nombre (Pendiente, Pagado, Anulado, Castigado), color_badge

-- Tabla Universal (Factoring)
3. tbl_docTributarios
   - id
   - movimiento ('VENTA', 'COMPRA')        -- Clasificador Maestro
   - rut_entidad                           -- RUT de la empresa proveedora/cliente
   - tipo_doc_id (FK -> tbl_tipoDocumentos)
   - numero_documento
   - fecha_emision
   - fecha_vencimiento
   - subtotal, iva, total_general          -- Monto oficial del Excel
   - monto_pagado, saldo_impago            -- Campos dinámicos operacionales
   - estado_id (FK -> tbl_estados)
```

## Resumen Final
**Tu propuesta es totalmente acertada** y es el paso correcto para migrar de un MVP a un sistema de nivel transaccional contable. La integración con tipos SII te salvará la vida al hacer integraciones financieras, y agrupar comprar y ventas abarata y simplifica la estructura.
