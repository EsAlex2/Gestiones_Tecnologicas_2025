-- sql/schema.sql
CREATE DATABASE IF NOT EXISTS inventario_v1 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_general_ci;
USE inventario_v1;

-- Usuarios (con rol, username y teléfono)
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  phone VARCHAR(30),
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','operator','client') NOT NULL DEFAULT 'client',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla para tokens de recuperación de contraseña
CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(128) NOT NULL,
  expires_at DATETIME NOT NULL,
  used TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Categorías
CREATE TABLE IF NOT EXISTS categories (
  id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Proveedores
CREATE TABLE IF NOT EXISTS suppliers (
  id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    contact VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Clientes
CREATE TABLE IF NOT EXISTS clients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  contact VARCHAR(150),
  phone VARCHAR(50),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Items (privados por usuario) con categoría y proveedor
CREATE TABLE IF NOT EXISTS items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  sku VARCHAR(50) NOT NULL,
  name VARCHAR(150) NOT NULL,
  description TEXT,
  quantity INT NOT NULL DEFAULT 0,
  unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  category_id INT DEFAULT NULL,
  supplier_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
  UNIQUE KEY (user_id, sku)
);

-- Movimientos (entradas / salidas) relacionados a proveedor o cliente opcionalmente
CREATE TABLE IF NOT EXISTS movements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  item_id INT NOT NULL,
  type ENUM('in','out') NOT NULL,
  quantity INT NOT NULL,
  supplier_id INT DEFAULT NULL,
  client_id INT DEFAULT NULL,
  note VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
  FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL
);



-- Establecer el primer usuario como administrador
UPDATE users SET role = 'admin' WHERE id = 1;


-- Asegurarse de que la tabla users tiene el campo role


-- Actualizar usuarios existentes
UPDATE users SET role = 'admin' WHERE id = 1; 
-- Primer usuario como admin
-- UPDATE users SET role = 'operator' WHERE id IN (2,3); -- Otros usuarios como operadores
-- UPDATE users SET role = 'client' WHERE role IS NULL OR role = '';

-- Crear índices para mejor performance en consultas multi-usuario
CREATE INDEX  idx_items_user_id ON items(user_id);
CREATE INDEX idx_movements_user_id ON movements(user_id);
CREATE INDEX idx_users_role ON users(role); 

-- Verificar que las tablas tienen las columnas necesarias
ALTER TABLE items 
MODIFY COLUMN user_id INT NOT NULL,
ADD INDEX idx_user_id (user_id);

ALTER TABLE movements 
MODIFY COLUMN user_id INT NOT NULL,
ADD INDEX idx_user_id (user_id);


-- Actualizar tabla items para usar las nuevas tablas
ALTER TABLE items 
MODIFY COLUMN category_id INT NULL,
MODIFY COLUMN supplier_id INT NULL,
ADD FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
ADD FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL;

-- Insertar categorías por defecto
INSERT IGNORE INTO categories (name, description) VALUES 
('Electrónicos', 'Dispositivos y componentes electrónicos'),
('Ropa', 'Prendas de vestir y accesorios'),
('Hogar', 'Artículos para el hogar'),
('Oficina', 'Suministros de oficina'),
('Herramientas', 'Herramientas y equipos'),
('Deportes', 'Artículos deportivos'),
('Juguetes', 'Juguetes y juegos'),
('Alimentos', 'Productos alimenticios'),
('Salud', 'Productos de salud y belleza'),
('Automotriz', 'Repuestos y accesorios para vehículos');

-- Insertar proveedores por defecto
INSERT IGNORE INTO suppliers (name, contact, phone, email, address) VALUES 
('TecnoSupply S.A.', 'Juan Pérez', '+1-234-567-8900', 'ventas@tecnosupply.com', 'Av. Tecnológica 123, Ciudad'),
('Distribuidora Global', 'María García', '+1-234-567-8901', 'contacto@globaldist.com', 'Calle Comercio 456, Zona Industrial'),
('Importaciones Elite', 'Carlos Rodríguez', '+1-234-567-8902', 'info@eliteimports.com', 'Plaza Empresarial 789, Centro'),
('Suministros Rápidos', 'Ana Martínez', '+1-234-567-8903', 'pedidos@suministrosrapidos.com', 'Boulevard Industrial 321, Parque Industrial');

-- índices 
CREATE INDEX IF NOT EXISTS idx_categories_name ON categories(name);
CREATE INDEX IF NOT EXISTS idx_suppliers_name ON suppliers(name);

-- Modificaciones para el módulo de clientes empresariales

-- Tabla para tipos de empresa
CREATE TABLE IF NOT EXISTS business_types (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla para clientes empresariales
CREATE TABLE IF NOT EXISTS business_clients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  
  -- Información de la persona natural
  personal_dni VARCHAR(20) NOT NULL,
  personal_first_name VARCHAR(100) NOT NULL,
  personal_last_name VARCHAR(100) NOT NULL,
  personal_email VARCHAR(150),
  personal_gender ENUM('male', 'female', 'other') NOT NULL,
  personal_phone VARCHAR(30),
  
  -- Información de la empresa
  business_id VARCHAR(50) NOT NULL,
  business_name VARCHAR(200) NOT NULL,  
  business_phone VARCHAR(30),
  business_email VARCHAR(150),
  business_address TEXT,
  business_type_id INT NOT NULL,
  business_position VARCHAR(100) NOT NULL, -- Cargo del contacto
  
  -- Estado y timestamps
  status ENUM('active', 'inactive') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (business_type_id) REFERENCES business_types(id) ON DELETE RESTRICT,
  UNIQUE KEY (user_id), -- Un usuario solo puede tener un cliente empresarial
  UNIQUE KEY (personal_dni),
  UNIQUE KEY (business_id)
);

-- Insertar tipos de empresa comunes
INSERT INTO business_types (name, description) VALUES 
('Retail', 'Venta de productos al por menor'),
('Wholesale', 'Venta de productos al por mayor'),
('Services', 'Prestación de servicios'),
('Manufacturing', 'Fabricación de productos'),
('Technology', 'Empresas de tecnología'),
('Food & Beverage', 'Restaurantes y venta de alimentos'),
('Healthcare', 'Servicios de salud'),
('Education', 'Instituciones educativas'),
('Construction', 'Empresas constructoras'),
('Other', 'Otro tipo de empresa');

-- Agregar campo client_id a la tabla users para relacionar con business_clients
ALTER TABLE users ADD COLUMN client_id INT DEFAULT NULL AFTER role;
ALTER TABLE users ADD FOREIGN KEY (client_id) REFERENCES business_clients(id) ON DELETE SET NULL;

-- Agregar campo client_id a la tabla items para identificar a qué cliente pertenece
ALTER TABLE items ADD COLUMN client_id INT DEFAULT NULL AFTER user_id;
ALTER TABLE items ADD FOREIGN KEY (client_id) REFERENCES business_clients(id) ON DELETE CASCADE;

-- Agregar campo client_id a movements
ALTER TABLE movements ADD COLUMN client_business_id INT DEFAULT NULL AFTER client_id;
ALTER TABLE movements ADD FOREIGN KEY (client_business_id) REFERENCES business_clients(id) ON DELETE SET NULL;

-- Actualizar la restricción única en items para incluir client_id
ALTER TABLE items DROP INDEX user_id;
ALTER TABLE items ADD UNIQUE KEY (user_id, client_id, sku);