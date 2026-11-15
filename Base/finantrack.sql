
CREATE DATABASE IF NOT EXISTS finantrack
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE finantrack;

CREATE TABLE usuarios (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB;


CREATE TABLE categorias (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id INT UNSIGNED NOT NULL,
  nombre VARCHAR(80) NOT NULL,
  tipo ENUM('ingreso','gasto') NOT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categoria_usuario (usuario_id, nombre, tipo),
  UNIQUE KEY uq_categoria_id_usuario (id, usuario_id),
  CONSTRAINT fk_categorias_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE movimientos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id INT UNSIGNED NOT NULL,
  categoria_id INT UNSIGNED NOT NULL,
  tipo ENUM('ingreso','gasto') NOT NULL,
  descripcion VARCHAR(150) NOT NULL,
  importe DECIMAL(12,2) NOT NULL,
  fecha DATE NOT NULL,
  metodo_pago VARCHAR(40) NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_mov_usuario_fecha (usuario_id, fecha),
  KEY idx_mov_usuario_tipo (usuario_id, tipo),
  KEY idx_mov_categoria (categoria_id, usuario_id),
  CONSTRAINT chk_mov_importe CHECK (importe > 0),
  CONSTRAINT fk_mov_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
    ON DELETE CASCADE,
  CONSTRAINT fk_mov_categoria
    FOREIGN KEY (categoria_id, usuario_id)
    REFERENCES categorias (id, usuario_id)
    ON DELETE RESTRICT
) ENGINE=InnoDB;


CREATE TABLE presupuestos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id INT UNSIGNED NOT NULL,
  categoria_id INT UNSIGNED NOT NULL,
  mes_anio DATE NOT NULL COMMENT 'Primer dia del mes, ej. 2026-11-01',
  monto_limite DECIMAL(12,2) NOT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_presupuesto (usuario_id, categoria_id, mes_anio),
  KEY idx_pres_categoria (categoria_id, usuario_id),
  CONSTRAINT chk_pres_monto CHECK (monto_limite > 0),
  CONSTRAINT fk_pres_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
    ON DELETE CASCADE,
  CONSTRAINT fk_pres_categoria
    FOREIGN KEY (categoria_id, usuario_id)
    REFERENCES categorias (id, usuario_id)
    ON DELETE RESTRICT
) ENGINE=InnoDB;


CREATE TABLE objetivos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id INT UNSIGNED NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  descripcion VARCHAR(255) NULL,
  monto_objetivo DECIMAL(12,2) NOT NULL,
  fecha_limite DATE NOT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_objetivo_id_usuario (id, usuario_id),
  KEY idx_obj_usuario (usuario_id),
  CONSTRAINT chk_obj_monto CHECK (monto_objetivo > 0),
  CONSTRAINT fk_obj_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
    ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE aportaciones (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id INT UNSIGNED NOT NULL,
  objetivo_id INT UNSIGNED NOT NULL,
  importe DECIMAL(12,2) NOT NULL,
  fecha DATE NOT NULL,
  nota VARCHAR(255) NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_aport_objetivo (objetivo_id, usuario_id),
  KEY idx_aport_usuario_fecha (usuario_id, fecha),
  CONSTRAINT chk_aport_importe CHECK (importe > 0),
  CONSTRAINT fk_aport_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
    ON DELETE CASCADE,
  CONSTRAINT fk_aport_objetivo
    FOREIGN KEY (objetivo_id, usuario_id)
    REFERENCES objetivos (id, usuario_id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE intentos_login (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(150) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_intentos (email, ip, creado_en)
) ENGINE=InnoDB;