# FinanTrack - Sistema de gestion de finanzas personales

Proyecto final de Programacion Web (PHP, MySQL, HTML, CSS y JavaScript) para XAMPP.

## Funciones
Registro e inicio de sesion, movimientos (ingresos y gastos) con filtros y paginacion,
categorias, presupuestos mensuales, objetivos de ahorro con aportaciones, y dashboard con una grafica de gastos.

## Instalacion en XAMPP
1. Inicia **Apache** y **MySQL** desde el panel de XAMPP.
2. Copia este proyecto a `C:\xampp\htdocs\` y **nombra la carpeta `FinanTrack`**
   (la ruta base esta definida en `includes/funciones.php` como `/FinanTrack/`).
3. En phpMyAdmin (`http://localhost/phpmyadmin`) importa `Base/finantrack.sql`.
   Crea la base `finantrack` y todas sus tablas.
4. La conexion usa `root` sin contraseña (valores por defecto de XAMPP). Si cambiaste
   los datos, edita `config/database.php`.
5. Abre `http://localhost/FinanTrack/` y crea una cuenta desde "Crear cuenta".
   Para comprobar la conexion abre `http://localhost/FinanTrack/prueba_conexion.php`.

## Estructura
- `config/` conexion a la base de datos
- `includes/` sesion, seguridad (CSRF), plantillas y funciones comunes
- `modules/` operaciones CRUD por modulo (create, read, update, delete, validar)
- `assets/` estilos y JavaScript
- `Base/finantrack.sql` esquema de la base de datos

## Nota
Bootstrap y Chart.js se cargan desde CDN (jsDelivr), por lo que se necesita Internet para ver estilos y graficas.
