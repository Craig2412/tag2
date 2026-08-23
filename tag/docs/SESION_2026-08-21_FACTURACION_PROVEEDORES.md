# 📘 Documentación de Cambios — Sesión 2026-08-20 / 2026-08-21

> **Proyecto:** TAG2 API (Laravel 12 · PHP 8.2 · MariaDB · Sanctum · Spatie · Reverb)
> **Alcance:** Usuarios y departamentos, facturación fiscal (IVA + retenciones) y carga de proveedores (aliados).

---

## Resumen ejecutivo

En esta sesión se implementaron 3 bloques de funcionalidad:

1. **Usuarios y departamentos** (`UsuariosDepartamentosSeeder`) — 19 usuarios organizados en 4 departamentos.
2. **Facturación fiscal** — módulo de IVA + retenciones configurables por BD, con dos endpoints sobre órdenes de compra.
3. **Proveedores (aliados)** — carga de 307 alianzas desde excel, con comisión numérica.

---

## 1️⃣ Usuarios y departamentos

### Objetivo
Crear los usuarios de los 4 departamentos (Gerencia General, Comercial Mercadeo, Administración, Operaciones) con correo `nombre@tag.com` y clave por defecto `123456789`.

### Archivos creados / modificados

| Archivo | Tipo | Descripción |
|---------|------|-------------|
| `database/migrations/2026_08_20_000001_add_departamento_to_personal_table.php` | Migración | Agrega `departamento` y `cargo` a la tabla `personal` |
| `app/Models/Personal.php` | Modelo | Agrega `departamento` y `cargo` a `$fillable` |
| `database/seeders/UsuariosDepartamentosSeeder.php` | Seeder | Crea 19 usuarios + fichas de `Personal` |
| `database/seeders/DatabaseSeeder.php` | Seeder | Registra el seeder nuevo (después de `PersonalSeeder`) |

### Cómo funciona el seeder `UsuariosDepartamentosSeeder`

1. Define la lista de 19 usuarios con: nombre completo, correo, departamento, cargo y "privilegio".
2. **Mapea los "privilegios" a roles Spatie existentes** mediante `MAPA_PRIVILEGIO_ROLE`:
   - `ADMIN` → rol `admin`
   - `COMERCIAL`, `MERCADEO`, `ADMINISTRATIVO`, `FACTURADOR`, `ASESOR` → rol `personal`
   - `EMISOR RETENCIONES` → **no mapea** (se ignora y se reporta).
3. Crea el `Usuario` con `firstOrCreate` por `correo` (clave hasheada `123456789`).
4. Crea la ficha `Personal` con `firstOrCreate` por `usuario_id`, incluyendo `departamento` y `cargo`.
5. **Al final reporta los privilegios ignorados** (en este caso solo `EMISOR RETENCIONES` → Luis Diaz).

### Notas
- **Se usó un seeder, no una migración**, porque la creación de usuarios depende de que los roles existan (creados en `RoleSeeder`). Las migraciones corren antes que los seeders, lo que rompería `syncRoles(['admin'])` si los usuarios se insertaran en una migración.
- La división `nombre`/`apellido` se hace con `separarNombre()` (primer token = nombre, resto = apellido).

---

## 2️⃣ Facturación fiscal (IVA + retenciones)

### Objetivo
Generar una factura fiscal por orden de compra con desglose por servicio (base gravable, exento, IVA, retenciones) y calcular las retenciones que aplica la empresa, todo **configurable desde la base de datos**.

### Reglas de negocio

#### A. Retenciones que aplica el CLIENTE (factura)
Calculadas **por servicio**:

| Concepto | Base de cálculo | % por defecto | Excepción |
|----------|-----------------|---------------|-----------|
| ISLR | base gravable | 2% | 0 si el servicio contiene "boleto" |
| Retención IVA | valor del IVA | 75% | — |
| 1 x 1000 | base gravable | 0.1% | — |
| Aporte Social | base gravable | 3% | — |
| FUVIDIT | base gravable | 0.5% | — |
| Alcaldía | base gravable | 1.25% | — |

`total_a_pagar_cliente = total_facturado − Σ(retenciones cliente)`

#### B. Retenciones que aplica la EMPRESA (sobre la OC)

| Concepto | Base de cálculo | % por defecto |
|----------|-----------------|---------------|
| Alcaldía | base gravable | 2.2% |
| ISLR | base gravable | 1% |
| INATUR | base gravable | 1% |
| IVA | valor del IVA | 25% |

`total_neto_empresa = total_facturado − Σ(ret. cliente) − Σ(ret. empresa)`

### Cálculo de IVA por servicio
- `base` = `monto_gravable`
- `exento` = `monto_no_sujeto` (no lleva impuestos)
- `iva_valor` = `base × (iva_establecido / 100)`
- `total_facturado` = `base + iva_valor + exento`

### Archivos creados / modificados

| Archivo | Tipo | Descripción |
|---------|------|-------------|
| `database/migrations/2026_08_20_000002_create_conceptos_fiscales_table.php` | Migración | Tabla `conceptos_fiscales` (impuestos/retenciones configurables) |
| `app/Models/ConceptoFiscal.php` | Modelo | Modelo + scope `activos()` |
| `database/seeders/ConceptosFiscalesSeeder.php` | Seeder | Siembra los 10 conceptos fiscales |
| `app/Services/FacturaService.php` | Servicio | Lógica de cálculo (factura + retenciones empresa) |
| `app/Http/Controllers/FacturaController.php` | Controlador | Endpoints `factura` y `retenciones` |
| `routes/api.php` | Ruta | Registra las 2 rutas nuevas |
| `database/seeders/DatabaseSeeder.php` | Seeder | Registra `ConceptosFiscalesSeeder` |
| `database/seeders/TipoServicioSeeder.php` | Seeder | "Vuelo" → "Boleto" |

### Diseño de la tabla `conceptos_fiscales` (totalmente configurable)

```php
- codigo              # islr_cliente, alcaldia_empresa, ...
- nombre              # "ISLR", "Alcaldía", "1 x 1000"
- tipo_aplicacion     # retencion | impuesto
- aplica_a            # cliente | empresa   ← a QUÉN se aplica
- base_calculo        # base_gravable | valor_iva  ← SOBRE QUÉ se calcula
- porcentaje          # decimal(8,4)
- excluir_si_contiene # "boleto" → si el servicio lo contiene, no aplica
- activo              # boolean
- orden               # orden del desglose
```

Esto permite **agregar/editar/eliminar impuestos sin tocar código**: basta un registro en esta tabla.

### Conceptos sembrados (`ConceptosFiscalesSeeder`)

| Codigo | Nombre | Aplica a | Base | % | Excluye "boleto" |
|--------|--------|----------|------|---|------------------|
| `islr_cliente` | ISLR | cliente | base_gravable | 2.0 | ✅ |
| `retencion_iva_cliente` | Retención IVA | cliente | valor_iva | 75.0 | ❌ |
| `unoxmil_cliente` | 1 x 1000 | cliente | base_gravable | 0.1 | ❌ |
| `aporte_social_cliente` | Aporte Social | cliente | base_gravable | 3.0 | ❌ |
| `fuvidit_cliente` | FUVIDIT | cliente | base_gravable | 0.5 | ❌ |
| `alcaldia_cliente` | Alcaldía | cliente | base_gravable | 1.25 | ❌ |
| `alcaldia_empresa` | Alcaldía | empresa | base_gravable | 2.2 | ❌ |
| `islr_empresa` | ISLR | empresa | base_gravable | 1.0 | ❌ |
| `inatur_empresa` | INATUR | empresa | base_gravable | 1.0 | ❌ |
| `retencion_iva_empresa` | IVA | empresa | valor_iva | 25.0 | ❌ |

### Endpoints creados

```
GET  /api/ordenes-compra/{ordenCompra}/factura           → previsualiza factura fiscal (cliente)
GET  /api/ordenes-compra/{ordenCompra}/retenciones       → previsualiza retenciones empresa
POST /api/ordenes-compra/{ordenCompra}/factura/emitir    → emite (persiste) la factura
GET  /api/facturas                                       → listado de todas las facturas emitidas
GET  /api/facturas/{ordenCompra}                         → factura(s) de una orden de compra
```

Todos bajo `auth:sanctum`. El parámetro `{ordenCompra}` en `/api/facturas/{ordenCompra}` resuelve por binding implícito al modelo `OrdenCompra`.

### Cómo funciona `FacturaService`

- **`generarFactura($orden)`**: recorre los servicios de la cotización de la OC, calcula por servicio `base`, `exento`, `iva_valor`, `total_facturado`, aplica los conceptos de `cliente` y devuelve el desglose + `total_a_pagar_cliente`.
- **`calcularRetencionesEmpresa($orden)`**: aplica los conceptos de `empresa` por servicio y devuelve desglose + `total_neto_empresa`.
- **`calcularMontoConcepto()`**: aplica la exclusión por palabra clave y elige la base (`base_gravable` o `valor_iva`) según la configuración del concepto.
- Redondeo a 2 decimales en todos los montos.

### Persistencia de facturas (emitir)

A partir de la versión se agregó la **persistencia** de facturas. Antes el cálculo era "al vuelo" (no se guardaba nada). Ahora:
- La factura se emite **automáticamente** al crearse la Orden de Compra (en `GenerarOrdenDesdeCotizacionListener`).
- También se puede emitir manualmente con `POST /factura/emitir`.

#### Tablas creadas (`2026_08_21_000003_create_facturas_table`)

| Tabla | Descripción |
|-------|-------------|
| `facturas` | Cabecera: número, OC, cliente, emisor (RIF/razón social), timbrado, totales congelados, correlativo por año |
| `factura_detalles` | Desglose por servicio (base, exento, IVA, total), montos congelados |
| `factura_retenciones` | Cada retención aplicada (cliente y empresa), con su % congelado |

#### Numeración secuencial
`numero_factura` = `A-{correlativo 8 dígitos}`, reinicia por año (`anio` + `correlativo`).

#### Campos fiscales del emisor
`emisor_rif` y `emisor_razon_social` se toman de la primera `Empresa` registrada (`tabla empresas`), junto con `timbrado` (por defecto null, editable luego).

#### Idempotencia
`emitir()` es idempotente: si ya existe una factura para la OC, devuelve la existente (no duplica). Usa una transacción de BD.

---

## 3️⃣ Proveedores (aliados)

### Objetivo
Cargar los **307 aliados** del archivo `Maestro Alianzas tAG.xlsx` a la tabla `proveedores`.

### Archivos creados / modificados

| Archivo | Tipo | Descripción |
|---------|------|-------------|
| `database/migrations/2026_08_21_000001_update_proveedores_table.php` | Migración | Agrega `ciudad`, `cargo_contacto`, `caracteristica`, `comision_tag`; quita `unique` de `correo_empresa` |
| `database/migrations/2026_08_21_000002_make_comision_tag_numeric.php` | Migración | Convierte `comision_tag` de string a `decimal(8,4)` |
| `app/Models/Proveedor.php` | Modelo | Agrega campos a `$fillable` + cast `comision_tag => float` |
| `database/seeders/ProveedoresAliadosSeeder.php` | Seeder | 307 aliados embebidos + mapeo de tipos + pivote |
| `database/seeders/DatabaseSeeder.php` | Seeder | Registra el seeder (después de `ProveedoresSeeder`) |

### Reglas de mapeo

#### Mapeo de columnas del Excel → tabla `proveedores`

| Columna Excel | Campo BD | Regla |
|---------------|----------|-------|
| Hotel | `nombre_empresa` / `razon_comercial` | tal cual (trim) |
| Ciudad | `ciudad` | tal cual (trim) |
| — | `rif` | placeholder único `PENDIENTE-<nro>-<sec>` (o `PENDIENTE-SN-<sec>` si no hay Nro) |
| Correo Electrónico | `correo_empresa` | minúsculas; `sin-correo-<n>@tag.com` si falta |
| Numero | `telefono_empresa` | tal cual |
| Contacto | `nombre_persona_contacto` | "Sin contacto" si vacío |
| Cargo | `cargo_contacto` | tal cual |
| Característica | `caracteristica` | tal cual |
| Comision tAG | `comision_tag` | **numérico** (ver abajo) |
| Producto | `tipo_proveedor` | normalizado a 6 categorías |

#### Mapeo "Producto" → `TipoProveedor` (categorías grandes)

| Producto | TipoProveedor |
|----------|---------------|
| Hotel, Posada, Campamento | Alojamiento |
| Traslados, Ferry, Crucero, Jeep Safari, etc. | Transporte |
| Línea Aérea, Consolidador | Aereo |
| Restaurante | Restaurante |
| Arrendadora, Asistencia al pasajero, Operador Turístico, etc. | Servicios |
| vacío / sucios | Otros |

#### Mapeo "Producto" → `TipoServicio` (pivote)

| Producto | TipoServicio |
|----------|--------------|
| Hotel, Posada, Campamento | Alojamiento |
| Traslados, Ferry | Traslado |
| Línea Aérea, Consolidador, Crucero | Boleto |
| Restaurante | Alimentación |
| otros | Consultoría |

#### Normalización de comisión a número

| Valor en Excel | Resultado numérico |
|----------------|--------------------|
| `0.05` … `0.4` | el mismo número |
| `neta` / `netas` / `NETA` / `NETAS` / `Neta` / `Netas` / `Solo Tarifas Netas` | `0.0` |
| `10% Descuento`, `convenio` | `0.0` |
| vacío | `0.0` |

### Cómo funciona el seeder `ProveedoresAliadosSeeder`

1. Crea/obtiene los `TipoProveedor` (6 categorías) y los `TipoServicio` (5) con `firstOrCreate`.
2. Itera el array embebido de 307 proveedores.
3. Para cada uno, `updateOrCreate` por `rif` (único garantizado).
4. Vincula el proveedor a su `TipoServicio` vía `syncWithoutDetaching`.
5. Reporta el total cargado.

### Resultado final

| Métrica | Valor |
|---------|-------|
| Aliados insertados | 307 |
| Proveedores totales | 308 (307 + "Servicios Delta" original) |
| Correos placeholder | 79 |
| Vinculados a TipoServicio | todos |
| Comisión numérica | `decimal(8,4)`, distribución: 0.0→176, 0.1→40, 0.2→45, 0.3→15, 0.15→15, resto menores |

---

## 🚦 Orden de ejecución de migraciones/seeders

Las migraciones corren en orden de timestamp y los seeders en el orden definido en `DatabaseSeeder`:

### Migraciones nuevas (esta sesión)

```
2026_08_20_000001_add_departamento_to_personal_table.php        → personal.departamento, personal.cargo
2026_08_20_000002_create_conceptos_fiscales_table.php            → conceptos_fiscales
2026_08_21_000001_update_proveedores_table.php                   → proveedores: ciudad, cargo_contacto, caracteristica, comision_tag + quita unique correo
2026_08_21_000002_make_comision_tag_numeric.php                  → comision_tag → decimal(8,4)
```

### Seeders nuevos (esta sesión) — orden en `DatabaseSeeder`

```
UsuariosDepartamentosSeeder   (después de PersonalSeeder)
ConceptosFiscalesSeeder       (al final)
ProveedoresAliadosSeeder      (después de ProveedoresSeeder)
```

---

## ⚠️ Decisiones y notas técnicas

1. **Seeder vs Migración para datos**: los usuarios y proveedores se cargan vía **seeders** (no migraciones) porque dependen de catálogos/roles creados en otros seeders. Las migraciones solo deben cambiar el **esquema**.

2. **Excepción "boleto" en ISLR**: se evalúa contra `tipoServicio.tipo_servicio`. Por eso se renombró el tipo "Vuelo" → "Boleto" (tanto en BD como en `TipoServicioSeeder`).

3. **MariaDB vs MySQL**: el servidor resultó ser **MariaDB**, donde el `->change()` de Laravel falla al convertir VARCHAR→DECIMAL con datos de texto. Se resolvió con `DB::statement` (UPDATE de normalización + `ALTER TABLE ... MODIFY`).

4. **RIF placeholder único**: el `Nro` del Excel tenía duplicados/vacíos, provocando colisiones de RIF (21 proveedores se perdían). Se corrigió haciendo el RIF siempre único: `PENDIENTE-<nro>-<sec>`.

5. **Correos duplicados**: se quitó el `unique` de `correo_empresa` para permitir insertar todas las sedes de una misma cadena (Eurobuilding ×8, Sunsol ×4, etc.).

6. **Datos sucios conservados**: correos como `mailto:...` o `www...` se conservan tal cual (solo trim/minúsculas). No se normalizaron por no haber sido solicitado.

---

## 🧪 Cómo probar

### Facturación
```powershell
# Requiere Reverb corriendo (para los broadcasts) o BROADCAST_CONNECTION=log
php artisan reverb:start
php artisan serve

# Con token del .env (SCRIBE_AUTH_TOKEN)
GET http://localhost:8000/api/ordenes-compra/1/factura
GET http://localhost:8000/api/ordenes-compra/1/retenciones
```

### Seeders
```powershell
php artisan db:seed --class=UsuariosDepartamentosSeeder
php artisan db:seed --class=ConceptosFiscalesSeeder
php artisan db:seed --class=ProveedoresAliadosSeeder
```

### Flujo completo
```powershell
php artisan app:build-docs   # migrate:fresh --seed + token admin + docs
```
