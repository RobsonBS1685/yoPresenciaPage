# Yopresencia Psicología — Angular + PHP + MySQL (XAMPP)

## Qué incluye
- Inicio con la portada minimalista y el footer; los enlaces abren páginas independientes.
- Páginas separadas: Sobre mí, Servicios, Valores, Contacto, Iniciar sesión y Registro.
- Formulario de contacto conectado a MySQL.
- Registro e inicio de sesión con contraseñas almacenadas mediante `password_hash()`.
- Sesiones PHP con cookie HttpOnly, SameSite=Lax y regeneración del identificador al iniciar sesión.
- Panel de administración protegido en frontend y backend: ver mensajes, marcar como leídos, borrar mensajes y consultar/eliminar cuentas normales.
- Consultas SQL preparadas, validación de datos, lista de orígenes permitidos, límite de longitud y cabeceras de seguridad.

## Instalación en Windows con XAMPP

1. Descomprime este proyecto. Copia la carpeta `backend` dentro de `C:\xampp\htdocs\yopresencia-api\` para que exista `C:\xampp\htdocs\yopresencia-api\api\index.php`.
2. Abre el panel XAMPP e inicia **Apache** y **MySQL**.
3. Entra a `http://localhost/phpmyadmin`, selecciona **Importar** y carga `backend/schema.sql` desde la carpeta del proyecto. Se creará la base `yopresencia`, con tablas `users` y `contact_messages`.
4. Revisa `C:\xampp\htdocs\yopresencia-api\config.php`. En una instalación local estándar de XAMPP el usuario suele ser `root` y la contraseña está vacía. Si tu MySQL tiene contraseña, configura `YOP_DB_PASS` en el entorno o cambia el archivo localmente. No subas contraseñas a un repositorio público.
5. Abre una terminal y ejecuta:
   ```powershell
   cd C:\xampp\htdocs\yopresencia-api
   C:\xampp\php\php.exe create_admin.php
   ```
   Ingresa nombre, correo y una contraseña única de al menos 12 caracteres con mayúscula, minúscula y número. La contraseña se escribe visible en esta versión de la terminal; ejecútalo en un lugar privado. Este script solo funciona por CLI, nunca desde el navegador.
6. En otra terminal, entra a la carpeta `Yopresencia` y ejecuta `npm install` (si fuera necesario) y luego `npm start`.
7. Abre `http://localhost:4200`. Prueba crear una cuenta normal, enviar un mensaje desde Contacto y luego iniciar sesión con el correo administrador para abrir `/admin`.

## Configuración importante
- Angular apunta a `http://localhost/yopresencia-api/api/index.php` en `src/app/core/api.service.ts`. Si cambias el nombre de la carpeta en `htdocs`, actualiza esa URL.
- El backend solo permite los orígenes `http://localhost:4200` y `http://127.0.0.1:4200`. Si usas otro origen local, añádelo explícitamente en `backend/config.php`.
- Las credenciales iniciales del administrador no están escritas en el código. El registro público siempre crea rol `user`; solo el script CLI crea administradores.

## Antes de publicarlo en Internet
XAMPP es para desarrollo local, no un servidor de producción. Antes de publicar: usa HTTPS, configura credenciales fuera del repositorio, limita permisos de la cuenta MySQL, añade protección anti-fuerza-bruta/rate limiting, política de privacidad y consentimiento, copias de seguridad y pruebas de seguridad. Para datos psicológicos, no recopiles información clínica sensible en mensajes públicos ni prometas confidencialidad absoluta sin definir procedimientos y obligaciones profesionales. El panel es una base funcional inicial, no una certificación de seguridad ni un sistema clínico completo.
