-- SQL d'initialisation pour Police Routière

CREATE DATABASE IF NOT EXISTS police_routiere_BD 
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE police_routiere_BD;

-- Créer l'utilisateur du projet
CREATE USER IF NOT EXISTS 'police_user'@'%' IDENTIFIED BY 'police_secure_pwd_2024';
GRANT ALL PRIVILEGES ON police_routiere_BD.* TO 'police_user'@'%';
FLUSH PRIVILEGES;

-- Configuration de la base
SET GLOBAL sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
SET SESSION sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
