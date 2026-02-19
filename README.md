# Sistema de Reembolsos

Este sistema permite cargar archivos XML (CFDI) y PDF para gestionar reembolsos.
El sistema lee automáticamente los datos del XML (Emisor, Receptor, Totales, UUID) y valida opcionalmente si el PDF contiene el UUID o Total para congruencia.

## Requisitos

- Laragon (PHP 8.2+, MySQL, Nginx/Apache)
- Composer

## Instalación

1. **Base de Datos**:
   Asegúrate de que Laragon esté ejecutando MySQL (puerto 3306).
   Abre la terminal en Laragon y ejecuta:

    ```bash
    mysql -u root -e "CREATE DATABASE IF NOT EXISTS reembolsos;"
    ```

    (O crea la base de datos `reembolsos` manualmente usando HeidiSQL o PhpMyAdmin).

2. **Configuración**:
   Edita el archivo `.env` y asegúrate de tener la configuración correcta para MySQL:

    ```env
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=reembolsos
    DB_USERNAME=root
    DB_PASSWORD=
    ```

3. **Instalación de Dependencias**:

    ```bash
    composer install
    ```

4. **Migraciones**:
   Ejecuta las migraciones para crear las tablas:

    ```bash
    php artisan migrate
    ```

5. **Ejecución**:
   Si usas Laragon, el sitio debería ser accesible en `http://reembolsos.test` (si la carpeta se llama `reembolsos`).
   O puedes iniciar el servidor de desarrollo:
    ```bash
    php artisan serve
    ```

## Funcionalidades

- **Subir Solicitud**: Carga XML y PDF. El XML se procesa automáticamente.
- **Listado**: Vista de todos los reembolsos con estatus (Pendiente, Aprobado, Rechazado).
- **Validación**: Al subir el PDF, el sistema intenta leer el texto para verificar si coincide el UUID con el XML.
- **Admin**: Botones para Aprobar o Rechazar reembolsos.
