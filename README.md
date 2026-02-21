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

2. Gestión de Clientes Empresariales (business_clients.php)

Un módulo especializado para el sector B2B que permite:

   - Vincular usuarios del sistema con empresas específicas.

   - Registrar datos fiscales (DNI/RUT), cargos jerárquicos y contactos corporativos.

   - Filtrado de clientes por tipo de industria o sector.

🔐 Seguridad y Reglas de Validación

El sistema implementa políticas estrictas para garantizar la integridad de la información:

| Campo | Regla de Validación | Motivo |
| :--- | :---: | ---: |
| Email | Debe terminar en @gmail.com | Política de estandarización corporativa. |
| Password | Entre 8 y 16 caracteres | Equilibrio entre usabilidad y fuerza bruta. |
| Categorías | Bloqueo de borrado si tiene ítems | Evitar registros huérfanos en la base de datos. |
| Sesiones | require_login() en cada cabecera | Prevenir acceso no autorizado por URL directa. |

# 🚀 Guía de Despliegue Rápido

## Requisitos Previos

   - PHP >= 8.0

   - Servidor Web (Apache/Nginx)

   - MySQL 5.7+ o MariaDB

## Pasos de Instalación

   1. Base de Datos:
    Importa el esquema inicial. El sistema requiere tablas para users, items, categories, suppliers y business_clients.

   2. Configuración de Conexión:
    Asegúrate de que el archivo config/db.php apunte a tu instancia de base de datos local o remota.

   3. Primer Usuario:
    Utiliza el módulo signup.php para crear el primer administrador. El sistema detectará automáticamente si es el primer registro para otorgar privilegios elevados si es necesario.

📁 Estructura de Archivos Clave

   - _layout_top.php: Contiene el menú dinámico que cambia según el rol del usuario conectado.

   - users.php: Interfaz para que Administradores gestionen el personal y sus roles.

   - categories.php: CRUD de categorías con validación de duplicados.

### Desarrollado para: 
Gestiones Tecnológicas S.A. Versión: 1.0.4

### Licencia: Propietaria - Todos los derechos reservados.
