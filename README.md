# 📦 Sistema de Gestión de Inventario - Gestiones Tecnológicas

Este sistema es una plataforma web robusta diseñada para la administración jerárquica de inventarios. Permite a las empresas no solo llevar un control de sus activos (items), sino también gestionar la relación entre empleados, proveedores y clientes corporativos bajo un esquema de seguridad estricto.

## 🛠️ Arquitectura Técnica

El proyecto sigue un patrón de diseño modular donde la lógica de negocio, la autenticación y la interfaz de usuario están claramente separadas:

   - Capa de Autenticación (lib/auth.php): Implementa un control de acceso basado en roles (RBAC). Define tres niveles: ADMIN, OPERATOR y ANALYST.

   - Motor de Datos: Utiliza PDO (PHP Data Objects) para interactuar con MySQL, garantizando protección contra inyecciones SQL mediante consultas preparadas.

   - Interfaz Dinámica: Uso de jQuery para validaciones en tiempo real y Chart.js para la representación de datos analíticos.


## 📊 Módulos Principales

1. Dashboard de Analítica

Ubicado en dashboard.php, ofrece un resumen visual del estado del inventario.

   - Gráfico de Distribución: Un gráfico circular (Doughnut) que muestra la proporción de ítems por categoría.

   - Gráfico de Stock por Proveedor: Un gráfico de barras que identifica qué proveedores tienen mayor volumen de productos asignados.

   - Métricas Rápidas: Contador de ítems totales y valorización del inventario.

![alt text](<dashboard admin.png>)







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
