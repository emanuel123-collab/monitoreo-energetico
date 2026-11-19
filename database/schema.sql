-- Esquema MySQL/MariaDB de Monitoreo Energético (versión PHP).
-- Se crea automáticamente con instalar.php; también puede importarse a mano.
CREATE DATABASE IF NOT EXISTS monitoreo_energetico
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE monitoreo_energetico;

CREATE TABLE IF NOT EXISTS usuarios (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  nombre    VARCHAR(80)  NOT NULL,
  usuario   VARCHAR(30)  NOT NULL UNIQUE,
  clave     VARCHAR(255) NOT NULL,              -- password_hash() (bcrypt), nunca texto plano
  rol       ENUM('Administrador','Técnico') NOT NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS zonas (
  id     INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(40) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS equipos (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  nombre       VARCHAR(60) NOT NULL UNIQUE,
  zona_id      INT NOT NULL,
  sensor       ENUM('ACS712','SCT-013') NOT NULL,
  base_w       DECIMAL(8,2) NOT NULL,           -- consumo base del sensor virtual
  umbral_w     DECIMAL(8,2) NOT NULL CHECK (umbral_w > 0),
  calibracion  DECIMAL(4,2) NOT NULL DEFAULT 1.00 CHECK (calibracion BETWEEN 0.50 AND 1.50),
  conectado    TINYINT(1) NOT NULL DEFAULT 1,
  falla        TINYINT(1) NOT NULL DEFAULT 0,   -- sobreconsumo simulado (x1.8)
  FOREIGN KEY (zona_id) REFERENCES zonas(id)
);

CREATE TABLE IF NOT EXISTS lecturas (
  id         BIGINT AUTO_INCREMENT PRIMARY KEY,
  equipo_id  INT NOT NULL,
  potencia_w DECIMAL(8,2) NOT NULL,
  tomada_en  TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE,
  INDEX idx_lecturas_equipo_fecha (equipo_id, tomada_en)
);

CREATE TABLE IF NOT EXISTS alertas (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  equipo_id    INT NOT NULL,
  potencia_w   DECIMAL(8,2) NOT NULL,
  umbral_w     DECIMAL(8,2) NOT NULL,
  estado       ENUM('activa','atendida','resuelta automáticamente') NOT NULL DEFAULT 'activa',
  abierta      TINYINT(1) NOT NULL DEFAULT 1,   -- 1 mientras el consumo siga sobre el umbral
  creada_en    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  atendida_por INT NULL,
  FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE,
  FOREIGN KEY (atendida_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_alertas_abierta (equipo_id, abierta)
);

CREATE TABLE IF NOT EXISTS reportes (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  descripcion  VARCHAR(120) NOT NULL,
  tarifa_bs    DECIMAL(6,2) NOT NULL,
  total_kwh    DECIMAL(10,3) NOT NULL,
  total_bs     DECIMAL(10,2) NOT NULL,
  detalle      LONGTEXT NOT NULL,               -- filas del reporte en JSON
  generado_en  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  generado_por INT NULL,
  FOREIGN KEY (generado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS intentos_login (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  ip        VARCHAR(45) NOT NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_intentos (ip, creado_en)
);

INSERT IGNORE INTO zonas (nombre) VALUES ('Refrigeración'), ('Iluminación'), ('Climatización');
