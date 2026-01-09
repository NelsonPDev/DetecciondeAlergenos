-- ========================================
-- Base de Datos: Detección de Alérgenos (LIMPIA)
-- ========================================

DROP DATABASE IF EXISTS deteccion_alergenos;
CREATE DATABASE deteccion_alergenos;
USE deteccion_alergenos;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ========================================
-- Tabla 1: USUARIOS
-- ========================================

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  INDEX `idx_email` (`email`),
  INDEX `idx_fecha` (`fecha_registro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Tabla 2: USUARIO_ALÉRGENOS
-- (Alérgenos específicos de cada usuario)
-- ========================================

CREATE TABLE `usuario_alergenos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `nombre_alergeno` varchar(100) NOT NULL,
  `fecha_agregado` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuario_alergeno` (`usuario_id`, `nombre_alergeno`),
  INDEX `idx_usuario` (`usuario_id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Tabla 3: HISTORIAL_ESCANEOS
-- (Historial de productos escaneados)
-- ========================================

CREATE TABLE `historial_escaneos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11),
  `codigo_barras` varchar(50),
  `nombre_producto` varchar(200),
  `marca` varchar(100),
  `imagen_url` varchar(500),
  `alergenos_detectados` json,
  `fecha_escaneo` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  INDEX `idx_usuario` (`usuario_id`),
  INDEX `idx_fecha` (`fecha_escaneo`),
  INDEX `idx_codigo` (`codigo_barras`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Fin de la base de datos
-- ========================================

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
