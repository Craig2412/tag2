# TAG API - Guía de Instalación y Configuración

Esta es la API central del proyecto TAG, construida con Laravel 12, Reverb (WebSockets) y Scribe para la documentación.

## 🚀 Pasos para la Instalación (Primera Vez)

Si acabas de clonar el proyecto, sigue estos pasos para tener todo funcionando perfectamente:

1. **Instalar Dependencias de PHP**
   ```powershell
   composer install
   ```

2. **Configurar el Entorno**
   Copia el archivo de ejemplo y genera la clave de la aplicación:
   ```powershell
   copy .env.example .env
   php artisan key:generate
   ```

3. **Preparar la Base de Datos (SQLite)**
   Si usas el valor por defecto (`DB_CONNECTION=sqlite`), crea el archivo de la base de datos:
   ```powershell
   # En Windows (PowerShell)
   New-Item -Path "database/database.sqlite" -ItemType "File"
   ```

4. **Ejecutar el Comando de Construcción Automático**
   Este comando automatiza el reset de la base de datos, carga los datos iniciales (seeds), genera un token de administrador y actualiza la documentación de la API:
   ```powershell
   php artisan app:build-docs
   ```

---

## 🛠️ Cómo Correr la API

Para que todo funcione (incluyendo los WebSockets en tiempo real), debes tener estas terminales abiertas:

- **Terminal 1 (API Server):**
  ```powershell
  php artisan serve
  ```
- **Terminal 2 (WebSockets/Reverb):**
  ```powershell
  php artisan reverb:start
  ```
- **Terminal 3 (Colas de procesos - Opcional pero recomendado):**
  ```powershell
  php artisan queue:listen
  ```

> [!TIP]
> Puedes usar el comando **`composer dev`** para levantar el servidor, las colas y los logs simultáneamente.

---

## 🔌 Configuración para el Cliente (Frontend)

Si vas a conectar un cliente (Next.js, React, Mobile), estos son los valores que debes configurar:

### 1. Conexión a WebSockets (Laravel Echo / Reverb)
Para que el cliente escuche eventos en tiempo real, usa estos valores del `.env`:

| Variable | Valor Sugerido (Local) |
| :--- | :--- |
| **Reverb App Key** | `your_reverb_key` |
| **Reverb Host** | `127.0.0.1` |
| **Reverb Port** | `8080` |
| **Reverb Scheme** | `http` |

### 2. Autenticación de la API
La API utiliza **Laravel Sanctum**. 
- El comando `app:build-docs` genera un token de administrador automáticamente.
- Puedes encontrar este token en la consola al terminar el comando o en tu archivo `.env` bajo la variable `SCRIBE_AUTH_TOKEN`.
- Úsalo en el header de tus peticiones: `Authorization: Bearer [TU_TOKEN]`.

### 3. Documentación Interactiva
Puedes ver todos los endpoints disponibles y probarlos directamente en:
👉 `http://localhost:8000/docs`

---


## 🧾 Facturación Fiscal (IVA + Retenciones)

La API incluye un módulo de facturación con cálculo de IVA y retenciones **configurables desde la base de datos** (tabla `conceptos_fiscales`).

### Endpoints
```
GET /api/ordenes-compra/{ordenCompra}/factura      → desglose de factura del cliente por servicio
GET /api/ordenes-compra/{ordenCompra}/retenciones  → retenciones de la empresa + total neto
```

### Conceptos por defecto
- **Cliente**: ISLR 2% (excepto "boleto"), Ret. IVA 75%, 1×1000 0.1%, Aporte Social 3%, FUVIDIT 0.5%, Alcaldía 1.25%.
- **Empresa**: Alcaldía 2.2%, ISLR 1%, INATUR 1%, Ret. IVA 25%.

Cada concepto define: a quién aplica (`cliente`/`empresa`), sobre qué base se calcula (`base_gravable`/`valor_iva`), el porcentaje y una exclusión por palabra clave. **No es necesario tocar código** para agregar o modificar impuestos.

> 📖 Documentación completa: `docs/SESION_2026-08-21_FACTURACION_PROVEEDORES.md`

---

## 👥 Usuarios y Departamentos

Se crean **19 usuarios** en 4 departamentos (Gerencia General, Comercial Mercadeo, Administración, Operaciones) vía `UsuariosDepartamentosSeeder`:

- Correo: `nombre@tag.com`
- Clave por defecto: `123456789`
- Privilegios mapeados a roles Spatie (`admin`/`personal`)

## 🏨 Proveedores (Aliados)

Se cargan **307 alianzas** desde `Maestro Alianzas tAG.xlsx` vía `ProveedoresAliadosSeeder`:

- Campos: nombre, ciudad, contacto, cargo, característica, teléfono, correo, comisión (numérica).
- `TipoProveedor` normalizado a 6 categorías (Alojamiento, Transporte, Aereo, Restaurante, Servicios, Otros).
- Vinculación automática a `TipoServicio` vía pivote.
- Comisión numérica `decimal(8,4)` — los valores "neta"/"convenio"/vacío se convierten a `0.0`.

---

## Integracion KIU Sandbox

Se agrego una capa de integracion para consumir el sandbox de KIU desde esta API Laravel. La configuracion vive en `config/services.php` bajo `services.kiu` y las credenciales/paths del sandbox se definen en `.env` con variables `KIU_*`.

Endpoints disponibles en la API:

- `POST /api/kiu/session`
- `POST /api/kiu/availability`
- `POST /api/kiu/pricing`
- `POST /api/kiu/booking`
- `POST /api/kiu/ticketing`
- `POST /api/kiu/post-sale`

Payload minimo esperado:

```json
{
	"payload": "<KIURequest>...</KIURequest>",
	"headers": {
		"X-Correlation-Id": "req-001"
	},
	"query": {
		"office": "CCS1"
	},
	"context": {
		"reservation_code": "ABC123"
	}
}
```

Notas importantes:

- Sin la URL sandbox real y las credenciales entregadas por KIU, la conexion no puede ejecutarse contra el proveedor.
- La capa creada actua como proxy seguro hacia KIU y deja separado el transporte HTTP del resto de la logica del proyecto.
- Si KIU exige SOAPAction, headers propios o rutas distintas por operacion, se ajustan con las variables `KIU_*_SOAP_ACTION` y `KIU_*_PATH`.
