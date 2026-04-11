# CLAUDE.md — Bialystok Brewing Co Production System

Guía de referencia para asistentes de IA que trabajen en este repositorio.
El sistema está escrito completamente en español; mantené ese idioma en todo el código, comentarios y mensajes al usuario.

---

## Stack tecnológico

| Capa        | Tecnología                                                    |
|-------------|---------------------------------------------------------------|
| Backend     | PHP 7.4+ (sin framework, página-a-página)                    |
| Base de datos | MySQL, acceso via PDO (código nuevo) / mysqli (código legacy) |
| Frontend    | HTML5, CSS3, JavaScript vanilla + jQuery 2.2.4               |
| Servidor    | Apache 2.4 con mod_rewrite (hosting compartido Ferozo)       |
| Auth        | PHP sessions con helpers propios en `auth.php`               |

No hay composer, npm, webpack ni ningún proceso de build. El PHP se ejecuta directamente desde los archivos.

---

## Estructura del repositorio

```
/
├── config.php              # Constantes de BD y entorno (NO subir credenciales reales)
├── conexion.php            # Singleton PDO; usar getPDO() para obtener la conexión
├── auth.php                # Helpers de seguridad: requireLogin(), requireRole(), e(), CSRF
├── menu.php                # Sidebar/navegación incluido en todas las páginas protegidas
├── info_user.php           # Bloque de info de usuario del header
├── login.php               # Página de login (acceso público)
├── logout.php              # Cierre de sesión
├── inicio.php              # Dashboard principal
│
├── lotes.php               # Listado de lotes (batches)
├── anadir_lote.php         # Formulario nuevo lote
├── guardar_lote.php        # Handler POST: crea/guarda lote
├── detalles_lote.php       # Vista detalle de un lote
├── editar_lote.php         # Formulario edición lote
├── guardar_edicion_lote.php # Handler POST: actualiza lote
├── anadir_detalles_lote.php # Agregar detalles/ingredientes a lote
│
├── recetas.php             # Listado de recetas/estilos
├── anadir_receta.php       # Formulario nueva receta
├── guardar_receta.php      # Handler POST: guarda receta
├── detalle_receta.php      # Vista detalle receta
├── guardar_estilo.php      # Handler POST: guarda estilo de cerveza
│
├── variedades_malta.php    # Catálogo de maltas
├── anadir_malta.php        # Formulario nueva malta
├── variedades_lupulo.php   # Catálogo de lúpulos
├── anadir_lupulo.php       # Formulario nuevo lúpulo
├── cepas_levadura.php      # Catálogo de levaduras
├── anadir_levadura.php     # Formulario nueva levadura
│
├── fermentadores.php       # Listado de fermentadores
├── actualizar_fermentador.php # Handler POST: actualiza fermentador
├── limpieza_fermentador.php   # Registro de limpiezas
│
├── reportes_h2o.php        # Listado de reportes de agua
├── anadir_reporte_h2o.php  # Formulario nuevo reporte de agua
├── guardar_reporteh2o.php  # Handler POST: guarda reporte agua
├── detalle_reporteh2o.php  # Vista detalle reporte agua
│
├── estadisticas.php        # Dashboard de estadísticas
├── comparar_fermentacion.php # Comparativa entre lotes
├── panel_sensorial.php     # Panel sensorial general
├── planilla_cata.php       # Planilla de cata individual
├── guardar_cata.php        # Handler POST: guarda cata
├── actualizar_cata.php     # Handler POST: actualiza cata
├── detalle_planilla_cata.php # Vista detalle planilla cata
├── eliminar_nota_cata.php  # Handler POST: elimina nota de cata
│
├── planificacion.php       # Vista de planificación de producción
├── planificacion_update.php # Handler POST: actualiza planificación
│
├── configuracion.php       # Panel de configuración (solo Admin)
├── config_update.php       # Handler POST: actualiza configuración
├── nuevo_usuario.php       # Formulario nuevo usuario (Admin)
├── nuevo_taster.php        # Formulario nuevo taster
├── guardar_usuario.php     # Handler POST: crea usuario
├── cambiar_contrasena.php  # Cambio de contraseña
│
├── eliminar_registro.php   # Handler POST: eliminación genérica
├── anadir_registro.php     # Handler POST: inserción genérica
│
├── css/                    # Hojas de estilo
│   ├── bialy-design-system.css  # Sistema de diseño (variables, componentes)
│   ├── bialy-inicio.css    # Estilos específicos del dashboard
│   └── ...
├── js/                     # JavaScript del cliente
│   └── jquery-2.2.4.min.js
├── img/                    # Imágenes y logotipos
├── files/                  # Copias de respaldo de archivos PHP
├── configuraciones/        # Scripts de configuración del servidor
└── .htaccess               # Reglas de reescritura de URL (base: /bialy/)
```

---

## Convenciones de naming

- **Páginas de vista**: `nombre_seccion.php` → `lotes.php`, `fermentadores.php`
- **Formularios de alta**: `anadir_*.php` → `anadir_lote.php`, `anadir_malta.php`
- **Handlers POST**: `guardar_*.php` o `actualizar_*.php` → `guardar_lote.php`
- **Vistas de detalle**: `detalle_*.php` o `detalles_*.php` → `detalle_receta.php`
- **Eliminación**: `eliminar_*.php` → `eliminar_nota_cata.php`
- **Variables PHP**: `snake_case` en español → `$numero_lote`, `$estilo_id`
- **Tablas MySQL**: `snake_case` en español → `lotes_cerveza`, `estilos_cerveza`
- **CSS classes**: `kebab-case` con prefijo `bialy-` para componentes custom → `.bialy-card`, `.bialy-btn`

---

## Routing / URLs

Las rutas se definen en `.htaccess` con `RewriteBase /bialy/`. Cada ruta limpia mapea a un archivo PHP:

```
/bialy/lotes           → lotes.php
/bialy/anadir_lote     → anadir_lote.php
/bialy/guardar_lote    → guardar_lote.php
/bialy/detalle_lote    → detalles_lote.php
```

Al agregar una nueva página, siempre registrar la ruta en `.htaccess`.

---

## Base de datos

### Conexión

Usar siempre `getPDO()` de `conexion.php`:

```php
require_once 'conexion.php';
$pdo = getPDO();
```

### Tablas principales

| Tabla                        | Descripción                                      |
|------------------------------|--------------------------------------------------|
| `users`                      | Usuarios del sistema (id, username, password, rol_id, nombre, apellido, mail, telefono) |
| `roles`                      | Roles: 1=Admin, 2=Elaborador, 3=Taster           |
| `lotes_cerveza`              | Lotes de producción (numero_lote, estilo_id, fermentador_id, OG/FG/ABV/IBU) |
| `estilos_cerveza`            | Estilos/recetas base (duracion_dias, parámetros objetivo) |
| `recetas_estilos`            | Parámetros objetivo por estilo (og, fg, ibu, abv, carb_level) |
| `lotes_maltas`               | Maltas utilizadas por lote                       |
| `lotes_lupulos`              | Lúpulos utilizados por lote                      |
| `lotes_levaduras`            | Levaduras utilizadas por lote                    |
| `variedades_malta`           | Catálogo de variedades de malta                  |
| `variedades_lupulo`          | Catálogo de variedades de lúpulo                 |
| `cepas_levadura`             | Catálogo de cepas de levadura                    |
| `fermentadores`              | Fermentadores con fechas de limpieza             |
| `reportesagua`               | Reportes de calidad del agua                     |
| `batches`                    | Registros de producción diaria                   |
| `alertas`                    | Recordatorios configurables                      |
| `configuraciones`            | Configuración de la app (ej: `creacion_usuarios`)|
| `tratamiento_agua_mash_sparge` | Parámetros de tratamiento de agua              |

### Reglas SQL obligatorias

**Siempre usar prepared statements con PDO.** Nunca interpolar variables directamente en SQL:

```php
// CORRECTO
$stmt = $pdo->prepare("SELECT * FROM lotes_cerveza WHERE id = :id");
$stmt->execute([':id' => $id]);

// INCORRECTO - SQL injection
$result = $pdo->query("SELECT * FROM lotes_cerveza WHERE id = $id");
```

---

## Autenticación y seguridad

### Incluir en toda página protegida

```php
require_once 'config.php';
require_once 'auth.php';
requireLogin();                // Redirige a login si no hay sesión activa
// requireRole([1, 2]);        // Opcional: restringir a roles específicos
```

### Roles de usuario

| ID | Nombre      | Permisos                                              |
|----|-------------|-------------------------------------------------------|
| 1  | Admin       | Acceso total, gestión de usuarios, configuración      |
| 2  | Elaborador  | Alta/edición de lotes, recetas, ingredientes, agua    |
| 3  | Taster      | Solo panel de cata (`planilla_cata`, `panel_sensorial`) |

### Funciones de seguridad en `auth.php`

```php
requireLogin()               // Verifica sesión activa o redirige
requireRole([1, 2])          // Verifica rol o redirige
isAdmin()                    // bool: es rol 1
isTaster()                   // bool: es rol 3

e($string)                   // Escapar output HTML (antiXSS) — SIEMPRE usar al mostrar datos
getIntParam('id')            // Obtener entero de $_GET (retorna null si inválido)
getIntParam('id', 'POST')    // Obtener entero de $_POST
getStringParam('nombre')     // Obtener string limpio de $_GET (max 500 chars)

getCsrfToken()               // Obtener/generar token CSRF
csrfField()                  // Retorna <input type="hidden"> con token CSRF
verifyCsrf()                 // Verifica token en $_POST o header; muere con 403 si falla
```

### CSRF: todo formulario POST debe incluir

```html
<form method="POST" action="guardar_lote">
    <?= csrfField() ?>
    <!-- campos del form -->
</form>
```

Y el handler debe verificarlo:

```php
require_once 'auth.php';
requireLogin();
verifyCsrf();
```

---

## Plantilla estándar para páginas nuevas

### Página de vista protegida

```php
<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'conexion.php';
requireLogin();
// requireRole([1, 2]); // si aplica

$pdo = getPDO();
// ... lógica de consultas ...
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Título — Bialystok Brewing Co</title>
    <link rel="stylesheet" href="css/bialy-design-system.css">
    <!-- css adicional si corresponde -->
</head>
<body>
<?php require_once 'menu.php'; ?>
<main class="bialy-main">
    <!-- contenido -->
</main>
</body>
</html>
```

### Handler POST

```php
<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'conexion.php';
requireLogin();
verifyCsrf();

$pdo = getPDO();
// Validar inputs con getIntParam() / getStringParam()
// Ejecutar query con prepared statement
// Redirigir
header('Location: destino');
exit;
```

---

## Sistema de diseño CSS

El archivo `css/bialy-design-system.css` define:

- **Tema oscuro por defecto** con toggle a claro (`.light-theme` en `<body>`)
- **Variables CSS** de color (tonos marrones cálidos, acento verde), tipografía DM Sans, espaciados
- **Componentes reutilizables**: `.bialy-card`, `.bialy-btn`, `.bialy-btn-primary`, `.bialy-table`, `.bialy-form-group`, `.bialy-badge`
- **Layout**: sidebar fijo + área de contenido principal

Usar siempre clases del design system antes de escribir CSS nuevo.

---

## Configuración del entorno

`config.php` define las constantes de entorno. En producción este archivo **no debe subirse al repositorio** con credenciales reales (la contraseña está como placeholder `TU_PASSWORD_AQUI`).

```php
define('DB_HOST',     'localhost');
define('DB_USER',     'c2651024_fabrica');
define('DB_PASS',     'TU_PASSWORD_AQUI');  // reemplazar localmente
define('DB_NAME',     'c2651024_fabrica');
define('DB_CHARSET',  'utf8mb4');
define('SESSION_LIFETIME', 3600);           // segundos de inactividad
define('SESSION_NAME',     'bco_session');
define('APP_ENV',   'production');          // 'development' | 'production'
define('APP_DEBUG', false);
```

`.user.ini` configura PHP a nivel de directorio:
- `memory_limit = 512M`
- `post_max_size = 128M`
- `upload_max_filesize = 128M`
- `date.timezone = America/Argentina/Buenos_Aires`
- `max_execution_time = 30`

---

## Código legacy vs. código moderno

El repositorio tiene **dos capas de código**:

### Moderno (PDO + auth.php) — USAR ESTE PATRÓN
- Usa `getPDO()` de `conexion.php`
- Prepared statements con parámetros nombrados (`:id`)
- Inputs validados con `getIntParam()` / `getStringParam()`
- Outputs escapados con `e()`
- CSRF con `verifyCsrf()` / `csrfField()`
- Auth con `requireLogin()` / `requireRole()`

### Legacy (mysqli) — NO REPLICAR
- Usa `$conn` con `mysqli_connect()`
- SQL con interpolación directa de variables
- Sin validación de inputs ni escape de outputs
- Sin CSRF

Al modificar archivos legacy, migrar el fragmento modificado al patrón moderno. No mezclar PDO y mysqli en el mismo archivo.

---

## Flujo de trabajo de desarrollo

1. **Sin build step**: editar archivos PHP directamente; los cambios son inmediatos en el servidor
2. **Sin tests automatizados**: verificar funcionalidad manualmente en el navegador
3. **Errores PHP**: revisar `error-php.log` en la raíz del proyecto
4. **Logging**: usar `error_log("mensaje")` para debug; no `var_dump()` en producción

### Ramas git

- `main` — producción estable
- `claude/<descripcion>` — ramas de trabajo de AI (ej: `claude/add-claude-documentation-cmIsk`)

### Al agregar una nueva funcionalidad

1. Crear la página de vista (`nueva_seccion.php`)
2. Crear el handler POST si hay formulario (`guardar_nueva_seccion.php`)
3. Registrar las rutas en `.htaccess`
4. Agregar el link al menú en `menu.php` si corresponde
5. Usar el patrón moderno (PDO + auth.php) en todo el código nuevo

---

## Errores conocidos

Los siguientes warnings aparecen en `error-php.log` y están pendientes de resolución:

- `inicio.php:259` — Variable indefinida (deprecation warning)
- `inicio.php:184-191` — `strtotime()` recibe `null` en lugar de string (PHP 8.1+ strict)

Al tocar `inicio.php`, corregir estos warnings como parte del cambio.

---

## Checklist de seguridad para código nuevo

- [ ] `require_once 'auth.php'` al inicio de toda página protegida
- [ ] `requireLogin()` llamado antes de cualquier lógica
- [ ] Todo output de datos de DB o usuario pasa por `e()`
- [ ] Todo input numérico de URL/form usa `getIntParam()`
- [ ] Todo input string de URL/form usa `getStringParam()`
- [ ] Todo formulario POST incluye `<?= csrfField() ?>`
- [ ] Todo handler POST llama `verifyCsrf()`
- [ ] Toda consulta SQL usa prepared statements con PDO
- [ ] No hay credenciales hardcodeadas en archivos rastreados por git
