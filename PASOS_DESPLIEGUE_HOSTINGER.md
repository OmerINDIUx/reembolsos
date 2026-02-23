# Guía de Despliegue en Hostinger (Laravel)

Esta guía te ayudará a subir tu sistema de reembolsos a un servidor de Hostinger paso a paso, diseñado para que funcione de manera segura.

---

## 🏗️ Fase 1: Preparación Local (Lo que haces en tu computadora)

1. **Compilar el Diseño (Ya lo hice por ti):**
    - Acabo de ejecutar el comando `npm run build` en tu computadora para "empaquetar" todos los colores, estilos e imágenes para que pesen poco y carguen rápido en producción.
2. **Crear el Archivo Comprimido (ZIP):**
    - Ve a la carpeta de tu proyecto local (`c:\laragon\www\reembolsos`).
    - Selecciona **todos** los archivos y carpetas, EXCEPTO:
        - ❌ `node_modules` (esta carpeta es solo de desarrollo y pesa muchísimo).
        - ❌ `tests` (opcional).
    - **Es importante incluir las carpetas ocultas** como `.env` y el archivo `.gitattributes`.
    - Haz clic derecho y comprime todo en un archivo `.zip` (por ejemplo: `reembolsos_app.zip`).

---

## 🌐 Fase 2: Preparación en Hostinger (hPanel)

1. **Crear la Base de Datos:**
    - Inicia sesión en **hPanel** de Hostinger.
    - Ve a **Bases de Datos** > **Gestión de Bases de Datos**.
    - Crea una nueva Base de Datos, con Usuario y Contraseña.
    - 📌 **Guarda bien estos tres datos** (Nombre de BD, Usuario, Contraseña) los usaremos enseguida.

---

## 🚀 Fase 3: Subida del Proyecto y Configuración

1. **Subir los Archivos:**
    - Ve a la sección **Archivos** > **Administrador de Archivos** en hPanel.
    - Navega dentro de la carpeta principal de tu dominio (generalmente `public_html` si es el dominio principal, o una subcarpeta si es un subdominio).
    - **Sube** el archivo `reembolsos_app.zip` que creaste en el paso 1.
    - Haz clic derecho sobre el `.zip` y selecciona **Extraer** (Extract).
    - _Importante: Asegúrate de que los archivos principales como `app`, `public`, `routes`, etc., queden directamente en la raíz de tu dominio o carpeta destinada, y no anidados dentro de otra carpeta llamada "reembolsos"._
    - Una vez extraído, puedes borrar el archivo `.zip`.

2. **Ajustar el Archivo `.env` (Configuración):**
    - En ese mismo administrador de archivos, busca el archivo `.env` (si no lo ves, asegúrate de activar la vista de archivos ocultos en la configuración). Ábrelo para editarlo.
    - Cambia las siguientes variables para que coincidan con la base de datos que creaste en el paso 2:

        ```env
        APP_ENV=production
        APP_DEBUG=false
        APP_URL=https://xn--diseoygestion-lkb.com/

        DB_CONNECTION=mysql
        DB_HOST=127.0.0.1
        DB_PORT=3306
        DB_DATABASE=ReembolsosGI
        DB_USERNAME=ReembolsosOmer
        DB_PASSWORD=Q7zNR|g&
        ```

    - Guarda los cambios.

---

## 🚪 Fase 4: Solucionar la Ruta de "Public" (El paso clave)

Laravel por seguridad "esconde" su código central y solo expone la carpeta `public/`. Sin embargo, Hostinger carga por defecto la carpeta `public_html/`. Para solucionarlo, haz lo siguiente:

1. **Crear regla de redirección (`.htaccess` en `public_html`):**
    - En tu **Administrador de Archivos**, directamente en la carpeta raíz (`public_html` o donde hayas extraído todo), vas a crear un NUEVO archivo llamado `.htaccess`.
    - Abre este nuevo `.htaccess` y pega el siguiente código para redirigir silenciosamente todo el tráfico hacia la carpeta `public`:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

- Guarda el archivo. Esto hará que al entrar a `tudominio.com` el servidor lea directamente lo que hay dentro de `public`.

---

## 💻 Fase 5: Levantar la Base de Datos (Comandos de Laravel)

Sigue los siguientes pasos en la consola en la que te acabas de conectar (donde viste la carpeta `domains`):

1. **Navegar a tu Proyecto:**
   Primero debes entrar a tu dominio y luego a la carpeta pública donde subiste los archivos.
    - Escribe: `cd domains` y presiona Enter.
    - Escribe: `ls` y presiona Enter (verás el listado de tu dominio, en este caso `xn--diseoygestion-lkb.com`).
      https://reembolsosindi.com/
    - Escribe: `cd xn--diseoygestion-lkb.com` y presiona Enter.
    - Escribe: `cd public_html` y presiona Enter.
    - _(Para verificar que estás en el lugar correcto, escribe `ls` de nuevo y deberías ver todas las carpetas que subiste: `app`, `public`, `routes`, el archivo `artisan`, etc.)_

2. **Migrar la Base de Datos (Generar Tablas):**
   Una vez dentro de `public_html`, ejecuta:
   `php artisan migrate --force`
   _(Nota: Si quieres crear el usuario administrador inicial, en su lugar corre `php artisan migrate:fresh --seed --force`)._
3. **Vincular Archivos Públicos:**
   Para que se vean las imágenes y PDF correctamente, ejecuta:
   `/opt/alt/php84/usr/bin/php artisan storage:link`
4. **Limpiar Caché:**
   Ejecuta esto para aplicar todas las configuraciones nuevas del `.env`:
   `/opt/alt/php84/usr/bin/php artisan config:clear`
   `/opt/alt/php84/usr/bin/php artisan cache:clear`
   `/opt/alt/php84/usr/bin/php artisan view:clear`

---

## 🎉 Cierre

Tu sistema de Reembolsos debería estar completamente operativo si visitas tu dominio. ¡Cualquier carga de Facturas PDF, notificaciones y reglas de validación funcionarán como en tu ordenador local!
