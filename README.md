# Inventario v1 (PHP + MySQL) - Con recuperación por correo

**Novedades v1**
- Login con username o email.
- Registro extendido: username, nombres, apellidos, teléfono.
- Recuperación de contraseña vía correo (token temporal).
- Clientes CRUD añadido.
- Movimientos pueden asociarse a proveedor o cliente.
- Configura SMTP en `config/config.php` y usa PHPMailer para envío real.

## Configurar envío de correos (PHPMailer)
1. Desde la raíz del proyecto (donde está `composer.json` si lo creas) instala PHPMailer:
   ```bash
   composer require phpmailer/phpmailer
   ```
   Esto generará `vendor/` con el autoloader que usan las páginas de recuperación.
2. Edita `config/config.php` y ajusta `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS`, `SMTP_FROM`.
3. En entornos locales puedes probar sin SMTP: el sistema guardará el token y mostrará un enlace de prueba.

## Importante
- Ejecuta `sql/schema.sql` en tu base de datos para crear las nuevas tablas.
- Ajusta `config/config.php` según tu entorno.
- Accede a `http://localhost/inventario_v1/public`
