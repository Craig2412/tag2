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
