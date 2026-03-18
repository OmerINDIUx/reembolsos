# Guía de Despliegue Final en Hostinger (PHP 8.4)

Esta guía contiene los pasos finales para subir la versión actualizada del sistema de reembolsos a Hostinger.

---

## 🏗️ Fase 1: Preparación Local

1. **Compilar el Diseño (OBLIGATORIO):**
    - Ejecuta `ls` en tu terminal local. Esto empaqueta todos los cambios visuales y validaciones en la carpeta `public/build`.
2. **Crear el Archivo ZIP (MODO SEGURO):**
    - Ve a `c:\laragon\www\reembolsos`.
    - Selecciona **todos** los archivos y carpetas, EXCEPTO:
        - ❌ `node_modules` (Demasiado pesado)
        - ❌ `tests` (Solo para desarrollo)
        - ❌ `.git` (Control de versiones)
        - ❌ **`storage/app/public/xmls`** (NO subas tus XML de prueba)
        - ❌ **`storage/app/public/pdfs`** (NO subas tus PDF de prueba)
        - ❌ **`storage/app/public/trips`** (NO subas tus carpetas de prueba)
    - **Asegúrate de incluir el archivo `.env`** (aunque en el servidor lo revisaremos).
      elminamos el hot de public
    - Comprime todo en un archivo llamado `reembolsos_update.zip`.

---

## 🌐 Fase 2: Configuración en Hostinger (hPanel)

1. **Base de Datos:**
    - Asegúrate de tener creados el nombre de la BD, usuario y contraseña en hPanel.

2. **⚙️ Configuración PHP (CRÍTICO para Bulk Upload):**
    - En hPanel, ve a **Avanzado** > **Configuración de PHP**.
    - Haz clic en la pestaña **Opciones de PHP**.
    - Busca y ajusta los siguientes valores para permitir la carga de hasta 20 facturas (64MB):
        - `max_file_uploads` ➡️ **60** (para cubrir XML + PDF + margen)
        - `upload_max_filesize` ➡️ **15M** (margen para PDFs pesados)
        - `post_max_size` ➡️ **80M** (total de la sesión de carga)
        - `max_execution_time` ➡️ **300** (dar tiempo al servidor para procesar todo)
    - Haz clic en **Guardar** al final de la página.

---

## 🚀 Fase 3: Subida del Proyecto y Configuración

1. **Subir los Archivos:**
    - Ve a **Archivos** > **Administrador de Archivos** en hPanel.
    - **IMPORTANTE:** No borres nada aún. Sube el archivo `reembolsos_update.zip`.
    - Al extraerlo, Hostinger te preguntará si deseas **Sobrescribir (Overwrite)**. Dile que **SÍ**. Esto reemplazará el código viejo por el nuevo pero **mantendrá las facturas que ya existen en el servidor**.

2. **Ajustar el Archivo `.env`:**
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
/opt/alt/php84/usr/bin/php artisan migrate --force
---

## 🚪 Fase 5: Redirección .htaccess

Si tu dominio apunta directamente a `public_html` pero el sistema está en una carpeta, asegúrate de que el `.htaccess` en la raíz de `public_html` tenga esto:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

---

## 🛡️ Reglas de Oro para NO Perder Datos

Para proteger los archivos que ya están en producción:

1.  **Nunca borres la carpeta `storage` del servidor:** Esta carpeta contiene las facturas reales de los usuarios. El ZIP que subes debe ser solo para actualizar el código (`app`, `resources`, `public`, etc.).
2.  **Base de Datos:** Nunca ejecutes `migrate:fresh`. Usa siempre `migrate --force`. El comando `fresh` borraría todas las tablas y datos existentes.
3.  **Backups:** Antes de subir nada, Hostinger permite descargar un respaldo de la base de datos desde la sección "Bases de Datos MySQL". Hazlo por precaución.
4.  **Vínculo Simbólico:** Si después de subir el código las facturas viejas no se ven (error 404), vuelve a ejecutar el comando `php artisan storage:link` por SSH.

---

## ¿Borraste los archivos por accidente? (Recuperación)

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

---

## 🐙 ANEXO: Despliegue Automático con Git (GitHub / GitLab)

Dado que ahora el proyecto está versionado correctamente y tiene los archivos protegidos en `.gitignore`, puedes vincular tu cuenta de Hostinger con Git para que las actualizaciones suban inmediatamente cuando hagas `git push`. 

### 1. Sube tu código a GitHub/GitLab
1. Crea un repositorio vacío (sin README ni .gitignore) en GitHub o GitLab.
2. Abre tu terminal en la carpeta local de tu proyecto y ejecuta:
```bash
git remote add origin URL_DE_TU_REPOSITORIO.git
git branch -M main
git push -u origin main
```
*(Asegúrate de ejecutar siempre `npm run build` localmente y hacer commit de los cambios generados antes de hacer `git push`).*

### 2. Vincula el Repositorio en Hostinger (hPanel)
1. Entra a hPanel y ve a **Avanzado** > **GIT**.
2. Completa los datos:
   - **Repositorio:** Escribe la ruta de tu repositorio (Ej: `usuario/reembolsos`). 
   - **Rama (Branch):** Escribe `main` o `master`.
   - **Directorio de instalación:** Asegúrate de que apunte a `public_html` (o a la subcarpeta donde está tu sistema si no está en la raíz).
3. Haz clic en **Crear**. 
   *(Si tu repositorio es privado, Hostinger te pedirá generar una "Deploy Key" (Clave de Despliegue ssh-rsa) y pegarla en las configuraciones (Settings > Deploy Keys) de tu repositorio en GitHub/GitLab).*

### 3. Activar Auto-Deploy (Webhooks)
Para que al hacer `git push` Hostinger se actualice de forma 100% automática:
1. En la misma pantalla de **GIT** en Hostinger, encontrarás una columna llamada **Webhook**.
2. Ahí verás una URL ("Auto Deployment URL"). Cópiala.
3. Ve a tu repositorio en **GitHub/GitLab** > **Settings** > **Webhooks** > **Add webhook**.
4. Pega la URL en "Payload URL", selecciona *Content type* como `application/json` y dale a agregar. 

### 4. ¿Qué hacer después de un Git Push?
Hostinger subirá de inmediato el nuevo código y compilará la información nueva (porque `public/build` ya no se ignora), pero **CUIDADO**: Git no ejecuta migraciones de base de datos automáticamente en Hostinger PHP Compartido.
* **Si agregaste nuevas tablas/columnas:** siempre tienes que ingresar por SSH (Fase 4 de este archivo) a correr `/opt/alt/php84/usr/bin/php artisan migrate --force`.
* Tus facturas en `public/storage`, tus configuraciones de `.env` y el archivo `node_modules`/`hot` quedarán intactos y completamente a salvo y no serán sobrescritos por Git.
