# Guía de Despliegue Final en Hostinger (PHP 8.4)

Esta guía contiene los pasos finales para subir la versión actualizada del sistema de reembolsos a Hostinger.

---

## 🏗️ Fase 1: Preparación Local

1. **Compilar el Diseño (RECIÉN COMPLETADO):**
    - Acabo de ejecutar `npm run build`. Todos los nuevos cambios visuales (Bitácora de Observaciones, tabla de usuarios mejorada, etc.) ya están empaquetados en la carpeta `public/build`.
2. **Crear el Archivo Comprimido (ZIP):**
    - Ve a `c:\laragon\www\reembolsos`.
    - Selecciona **todos** los archivos y carpetas, EXCEPTO:
        - ❌ `node_modules`
        - ❌ `tests`
        - ❌ `.git`
    - **Asegúrate de incluir el archivo `.env`**.
    - Comprime todo en un archivo llamado `reembolsos_final.zip`.

---

## 🌐 Fase 2: Configuración en Hostinger (hPanel)

1. **Base de Datos:**
    - Asegúrate de tener creados el nombre de la BD, usuario y contraseña en hPanel.

---

## 🚀 Fase 3: Subida del Proyecto y Configuración

1. **Subir los Archivos:**
    - Ve a **Archivos** > **Administrador de Archivos** en hPanel.
    - Sube y extrae tu `.zip`.

2. **Ajustar el Archivo `.env` (Configuración de Producción):**
    - Abre el archivo `.env` en el servidor y asegúrate de que el disco de archivos sea el correcto:

        ```env
        APP_NAME=Reembolsos
        APP_ENV=production
        APP_DEBUG=false
        APP_URL=https://reembolsosindi.com

        FILESYSTEM_DISK=public  # <--- INDISPENSABLE para que se vean los archivos

        DB_CONNECTION=mysql
        DB_HOST=127.0.0.1
        DB_DATABASE=uXXX_XXXXX
        DB_USERNAME=uXXX_XXXXX
        DB_PASSWORD=xxxxxxxxxx
        ```

3. **Restaurar Archivos Existentes (Si ya habías subido reembolsos):**
    - Si ya tenías archivos y no se ven, usa el Administrador de Archivos para **MOVER** las carpetas `xmls`, `pdfs` y `trips`:
    - De: `storage/app/private/`
    - A: `storage/app/public/`
    - _Si la carpeta `public` ya tiene carpetas con el mismo nombre, solo mueve el contenido._

---

## 💻 Fase 4: Comandos Críticos (SSH)

**IMPORTANTE:** Debes usar la ruta completa a la versión 8.4 de PHP que configuramos en Hostinger: `/opt/alt/php84/usr/bin/php`

1. **Navegar a la carpeta:**

    ```bash
    cd domains/reembolsosindi.com/public_html
    ```

2. **Actualizar la Base de Datos:**

    ```bash
    /opt/alt/php84/usr/bin/php artisan migrate --force
    ```

3. **Crear enlace de almacenamiento:**

    ```bash
    /opt/alt/php84/usr/bin/php artisan storage:link
    ```

4. **Optimizar para Producción:**
    ```bash
    /opt/alt/php84/usr/bin/php artisan config:cache
    /opt/alt/php84/usr/bin/php artisan route:cache
    /opt/alt/php84/usr/bin/php artisan view:cache
    ```

---

## 🚪 Fase 4: Redirección .htaccess

Si tu dominio apunta directamente a `public_html` pero el sistema está en una carpeta, asegúrate de que el `.htaccess` en la raíz de `public_html` tenga esto:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

---

## � ¿Borraste los archivos por accidente? (Recuperación)

Si subiste el `.zip` y borraste las facturas que los clientes ya habían subido, **no entres en pánico**, Hostinger guarda respaldos automáticos:

1.  En hPanel, ve a **Archivos** > **Copias de Seguridad** (Backups).
2.  Busca la opción **Restauración de Archivos**.
3.  Selecciona una fecha (de ayer o antes del error).
4.  Busca la carpeta `domains/tu-dominio/public_html/storage` y dale a **Restaurar**.
5.  Esto recuperará los XML y PDF perdidos sin afectar tu código nuevo.

---

## ⚠️ ADVERTENCIA DE SEGURIDAD (Regla de Oro)

Para futuras actualizaciones, sigue este flujo para **NUNCA** perder datos de clientes:

1.  **NO BORRES la carpeta `storage`** en el servidor.
2.  Cuando subas tu nuevo `.zip`, extráelo. Si el administrador de archivos te pregunta si quieres "Sobrescribir" (Overwrite), dile que **SÍ**. Esto actualizará el código pero **mantendrá las carpetas de facturas** intactas.
3.  **Excluye** tus archivos locales de prueba (toda tu carpeta `storage/app/public/xmls` y `pdfs`) del ZIP que subas, para no "ensuciar" el servidor con tus pruebas personales.

---

## �🎉 ¡Listo!

El sistema ahora reflejará todos los cambios de roles (Subdirección, Cuentas por Pagar), las notificaciones de Dirección y la nueva bitácora de observaciones.
