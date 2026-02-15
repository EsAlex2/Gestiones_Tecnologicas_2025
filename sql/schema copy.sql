-- sql/schema.sql
CREATE DATABASE IF NOT EXISTS inventario_v1 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_general_ci;
USE inventario_v1;

-- Usuarios (con rol, username y teléfono)
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cedula VARCHAR(20) NOT NULL UNIQUE,
  username VARCHAR(50) NOT NULL UNIQUE,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  phone VARCHAR(30),
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','operator','analyst') NOT NULL DEFAULT 'analyst',
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

--------------------actualizaciones a las tablas de la base de datos
-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

-- índices 
CREATE INDEX IF NOT EXISTS idx_categories_name ON categories(name);
CREATE INDEX IF NOT EXISTS idx_suppliers_name ON suppliers(name);

-- Crear índices para mejor performance en consultas multi-usuario
CREATE INDEX  idx_items_user_id ON items(user_id);
CREATE INDEX idx_movements_user_id ON movements(user_id);
CREATE INDEX idx_users_role ON users(role); 

-- Agregar campo cedula a la tabla users para identificación única
ALTER TABLE users 
ADD COLUMN cedula VARCHAR(20) UNIQUE AFTER username;

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



-- Insertar tipos de empresa comunes
INSERT INTO business_types (name, description) VALUES 
('Educacion', 'Gestion Educativa'),
('Wholesale', 'Gestion en el area de salud');


-- Insertar categorías por defecto
INSERT IGNORE INTO categories (name, description) VALUES 
('Sala de Computacion', 'Equipos informaticos'),
('Sala de Servidores', 'Serivores Empresariales'),
('Centro de Comunicaciones', 'Salon de datos e Informacion'),
('Electrónicos', 'Dispositivos y componentes electrónicos'),
('Direccion Administrativa', 'Sala de Informacion administrativa'),
('Oficina', 'Suministros de oficina'),
('Herramientas', 'Herramientas y equipos');

-- Insertar proveedores por defecto
insert ignore into suppliers (name, contact, phone, email, address) values
('Indatech', 'Jose Perez', '04242977384', 'Info@indatechca.com', 'c.c.c.t, Torre C, piso 7 oficinas 707 C. Ernesto Blohm, Caracas 1060, Distrito Capital 
Venezuela');


--insertar equipos del iuti
insert ignore into items (id, user_id, sku, name, description, quantity, category_id, supplier_id) 
values
(1,	1,	'IUTI-DIRNAC-002',	'equipo 01', 	'Interl Core I3-2120 3.30 ghz	8GB	500GB SSD	Windows 10',	1,	1,	1),
(2,	1,	'IUTI-SUBDIR-001',	'equipo 02',	'AMD Athlon II X3 435	8GB	500GB HDD	Windows 10',	1,	1,	1),
(3,	1,	'IUTI-ASTDNC-002',	'equipo 03',	'Intel Core I5 8500 3GHZ	8GB	500 SSD	Windows 11',	1,	1,	1),
(4,	1,	'IUTI-CACADEMICA',	'equipo 04',	'Intel Core I5 4590S 3GHZ	8GB	256 SSD	Windows 7 Ultimate',	1,	1,	1),
(5,	1,	'IUTI-ASTCA-001',	'equipo 05',	'Intel Core I5 3470S 2,9 GHZ	6GB	500 HDD	Windows 10',	1,	1,	1),
(6,	1,	'IUTI-ACDM-001',	'equipo 06',	'Intel Core I5 8500 3GHZ	8GB	256 HDD	Windows 11',	1,	1,	1),
(7,	1,	'IUTI-ADMON-001',	'equipo 07',	'Intel Core I5 6500 3,20 GHZ	8GB	256 SSD	Windows 10',	1,	1,	1),
(8, 1,	'IUTI-ASTADM-001',	'equipo 08',	'Intel(R) Core(TM) i5-6500 CPU @ 3.20GHz   3.19 GHz	8GB	256 SSD	Windows 11',	1,	1,	1),
(9, 1,	'IUTI-ASTCONTBL',	'equipo 09',	'Pentium(R) Dual Core CPU E5500 2.8 GHz	4GB	128 HHD	Windows 7 Ultimate',	1,	1,	1),
(10, 1,	'IUTI-CAJA01',	'equipo 10',	'Intel(R) Core(TM) i3-2100 CPU @ 3.10GHz   3.10 GHz	4GB	500 SSD	Windows 10',	1,	1,	1),
(11, 1,	'IUTI-CAJA02',	'equipo 11',	'Intel(R) Core(TM) i3-4130 CPU @ 3.10GHz   3.40 GHz	8GB	256 SSD	Windows 10',	1,	1,	1),
(12, 1,	'IUTI-CLIPER-001',	'equipo 12',	'Intel(R) Core(TM) i7-7700 CPU @ 3.60GHz	8GB	256 SSD	Windows 7 Ultimate',	1,	1,	1),
(13,	1,	'IUTI-ARCHV-001',	'equipo 13',	'Intel(R) Core(TM) i7-7700 CPU @ 3.60GHz	8GB	256 ssd	Windows 7 Ultimate',	1, 1,	1),
(14,	1,	'TAQ001',	'equipo 14',	'Intel(R) Core(TM) i3-2130 CPU @ 3.40GHz	5GB	256 ssd	Windows 7 Ultimate',	1,	1, 1),
(15,	1,	'TAQ002',	'equipo 15',	'Intel(R) Core(TM) i3-4130 CPU @ 3.40GHz	8GB	256 ssd Windows 7 Ultimate',	1,	1,	1),
(16,	1,	'IUTI-ASICE-001',	'equipo 16',	'Intel(R) Core(TM) i3-4130 CPU @ 3.40GHz	8GB	500GB HDD	Windows 7 Ultimate',	1,	1,	1),
(17,	1,	'IUTI-CORDCE-001',	'equipo 17',	'Intel(R) Core(TM) i5-4570 CPU @ 3.20 GHz	8GB	500GB HDD	Windows 7 Ultimate',	1,	1,	1),
(18,	1,	'IUTI-CBASIC-001',	'equipo 18',	'Intel(R) Core(TM) i7-7700 CPU @ 3.60GHz   3.60 GHz	8GB	256SSD	Windows 11',	1,	1,	1),
(19,	1,	'IUTI-RINST-002',	'equipo 19',	'Intel(R) Core(TM) i3-4130 CPU @ 3.40GHz   3.40 GHz	8GB	256SSD	Windows 11',	1,	1,	1),
(20,	1,	'IUTI-RINST-001',	'equipo 20',	'Intel(R) Core(TM) i5-8500 CPU @ 3.00GHz (3.00 GHz)	16GB	500 SSD	Windows 10',	1,	1,	1),
(21,	1,	'IUTI-BIBLIO-001',	'equipo 21',	'Intel(R) Core(TM) i5-6500 CPU @ 3.20GHz (3.20 GHz)	8gb	256 SSD	Windows 10',	1,	1,	1),
(22,	1,	'IUTI-CPAS-001',	'equipo 22',	'Intel(R) Core(TM) i3-4130 CPU @ 3.40GHz   3.40 GHz	8gb	256 SSD	Windows 10',	1,	1,	1),
(23,	1, 'IUTI-CEXT-001',	'equipo 23',	'Intel(R) Core(TM) i5-6500 CPU @ 3.20GHz (3.20 GHz)	8gb	256 SSD	Windows 10',	1,	1,	1);



