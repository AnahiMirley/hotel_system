# Sistema de Gestión Hotelera — PHP puro + MySQL (PDO) + Bootstrap 5

Proyecto generado a partir del diagrama Entidad-Relación **"HOTEL"** del PDF
proporcionado. Entidades: TIPO_HABITACION, HABITACION, CLIENTE,
RESERVA, SERVICIOS y GASTOS.

El backend es PHP puro con arquitectura MVC manual (sin frameworks PHP).
El único framework utilizado en el frontend es **Bootstrap 5** (CSS y JS,
vía CDN) junto con Bootstrap Icons; no se usa jQuery, React ni ninguna otra
librería de UI.

Este proyecto ha sido probado de extremo a extremo (servidor PHP embebido +
MariaDB) antes de la entrega: script SQL, conexión PDO, los 7 CRUD completos,
el panel de estadísticas y las reglas de negocio (formato de DNI/teléfono/
email, rangos de capacidad, estados válidos, solapamiento de fechas en
reservas, restricciones de borrado) funcionan correctamente.

## 1. Requisitos

- XAMPP (Apache + MySQL/MariaDB + PHP 8+)
- DBeaver (para administrar la base de datos)
- Conexión a internet en el navegador (Bootstrap y los íconos se cargan
  desde CDN; las imágenes ilustrativas también son externas)

## 2. Base de datos (DBeaver)

1. Abre DBeaver y conéctate a tu servidor MySQL de XAMPP (host `127.0.0.1`,
   puerto `3306`, usuario `root`, sin contraseña por defecto).
2. Abre el archivo `database.sql` incluido en este proyecto.
3. Ejecuta el script completo (crea la base `hotel_db`, las 7 tablas con sus
   llaves foráneas e índices, y datos de prueba).

   **Importante sobre acentos:** el script está en UTF-8. Si prefieres
   importarlo por línea de comandos en lugar de DBeaver, hazlo así para que
   las tildes no se guarden mal:
   ```
   mysql --default-character-set=utf8mb4 -u root -p < database.sql
   ```
   Desde DBeaver no necesitas hacer nada especial: su editor SQL ya usa
   UTF-8 por defecto.

## 3. Copiar el proyecto a XAMPP

1. Copia toda la carpeta `hotel_system/` dentro de `htdocs` de tu instalación
   de XAMPP, por ejemplo:
   - Windows: `C:\xampp\htdocs\hotel_system`
   - Linux: `/opt/lampp/htdocs/hotel_system`
2. Inicia los módulos **Apache** y **MySQL** desde el panel de control de
   XAMPP.

## 4. Configurar la conexión (si es necesario)

El archivo `config/conexion.php` ya está configurado para XAMPP por defecto
(`host=127.0.0.1`, `usuario=root`, `password=""`). Si tu instalación usa
otro usuario/contraseña, edita las constantes al inicio de esa clase.

## 5. Ejecutar la aplicación

Abre el navegador en:
```
http://localhost/hotel_system/index.php
```

Desde el menú lateral puedes gestionar: Hoteles, Tipos de Habitación,
Habitaciones, Clientes, Reservas, Servicios y Gastos. Cada módulo permite
crear, editar, eliminar, listar y buscar mediante formularios modales y
peticiones AJAX (fetch) al propio `index.php`, que actúa como enrutador
hacia los controladores.

## 6. Estructura del proyecto

```
hotel_system/
├── database.sql
├── index.php                 (enrutador: vistas + API)
├── config/
│   └── conexion.php           (PDO Singleton)
├── models/                    (una clase por entidad, SQL con PDO)
├── controllers/                (BaseController + 7 controladores)
├── views/<entidad>/index.html  (fragmento HTML: tabla + modal)
├── css/estilos.css
└── js/
    ├── comun.js                (fetch, notificaciones, modal)
    └── <entidad>.js             (lógica de cada módulo)
```

## 7. Reglas de negocio implementadas

- No se puede eliminar un TIPO_HABITACION en uso.
- No se puede eliminar una HABITACION con reservas asociadas.
- El DNI del CLIENTE es único; nombre y apellido solo aceptan letras y
  espacios; el teléfono y el email se validan por formato (servidor y
  formulario).
- La capacidad de un TIPO_HABITACION debe estar entre 1 y 20 personas.
- El estado de HABITACION y RESERVA se valida contra una lista fija de
  valores permitidos (no se aceptan valores arbitrarios).
- Una RESERVA valida que `fecha_salida > fecha_entrada`.
- Una RESERVA no puede solaparse con otra reserva activa de la misma
  habitación (se valida tanto en creación como en edición).
- Los precios y montos no pueden ser negativos (validado en PHP y reforzado
  con `CHECK` en la base de datos).

Todas las reglas se validan primero en el navegador (atributos HTML5 y la
clase `needs-validation` de Bootstrap) y luego se repiten en el servidor,
que es la fuente de verdad: ninguna regla depende únicamente del frontend.
