# Análisis de Arquitectura: Usuarios vs Entidades (Clientes/Proveedores/Admin)

## Resumen Ejecutivo
Para este sistema financiero, la **mejor práctica** es mantener **separado el control de acceso (Login) de las entidades comerciales (Empresas)**. 

Esto significa usar **una sola tabla para todos los Usuarios** (`tbl_usuarios` con roles) y **tablas separadas para las entidades comerciales** (`tbl_clientes`, `tbl_proveedores`).

A continuación el análisis detallado de ambas opciones:

---

## Opción 1: Mezclar todo (Usuarios = Clientes/Proveedores)
*Intentar que `tbl_clientes` o `tbl_proveedores` también sirvan para hacer login agregando campos de email/password.*

### ❌ ¿Por qué NO se recomienda?
1. **Un cliente B2B no es una persona:** Un Cliente o Proveedor (ej. "Comercial XYZ S.A.") es una entidad comercial. Quien entra al sistema no es la empresa, sino un **empleado** de esa empresa (ej. Juan Pérez).
2. **Multi-usuarios por empresa:** ¿Qué pasa si el cliente "Constructor ABC" quiere que su contador y su gerente de compras tengan acceso al portal? Si mezclas la tabla, tendrías que registrar al cliente dos veces.
3. **Escalabilidad y Mantenimiento:** La tabla de clientes crecería con campos técnicos que no necesita (último_acceso, token_recuperación, password_hash) ensuciando los datos de facturación.

---

## Opción 2: Sistema Desacoplado (Recomendado ✅)
*Mantener la tabla centralizada de usuarios apoyada en roles, y vincular los usuarios a las entidades comerciales usando el RUT o un ID.*

### Estructura Ideal
- **`tbl_usuarios`** + **`tbl_perfiles`**: Maneja exclusivamente quién puede entrar al sistema (email, clave, perfil, último_acceso).
- **`tbl_clientes`**: Maneja exclusivamente datos comerciales y de facturación.
- **`tbl_proveedores`**: Maneja exclusivamente datos comerciales de proveedores.

### ✅ ¿Por qué SÍ se recomienda?
1. **Centralización de Seguridad:** Tienes un solo lugar para controlar accesos (`tbl_usuarios`). Si necesitas auditar quién entró o suspender a alguien, lo haces en un solo sitio.
2. **Flexibilidad 1 a Muchos:** 
   - Un Admin no necesita estar ni en la tabla Clientes ni en Proveedores.
   - Un Cliente comercial (`tbl_clientes`) puede tener 3 usuarios (`tbl_usuarios`) distintos ingresando al portal a ver sus deudas vinculándolos mediante un campo como `empresa_rut` dentro del usuario.
3. **Roles Claros:** Si mañana agregas el rol de "Auditor" o "Vendedor", solo agregas un perfil nuevo a `tbl_perfiles` sin tocar las tablas de facturación.
4. **Facilidad de Desarrollo:** Tu código de Login (Authentication) siempre atacará a una sola tabla (`tbl_usuarios`), sin tener que hacer _If es admin busca en tabla A, if es cliente busca en tabla B_.

---

## Conclusión Aplicada a tu Proyecto
La estructura que generamos en `estructura_usuarios.sql` junto con tus tablas actuales es el enfoque correcto. 

**¿Cómo interactúan?**
- **Administrador:** Se le crea registro en `tbl_usuarios` con `perfil_id = 1`. No necesita estar en ninguna otra tabla.
- **Cliente:** Se registra en `tbl_usuarios` con `perfil_id = 2`. Su campo `rut` en esta tabla debe coincidir con el `rut` de la `tbl_clientes`. Así, al iniciar sesión, el sistema solo le muestra las deudas que coincidan con su RUT.
- **Proveedor:** Se registra en `tbl_usuarios` con `perfil_id = 3`.

Mantener este paradigma te evitará enormes dolores de cabeza cuando el sistema deba escalar o auditarse.
