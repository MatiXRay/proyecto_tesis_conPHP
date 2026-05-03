-- ============================================================
-- BIALYSTOK BREWING CO — Schema + datos de ejemplo para Docker
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ── roles ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `roles` (
  `id`     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(50)  NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `roles` (`id`, `nombre`) VALUES
  (1, 'Admin'),
  (2, 'Elaborador'),
  (3, 'Taster');

-- ── users ────────────────────────────────────────────────────
-- Las contraseñas las genera docker/seed.php
CREATE TABLE IF NOT EXISTS `users` (
  `id`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre`   VARCHAR(100) NOT NULL,
  `apellido` VARCHAR(100) NOT NULL DEFAULT '',
  `mail`     VARCHAR(150) NOT NULL,
  `telefono` VARCHAR(30)  NOT NULL DEFAULT '',
  `rol_id`   INT UNSIGNED NOT NULL DEFAULT 3,
  `username` VARCHAR(50)  NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`),
  UNIQUE KEY `uq_mail`     (`mail`),
  FOREIGN KEY (`rol_id`) REFERENCES `roles`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── configuraciones ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `configuraciones` (
  `id`                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `creacion_usuarios`    TINYINT(1)   NOT NULL DEFAULT 0,
  `alertas_habilitadas`  TINYINT(1)   NOT NULL DEFAULT 1,
  `dias_alerta_limp`     INT          NOT NULL DEFAULT 30,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `configuraciones` (`id`, `creacion_usuarios`, `alertas_habilitadas`, `dias_alerta_limp`)
VALUES (1, 1, 1, 30);

-- ── variedades_malta ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `variedades_malta` (
  `id`     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(150) NOT NULL,
  `marca`  VARCHAR(150) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `variedades_malta` (`nombre`, `marca`) VALUES
  ('Pale Ale 2-Row',    'Crisp'),
  ('Munich I',          'Weyermann'),
  ('Caramunich I',      'Weyermann'),
  ('Chocolate',         'Crisp'),
  ('Crystal 60L',       'Briess'),
  ('Pilsner',           'Best Malz'),
  ('Wheat Malt',        'Weyermann'),
  ('Black Patent',      'Crisp');

-- ── variedades_lupulo ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `variedades_lupulo` (
  `id`     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(150) NOT NULL,
  `marca`  VARCHAR(150) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `variedades_lupulo` (`nombre`, `marca`) VALUES
  ('Cascade',       'Hopunion'),
  ('Centennial',    'Hopunion'),
  ('Citra',         'Yakima Chief'),
  ('Mosaic',        'Yakima Chief'),
  ('Magnum',        'HVG'),
  ('Saaz',          'Czech'),
  ('Hallertau',     'HVG');

-- ── cepas_levadura ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `cepas_levadura` (
  `id`    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cepa`  VARCHAR(150) NOT NULL,
  `marca` VARCHAR(150) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `cepas_levadura` (`cepa`, `marca`) VALUES
  ('US-05 American Ale',    'Fermentis'),
  ('S-04 English Ale',      'Fermentis'),
  ('W-34/70 Lager',         'Fermentis'),
  ('BRY-97 West Coast Ale', 'Lallemand'),
  ('WB-06 Weizen',          'Fermentis');

-- ── estilos_cerveza ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `estilos_cerveza` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre`        VARCHAR(150) NOT NULL,
  `descripcion`   TEXT,
  `duracion_dias` INT          NOT NULL DEFAULT 21,
  `color`         VARCHAR(7)   DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_estilo_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `estilos_cerveza` (`nombre`, `descripcion`, `duracion_dias`, `color`) VALUES
  ('American IPA',     'Hoppy, bitter, citrus and pine notes. High aroma.', 21, '#2e7db5'),
  ('American Stout',   'Roasty, dark, chocolate and coffee notes.', 28,       '#5c3317'),
  ('Pale Ale',         'Balanced malt and hops, fruity esters.', 18,          '#c8922a'),
  ('German Lager',     'Clean, crisp, light malt body, low bitterness.', 42,  '#4a8f4a');

-- ── recetas_estilos ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `recetas_estilos` (
  `id`         INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `estilo_id`  INT UNSIGNED   NOT NULL,
  `og`         DECIMAL(5,3)   NOT NULL DEFAULT 1.000,
  `fg`         DECIMAL(5,3)   NOT NULL DEFAULT 1.000,
  `ibu`        INT            NOT NULL DEFAULT 0,
  `abv`        DECIMAL(4,2)   NOT NULL DEFAULT 0.00,
  `carb_level` DECIMAL(4,2)   NOT NULL DEFAULT 2.50,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`estilo_id`) REFERENCES `estilos_cerveza`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `recetas_estilos` (`estilo_id`, `og`, `fg`, `ibu`, `abv`, `carb_level`) VALUES
  (1, 1.065, 1.012, 65, 6.90, 2.40),  -- IPA
  (2, 1.060, 1.014, 40, 6.00, 2.20),  -- Stout
  (3, 1.052, 1.010, 35, 5.50, 2.50),  -- Pale Ale
  (4, 1.048, 1.008, 18, 5.20, 2.60);  -- Lager

-- ── recetasmalta ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `recetasmalta` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `id_receta`    INT UNSIGNED  NOT NULL,
  `malta_id`     INT UNSIGNED  NOT NULL,
  `cantidad_kg`  DECIMAL(8,3)  NOT NULL DEFAULT 0,
  `ppg`          INT           NOT NULL DEFAULT 0,
  `porcentaje`   DECIMAL(5,2)  NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_receta`) REFERENCES `recetas_estilos`(`id`),
  FOREIGN KEY (`malta_id`)  REFERENCES `variedades_malta`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `recetasmalta` (`id_receta`, `malta_id`, `cantidad_kg`, `ppg`, `porcentaje`) VALUES
  -- IPA (receta 1)
  (1, 1, 5.500, 37, 85.0),
  (1, 3, 0.500, 34, 7.7),
  (1, 5, 0.450, 34, 7.0),
  -- Stout (receta 2)
  (2, 1, 4.500, 37, 75.0),
  (2, 4, 0.500, 25, 8.3),
  (2, 3, 0.600, 34, 10.0),
  (2, 8, 0.300, 25, 5.0),
  -- Pale Ale (receta 3)
  (3, 1, 4.200, 37, 82.0),
  (3, 2, 0.500, 37, 9.8),
  (3, 3, 0.400, 34, 7.8),
  -- Lager (receta 4)
  (4, 6, 4.800, 37, 90.0),
  (4, 2, 0.500, 37, 9.4);

-- ── recetaslupulo ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `recetaslupulo` (
  `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `id_receta`       INT UNSIGNED  NOT NULL,
  `lupulo_id`       INT UNSIGNED  NOT NULL,
  `cantidad_gr`     DECIMAL(8,2)  NOT NULL DEFAULT 0,
  `tiempo_minutos`  INT           NOT NULL DEFAULT 60,
  `ibu`             DECIMAL(6,2)  NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_receta`)  REFERENCES `recetas_estilos`(`id`),
  FOREIGN KEY (`lupulo_id`)  REFERENCES `variedades_lupulo`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `recetaslupulo` (`id_receta`, `lupulo_id`, `cantidad_gr`, `tiempo_minutos`, `ibu`) VALUES
  -- IPA
  (1, 5, 30.0, 60, 30.0),
  (1, 1, 40.0, 15, 20.0),
  (1, 3, 60.0,  5,  8.0),
  (1, 4, 60.0,  0,  0.0),
  -- Stout
  (2, 5, 35.0, 60, 35.0),
  (2, 1, 20.0, 10,  5.0),
  -- Pale Ale
  (3, 1, 25.0, 60, 20.0),
  (3, 2, 30.0, 10, 10.0),
  (3, 3, 40.0,  0,  0.0),
  -- Lager
  (4, 5, 20.0, 60, 15.0),
  (4, 6, 30.0, 10,  3.0);

-- ── recetaslevadura ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `recetaslevadura` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `id_receta`  INT UNSIGNED  NOT NULL,
  `cepa_id`    INT UNSIGNED  NOT NULL,
  `generacion` VARCHAR(20)   NOT NULL DEFAULT '1',
  `temperatura` DECIMAL(4,1) NOT NULL DEFAULT 20.0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_receta`) REFERENCES `recetas_estilos`(`id`),
  FOREIGN KEY (`cepa_id`)   REFERENCES `cepas_levadura`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `recetaslevadura` (`id_receta`, `cepa_id`, `temperatura`) VALUES
  (1, 1, 19.0),  -- IPA → US-05
  (2, 2, 18.0),  -- Stout → S-04
  (3, 1, 19.0),  -- Pale Ale → US-05
  (4, 3, 10.0);  -- Lager → W-34/70

-- ── fermentadores ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `fermentadores` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre`              VARCHAR(100) NOT NULL,
  `capacidad`           VARCHAR(50)  NOT NULL DEFAULT '',
  `limp_alcalina_date`  DATE         DEFAULT NULL,
  `limp_acida_date`     DATE         DEFAULT NULL,
  `limp_oxidativa_date` DATE         DEFAULT NULL,
  `limp_exterior_date`  DATE         DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `fermentadores` (`nombre`, `capacidad`, `limp_alcalina_date`, `limp_acida_date`, `limp_oxidativa_date`, `limp_exterior_date`) VALUES
  ('FV-01',  '300L',   '2026-03-15', '2026-03-15', '2026-02-20', '2026-04-01'),
  ('FV-02',  '300L',   '2026-02-10', '2026-02-10', '2026-01-20', '2026-03-01'),
  ('FV-03',  '150L',   '2026-04-01', '2026-04-01', '2026-03-15', '2026-04-10'),
  ('FV-04',  '300L',   '2026-04-01', '2026-04-01', '2026-03-15', '2026-04-10'),
  ('FV-05',  '300L',   '2026-03-20', '2026-03-20', '2026-02-28', '2026-04-05'),
  ('FV-06',  '150L',   '2026-04-10', '2026-04-10', '2026-03-25', '2026-04-15'),
  ('FV-07',  '500L',   '2026-02-15', '2026-02-15', '2026-01-20', '2026-03-01'),
  ('FV-08',  '500L',   '2026-04-05', '2026-04-05', '2026-03-10', '2026-04-12'),
  ('FV-09',  '200L',   '2026-03-10', '2026-03-10', '2026-02-15', '2026-03-20'),
  ('FV-10',  '200L',   '2026-04-15', '2026-04-15', '2026-03-30', '2026-04-20'),
  ('FV-11',  '100L',   '2026-01-20', '2026-01-20', '2025-12-15', '2026-02-01'),
  ('FV-12',  '100L',   '2026-04-20', '2026-04-20', '2026-04-05', '2026-04-22'),
  ('FV-13',  '600L',   '2026-03-01', '2026-03-01', '2026-02-05', '2026-03-10'),
  ('FV-14',  '600L',   '2026-04-08', '2026-04-08', '2026-03-20', '2026-04-15'),
  ('FV-15', '1000L',   '2026-02-01', '2026-02-01', '2026-01-10', '2026-02-15');

-- ── reportesagua ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `reportesagua` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `fecha`        DATE          NOT NULL,
  `laboratorio`  VARCHAR(150)  NOT NULL DEFAULT '',
  `origen`       ENUM('RED','OSMOSIS') NOT NULL DEFAULT 'RED',
  `ph`           VARCHAR(10)   NOT NULL DEFAULT '',
  `sulfato`      INT           NOT NULL DEFAULT 0,
  `nitrato`      INT           NOT NULL DEFAULT 0,
  `nitrito`      INT           NOT NULL DEFAULT 0,
  `dureza`       INT           NOT NULL DEFAULT 0,
  `calcio`       INT           NOT NULL DEFAULT 0,
  `magnesio`     INT           NOT NULL DEFAULT 0,
  `cloruro`      INT           NOT NULL DEFAULT 0,
  `carbonato`    INT           NOT NULL DEFAULT 0,
  `bicarbonato`  INT           NOT NULL DEFAULT 0,
  `sodio`        INT           NOT NULL DEFAULT 0,
  `alcalinidad`  INT           NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `reportesagua` (`fecha`, `laboratorio`, `origen`, `ph`, `sulfato`, `nitrato`, `nitrito`, `dureza`, `calcio`, `magnesio`, `cloruro`, `carbonato`, `bicarbonato`, `sodio`, `alcalinidad`) VALUES
  ('2026-04-01', 'Lab Municipal BKS', 'RED',    '7.2', 45,  5, 0, 180, 62, 14, 50, 0, 120, 20, 98),
  ('2026-04-01', 'Lab Municipal BKS', 'OSMOSIS','6.8',  8,  1, 0,  30, 10,  3,  8, 0,  20,  5, 16),
  ('2026-03-01', 'Lab Municipal BKS', 'RED',    '7.3', 48,  6, 0, 185, 64, 15, 52, 0, 125, 22, 102),
  ('2026-03-01', 'Lab Municipal BKS', 'OSMOSIS','6.9',  9,  1, 0,  28,  9,  3,  7, 0,  18,  4, 15);

-- ── lotes_cerveza ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `lotes_cerveza` (
  `id`                    INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `estilo_id`             INT UNSIGNED  DEFAULT NULL,
  `numero_lote`           VARCHAR(50)   NOT NULL,
  `fecha_elaboracion`     DATE          DEFAULT NULL,
  `og`                    DECIMAL(5,3)  DEFAULT NULL,
  `fg`                    DECIMAL(5,3)  DEFAULT NULL,
  `ibu`                   INT           DEFAULT NULL,
  `abv`                   DECIMAL(4,2)  DEFAULT NULL,
  `co2`                   DECIMAL(4,2)  DEFAULT NULL,
  `ph_mosto`              DECIMAL(3,1)  DEFAULT NULL,
  `ph_fin_fermentacion`   DECIMAL(3,1)  DEFAULT NULL,
  `ca_mas_2`              DECIMAL(6,2)  DEFAULT NULL,
  `mg_mas_2`              DECIMAL(6,2)  DEFAULT NULL,
  `na_mas_2`              DECIMAL(6,2)  DEFAULT NULL,
  `cl_menos`              DECIMAL(6,2)  DEFAULT NULL,
  `so04_menos_2`          DECIMAL(6,2)  DEFAULT NULL,
  `carb_level`            DECIMAL(4,2)  DEFAULT NULL,
  `litros_a_fermentador`  DECIMAL(8,2)  DEFAULT NULL,
  `litros_envasados`      DECIMAL(8,2)  DEFAULT NULL,
  `dia_envasado`          DATE          DEFAULT NULL,
  `DO`                    DECIMAL(5,3)  DEFAULT NULL,
  `DF`                    DECIMAL(5,3)  DEFAULT NULL,
  `fermentador_id`        INT UNSIGNED  DEFAULT NULL,
  `reporteRED`            INT UNSIGNED  DEFAULT NULL,
  `reporteOSMO`           INT UNSIGNED  DEFAULT NULL,
  `comentarios`           TEXT,
  `cata_habilitada`       TINYINT(1)    NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_numero_lote` (`numero_lote`),
  FOREIGN KEY (`estilo_id`)    REFERENCES `estilos_cerveza`(`id`),
  FOREIGN KEY (`fermentador_id`) REFERENCES `fermentadores`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `lotes_cerveza` (`estilo_id`, `numero_lote`, `fecha_elaboracion`, `og`, `fg`, `ibu`, `abv`, `ph_mosto`, `ph_fin_fermentacion`, `litros_a_fermentador`, `litros_envasados`, `dia_envasado`, `fermentador_id`, `reporteRED`, `reporteOSMO`, `carb_level`, `cata_habilitada`, `comentarios`) VALUES
  (1, 'BBC-2026-001', '2026-01-15', 1.066, 1.013, 64, 6.95, 5.4, 4.1, 280.0, 245.0, '2026-02-12', 1, 1, 2, 2.40, 1, 'IPA de arranque de temporada. Muy buena retención de espuma.'),
  (2, 'BBC-2026-002', '2026-02-10', 1.061, 1.015, 41, 5.98, 5.3, 4.2, 290.0, 260.0, '2026-03-10', 2, 3, 4, 2.20, 1, 'Stout invernal. Aromas a chocolate y café bien pronunciados.'),
  (3, 'BBC-2026-003', '2026-03-05', 1.053, 1.011, 34, 5.51, 5.4, 4.1, 285.0, NULL, NULL, 3, 3, 4, 2.50, 0, 'Pale Ale primaveral. En carbonatación.'),
  (1, 'BBC-2026-004', '2026-04-15', NULL,  NULL,  NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'IPA planificada.');

-- ── lotes_maltas ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `lotes_maltas` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `lote_id`     INT UNSIGNED  NOT NULL,
  `malta_id`    INT UNSIGNED  NOT NULL,
  `cantidad`    DECIMAL(8,3)  NOT NULL DEFAULT 0,
  `tiempo`      VARCHAR(50)   NOT NULL DEFAULT '',
  `lote_malta`  VARCHAR(100)  NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`lote_id`)  REFERENCES `lotes_cerveza`(`id`),
  FOREIGN KEY (`malta_id`) REFERENCES `variedades_malta`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `lotes_maltas` (`lote_id`, `malta_id`, `cantidad`, `tiempo`, `lote_malta`) VALUES
  (1, 1, 5.500, '60', 'L001-M01'),
  (1, 3, 0.500, '60', 'L001-M02'),
  (1, 5, 0.450, '60', 'L001-M03'),
  (2, 1, 4.500, '60', 'L002-M01'),
  (2, 4, 0.500, '60', 'L002-M02'),
  (2, 3, 0.600, '60', 'L002-M03'),
  (2, 8, 0.300, '60', 'L002-M04'),
  (3, 1, 4.200, '60', 'L003-M01'),
  (3, 2, 0.500, '60', 'L003-M02'),
  (3, 3, 0.400, '60', 'L003-M03');

-- ── lotes_lupulos ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `lotes_lupulos` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `lote_id`      INT UNSIGNED  NOT NULL,
  `lupulo_id`    INT UNSIGNED  NOT NULL,
  `cantidad`     DECIMAL(8,2)  NOT NULL DEFAULT 0,
  `tiempo`       VARCHAR(50)   NOT NULL DEFAULT '',
  `ibu`          DECIMAL(6,2)  NOT NULL DEFAULT 0,
  `lote_lupulo`  VARCHAR(100)  NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`lote_id`)   REFERENCES `lotes_cerveza`(`id`),
  FOREIGN KEY (`lupulo_id`) REFERENCES `variedades_lupulo`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `lotes_lupulos` (`lote_id`, `lupulo_id`, `cantidad`, `tiempo`, `ibu`, `lote_lupulo`) VALUES
  (1, 5, 30.0, '60', 30.0, 'L001-H01'),
  (1, 1, 40.0, '15', 20.0, 'L001-H02'),
  (1, 3, 60.0, '5',   8.0, 'L001-H03'),
  (1, 4, 60.0, '0',   0.0, 'L001-H04'),
  (2, 5, 35.0, '60', 35.0, 'L002-H01'),
  (2, 1, 20.0, '10',  5.0, 'L002-H02'),
  (3, 1, 25.0, '60', 20.0, 'L003-H01'),
  (3, 2, 30.0, '10', 10.0, 'L003-H02'),
  (3, 3, 40.0, '0',   0.0, 'L003-H03');

-- ── lotes_levaduras ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `lotes_levaduras` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `lote_id`          INT UNSIGNED  NOT NULL,
  `cepa_id`          INT UNSIGNED  NOT NULL,
  `gen`              VARCHAR(20)   NOT NULL DEFAULT '1',
  `temp_inoculacion` DECIMAL(4,1)  NOT NULL DEFAULT 18.0,
  `tasa_inoculacion` DECIMAL(8,4)  NOT NULL DEFAULT 0,
  `viabilidad`       DECIMAL(5,2)  NOT NULL DEFAULT 0,
  `kilos_biomasa`    DECIMAL(6,3)  NOT NULL DEFAULT 0,
  `oxigenacion`      INT           NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`lote_id`) REFERENCES `lotes_cerveza`(`id`),
  FOREIGN KEY (`cepa_id`) REFERENCES `cepas_levadura`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `lotes_levaduras` (`lote_id`, `cepa_id`, `gen`, `temp_inoculacion`, `tasa_inoculacion`, `viabilidad`, `kilos_biomasa`, `oxigenacion`) VALUES
  (1, 1, '2', 19.0, 1.2500, 92.50, 2.500, 10),
  (2, 2, '1', 18.0, 1.0000, 98.00, 2.200, 8),
  (3, 1, '3', 19.0, 1.2500, 88.00, 2.500, 10);

-- ── tratamiento_agua_mash_sparge ─────────────────────────────
CREATE TABLE IF NOT EXISTS `tratamiento_agua_mash_sparge` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lote_id`             INT UNSIGNED NOT NULL,
  `total_agua_mash`     DECIMAL(6,2) DEFAULT NULL,
  `porcentaje_ro_mash`  DECIMAL(5,2) DEFAULT NULL,
  `temperatura_mash`    DECIMAL(4,1) DEFAULT NULL,
  `ph_mash`             DECIMAL(3,1) DEFAULT NULL,
  `caso4_mash`          DECIMAL(6,3) DEFAULT NULL,
  `cacl2_mash`          DECIMAL(6,3) DEFAULT NULL,
  `mgcl_mash`           DECIMAL(6,3) DEFAULT NULL,
  `fosforico_mash`      DECIMAL(6,3) DEFAULT NULL,
  `otro_mash`           DECIMAL(6,3) DEFAULT NULL,
  `fosforico_h2o_mash`  DECIMAL(6,3) DEFAULT NULL,
  `total_agua_sparge`   DECIMAL(6,2) DEFAULT NULL,
  `porcentaje_ro_sparge` DECIMAL(5,2) DEFAULT NULL,
  `temperatura_sparge`  DECIMAL(4,1) DEFAULT NULL,
  `ph_sparge`           DECIMAL(3,1) DEFAULT NULL,
  `caso4_sparge`        DECIMAL(6,3) DEFAULT NULL,
  `cacl2_sparge`        DECIMAL(6,3) DEFAULT NULL,
  `mgcl_sparge`         DECIMAL(6,3) DEFAULT NULL,
  `fosforico_sparge`    DECIMAL(6,3) DEFAULT NULL,
  `otro_sparge`         DECIMAL(6,3) DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`lote_id`) REFERENCES `lotes_cerveza`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `tratamiento_agua_mash_sparge` (`lote_id`, `total_agua_mash`, `porcentaje_ro_mash`, `temperatura_mash`, `ph_mash`, `caso4_mash`, `cacl2_mash`, `mgcl_mash`, `fosforico_mash`, `total_agua_sparge`, `porcentaje_ro_sparge`, `temperatura_sparge`, `ph_sparge`, `caso4_sparge`, `cacl2_sparge`) VALUES
  (1, 160.0, 40.0, 67.0, 5.4, 1.500, 1.200, 0.300, 1.50, 140.0, 30.0, 76.0, 5.8, 1.000, 0.800),
  (2, 155.0, 35.0, 66.0, 5.3, 1.200, 1.000, 0.200, 1.20, 145.0, 25.0, 76.0, 5.7, 0.800, 0.600),
  (3, 158.0, 40.0, 67.5, 5.4, 1.400, 1.100, 0.250, 1.40, 142.0, 30.0, 76.0, 5.8, 0.900, 0.700);

-- ── batches ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `batches` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lote_id`          INT UNSIGNED NOT NULL,
  `temp_mash`        DECIMAL(4,1) DEFAULT NULL,
  `temp2_mash`       DECIMAL(4,1) DEFAULT NULL,
  `temp3_mash`       DECIMAL(4,1) DEFAULT NULL,
  `ph_mash`          DECIMAL(3,1) DEFAULT NULL,
  `ph2_mash`         DECIMAL(3,1) DEFAULT NULL,
  `ph3_mash`         DECIMAL(3,1) DEFAULT NULL,
  `dens_primer_mosto` DECIMAL(5,3) DEFAULT NULL,
  `dens_last_run`    DECIMAL(5,3) DEFAULT NULL,
  `ph_last_run`      DECIMAL(3,1) DEFAULT NULL,
  `temp_sparge`      DECIMAL(4,1) DEFAULT NULL,
  `ph_sparge`        DECIMAL(3,1) DEFAULT NULL,
  `vol_inicial_boil` DECIMAL(6,2) DEFAULT NULL,
  `dens_pre_boil`    DECIMAL(5,3) DEFAULT NULL,
  `ph_inicio_boil`   DECIMAL(3,1) DEFAULT NULL,
  `vol_final_boil`   DECIMAL(6,2) DEFAULT NULL,
  `dens_post_boil`   DECIMAL(5,3) DEFAULT NULL,
  `ph_fin`           DECIMAL(3,1) DEFAULT NULL,
  `batch`            INT          NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`lote_id`) REFERENCES `lotes_cerveza`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `batches` (`lote_id`, `temp_mash`, `temp2_mash`, `ph_mash`, `ph2_mash`, `dens_primer_mosto`, `dens_last_run`, `ph_last_run`, `temp_sparge`, `ph_sparge`, `vol_inicial_boil`, `dens_pre_boil`, `ph_inicio_boil`, `vol_final_boil`, `dens_post_boil`, `ph_fin`, `batch`) VALUES
  (1, 67.2, 67.0, 5.40, 5.38, 1.078, 1.010, 5.8, 76.0, 5.90, 310.0, 1.055, 5.5, 290.0, 1.065, 5.4, 1),
  (2, 66.8, 66.5, 5.35, 5.33, 1.072, 1.008, 5.9, 76.0, 5.95, 315.0, 1.050, 5.4, 295.0, 1.060, 5.3, 1),
  (3, 67.5, 67.2, 5.40, 5.40, 1.064, 1.011, 5.8, 76.0, 5.88, 308.0, 1.044, 5.4, 288.0, 1.053, 5.4, 1);

-- ── seguimiento_fermentacion ──────────────────────────────────
CREATE TABLE IF NOT EXISTS `seguimiento_fermentacion` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lote_id`     INT UNSIGNED NOT NULL,
  `fecha`       DATE         NOT NULL,
  `hora`        VARCHAR(10)  NOT NULL DEFAULT '',
  `densidad`    DECIMAL(5,3) DEFAULT NULL,
  `ph`          DECIMAL(3,1) DEFAULT NULL,
  `temperatura` DECIMAL(4,1) DEFAULT NULL,
  `purga`       VARCHAR(10)  NOT NULL DEFAULT '',
  `comentarios` TEXT,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`lote_id`) REFERENCES `lotes_cerveza`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `seguimiento_fermentacion` (`lote_id`, `fecha`, `hora`, `densidad`, `ph`, `temperatura`, `purga`, `comentarios`) VALUES
  (1, '2026-01-15', '14:00', 1.066, 5.4, 19.0, 'NO',  'Inicio fermentación. Actividad a las 12h.'),
  (1, '2026-01-17', '09:00', 1.042, 4.4, 19.5, 'SI',  'Fermentación activa. Espuma abundante.'),
  (1, '2026-01-19', '09:00', 1.028, 4.2, 19.5, 'NO',  'Bajando densidad normalmente.'),
  (1, '2026-01-22', '09:00', 1.015, 4.1, 20.0, 'NO',  'Casi terminal.'),
  (1, '2026-01-25', '09:00', 1.013, 4.1, 20.0, 'NO',  'FG alcanzada. Iniciando dryhop.'),
  (2, '2026-02-10', '15:00', 1.061, 5.3, 18.0, 'NO',  'Inoculación perfecta.'),
  (2, '2026-02-12', '09:00', 1.038, 4.3, 18.5, 'SI',  'Fermentación muy activa.'),
  (2, '2026-02-15', '09:00', 1.022, 4.2, 18.5, 'NO',  'Progresando bien.'),
  (2, '2026-02-18', '09:00', 1.015, 4.2, 19.0, 'NO',  'Cerca del FG.'),
  (2, '2026-02-22', '09:00', 1.014, 4.2, 19.0, 'NO',  'FG estable. Listo para envasar.'),
  (3, '2026-03-05', '14:00', 1.053, 5.4, 19.0, 'NO',  'Inicio. Todo normal.'),
  (3, '2026-03-07', '09:00', 1.035, 4.4, 19.5, 'SI',  'Fermentación activa.'),
  (3, '2026-03-10', '09:00', 1.020, 4.2, 19.5, 'NO',  'Bajando bien.'),
  (3, '2026-03-14', '09:00', 1.012, 4.1, 20.0, 'NO',  'FG alcanzada. Iniciando carbonatación.');

-- ── lotesenlatado ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `lotesenlatado` (
  `id`                    INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `id_lote`               INT UNSIGNED  NOT NULL,
  `diaEnlatado`           DATE          DEFAULT NULL,
  `presionbarrido`        DECIMAL(5,2)  DEFAULT NULL,
  `presionenenlatadora`   DECIMAL(5,2)  DEFAULT NULL,
  `presionentanque`       DECIMAL(5,2)  DEFAULT NULL,
  `tiempollenado`         INT           DEFAULT NULL,
  `tiempo1`               INT           DEFAULT NULL,
  `tiempo2`               INT           DEFAULT NULL,
  `tempentanque`          DECIMAL(4,1)  DEFAULT NULL,
  `tempenenlatadora`      DECIMAL(4,1)  DEFAULT NULL,
  `tempambiente`          DECIMAL(4,1)  DEFAULT NULL,
  `observacionesenlatado` TEXT,
  `disoxigen`             DECIMAL(5,3)  DEFAULT NULL,
  `tpo`                   DECIMAL(5,3)  DEFAULT NULL,
  `latascerradasDes`      INT           DEFAULT NULL,
  `latasvaciasDes`        INT           DEFAULT NULL,
  `tapasDes`              INT           DEFAULT NULL,
  `latasOK`               INT           DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_lote`) REFERENCES `lotes_cerveza`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `lotesenlatado` (`id_lote`, `diaEnlatado`, `presionbarrido`, `presionenenlatadora`, `presionentanque`, `tiempollenado`, `tiempo1`, `tiempo2`, `tempentanque`, `tempenenlatadora`, `tempambiente`, `observacionesenlatado`, `disoxigen`, `tpo`, `latascerradasDes`, `latasvaciasDes`, `tapasDes`, `latasOK`) VALUES
  (1, '2026-02-12', 1.50, 2.80, 3.20, 4, 2, 3, 2.0, 4.0, 18.0, 'Sin incidentes. Línea limpia.', 0.025, 0.040, 500, 3, 3, 490),
  (2, '2026-03-10', 1.50, 2.75, 3.10, 4, 2, 3, 2.0, 4.0, 17.0, 'Todo normal.', 0.030, 0.045, 510, 2, 2, 500);

-- ── notas_cata ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `notas_cata` (
  `id`                       INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `id_usuario`               INT UNSIGNED  NOT NULL,
  `id_lote`                  INT UNSIGNED  NOT NULL,
  `origen_muestra`           VARCHAR(100)  DEFAULT NULL,
  `tiempo_transcurrido`      INT           DEFAULT NULL,
  `malta_intensidad`         INT           DEFAULT 0,
  `lupulo_intensidad`        INT           DEFAULT 0,
  `esteres_intensidad`       INT           DEFAULT 0,
  `fenoles_intensidad`       INT           DEFAULT 0,
  `alcohol_intensidad`       INT           DEFAULT 0,
  `dulzor_intensidad`        INT           DEFAULT 0,
  `acidez_intensidad`        INT           DEFAULT 0,
  `otros_intensidad`         INT           DEFAULT 0,
  `maltas_atributos`         TEXT,
  `lupulo_atributos`         TEXT,
  `esteres_atributos`        TEXT,
  `otros_atributos`          TEXT,
  `aroma_comentario`         TEXT,
  `aroma_puntaje`            INT           DEFAULT 0,
  `claridad_intensidad`      INT           DEFAULT 0,
  `retencion_intensidad`     INT           DEFAULT 0,
  `tamano_intensidad`        INT           DEFAULT 0,
  `textura_intensidad`       INT           DEFAULT 0,
  `color_cerveza`            VARCHAR(100)  DEFAULT NULL,
  `color_espuma`             VARCHAR(100)  DEFAULT NULL,
  `color_otro`               VARCHAR(100)  DEFAULT NULL,
  `apariencia_comentario`    TEXT,
  `apariencia_puntaje`       INT           DEFAULT 0,
  `sabor_malta_intensidad`   INT           DEFAULT 0,
  `sabor_lupulo_intensidad`  INT           DEFAULT 0,
  `sabor_esteres_intensidad` INT           DEFAULT 0,
  `sabor_fenoles_intensidad` INT           DEFAULT 0,
  `sabor_alcohol_intensidad` INT           DEFAULT 0,
  `sabor_dulzor_intensidad`  INT           DEFAULT 0,
  `sabor_acidez_intensidad`  INT           DEFAULT 0,
  `sabor_otros_intensidad`   INT           DEFAULT 0,
  `sabor_malta_atributos`    TEXT,
  `sabor_lupulo_atributos`   TEXT,
  `sabor_esteres_atributos`  TEXT,
  `sabor_otros_atributos`    TEXT,
  `balance`                  VARCHAR(100)  DEFAULT NULL,
  `sabor_comentario`         TEXT,
  `sabor_puntaje`            INT           DEFAULT 0,
  `cuerpo_intensidad`        INT           DEFAULT 0,
  `carbonatacion_intensidad` INT           DEFAULT 0,
  `calentamiento_intensidad` INT           DEFAULT 0,
  `cremosidad_intensidad`    INT           DEFAULT 0,
  `astringencia_intensidad`  INT           DEFAULT 0,
  `mouthfeel_fallas`         TEXT,
  `mouthfeel_final`          VARCHAR(100)  DEFAULT NULL,
  `mouthfeel_comentario`     TEXT,
  `mouthfeel_puntaje`        INT           DEFAULT 0,
  `impresion_comentario`     TEXT,
  `impresion_puntaje`        INT           DEFAULT 0,
  `fallas`                   TEXT,
  `created_at`               DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_usuario`) REFERENCES `users`(`id`),
  FOREIGN KEY (`id_lote`)    REFERENCES `lotes_cerveza`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── planificacion ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `planificacion` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre`         VARCHAR(100) NOT NULL,
  `estilo_id`      INT UNSIGNED DEFAULT NULL,
  `fermentador_id` INT UNSIGNED DEFAULT NULL,
  `fecha_coccion`  DATE         DEFAULT NULL,
  `fecha_fin`      DATE         DEFAULT NULL,
  `duracion_dias`  INT          DEFAULT NULL,
  `notas`          TEXT,
  `color`          VARCHAR(7)   DEFAULT NULL,
  `estado`         VARCHAR(30)  NOT NULL DEFAULT 'planificado',
  `orden`          INT          NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`estilo_id`)      REFERENCES `estilos_cerveza`(`id`),
  FOREIGN KEY (`fermentador_id`) REFERENCES `fermentadores`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `planificacion` (`nombre`, `estilo_id`, `fermentador_id`, `fecha_coccion`, `fecha_fin`, `duracion_dias`, `notas`, `color`, `estado`) VALUES
  ('IPA Mayo',          1, 1, '2026-05-05', '2026-05-26', 21, 'IPA para la feria de mayo.',      '#e07b39', 'planificado'),
  ('Stout Invierno',    2, 2, '2026-06-02', '2026-06-30', 28, 'Stout imperial de temporada fría.','#5c3317', 'planificado'),
  ('Pale Ale Junio',    3, 3, '2026-06-15', '2026-07-03', 18, 'Pale Ale para consumo local.',    '#f0a500', 'planificado');

-- ── planificacion_tareas ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS `planificacion_tareas` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan_id`        INT UNSIGNED NOT NULL,
  `nombre`         VARCHAR(150) NOT NULL,
  `fecha_estimada` DATE         DEFAULT NULL,
  `orden`          INT          NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`plan_id`) REFERENCES `planificacion`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `planificacion_tareas` (`plan_id`, `nombre`, `fecha_estimada`, `orden`) VALUES
  (1, 'Pedir insumos',      '2026-04-28', 0),
  (1, 'Preparar agua mash', '2026-05-04', 1),
  (1, 'Día de cocción',     '2026-05-05', 2),
  (1, 'Inoculación',        '2026-05-05', 3),
  (1, 'Dryhop',             '2026-05-20', 4),
  (1, 'Envasado',           '2026-05-26', 5),
  (2, 'Pedir insumos',      '2026-05-26', 0),
  (2, 'Día de cocción',     '2026-06-02', 1),
  (2, 'Envasado',           '2026-06-30', 2);

-- ── alertas ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `alertas` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tipo`             VARCHAR(100) NOT NULL,
  `descripcion`      TEXT,
  `periodicidad_dias` INT         NOT NULL DEFAULT 30,
  `ultima_vez`       DATE         DEFAULT NULL,
  `activa`           TINYINT(1)   NOT NULL DEFAULT 1,
  `asignada_a_rol`   VARCHAR(30)  NOT NULL DEFAULT 'admin',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `alertas` (`tipo`, `descripcion`, `periodicidad_dias`, `ultima_vez`, `activa`, `asignada_a_rol`) VALUES
  ('limpieza_fermentador', 'Limpieza alcalina de fermentadores', 30, '2026-03-15', 1, 'elaborador'),
  ('limpieza_fermentador', 'Limpieza ácida de fermentadores',    30, '2026-03-15', 1, 'elaborador'),
  ('reporte_agua',         'Nuevo reporte de agua mensual',      30, '2026-04-01', 1, 'elaborador');

SET FOREIGN_KEY_CHECKS = 1;
