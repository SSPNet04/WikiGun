-- Auto-run by MySQL container on first start.
-- Creates all tables then seeds sample data.

USE wikign;

-- =========================
-- CORE TABLES
-- =========================

CREATE TABLE IF NOT EXISTS firearm_type (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS fire_mode (
    id INT PRIMARY KEY AUTO_INCREMENT,
    mode VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS brand (
    id INT PRIMARY KEY AUTO_INCREMENT,
    brand VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS ammo (
    id INT PRIMARY KEY AUTO_INCREMENT,
    calibre VARCHAR(50),
    type VARCHAR(50)
);

CREATE TABLE IF NOT EXISTS manufacturer (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    img_path TEXT
);

CREATE TABLE IF NOT EXISTS attachment (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50),
    img_path TEXT
);

CREATE TABLE IF NOT EXISTS picture (
    id INT PRIMARY KEY AUTO_INCREMENT,
    img_path TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS firearm (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    rate_of_fire FLOAT,
    capacity INT,
    effective_range FLOAT,
    barrel_length FLOAT,
    weight FLOAT,
    firearm_type_id INT,
    FOREIGN KEY (firearm_type_id) REFERENCES firearm_type(id)
);

-- =========================
-- RELATION TABLES
-- =========================

CREATE TABLE IF NOT EXISTS firearm_fire_mode (
    firearm_id INT,
    fire_mode_id INT,
    PRIMARY KEY (firearm_id, fire_mode_id),
    FOREIGN KEY (firearm_id)   REFERENCES firearm(id)    ON DELETE CASCADE,
    FOREIGN KEY (fire_mode_id) REFERENCES fire_mode(id)  ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS firearm_ammo (
    firearm_id INT,
    ammo_id INT,
    PRIMARY KEY (firearm_id, ammo_id),
    FOREIGN KEY (firearm_id) REFERENCES firearm(id) ON DELETE CASCADE,
    FOREIGN KEY (ammo_id)    REFERENCES ammo(id)    ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS ammo_brand (
    ammo_id INT,
    brand_id INT,
    PRIMARY KEY (ammo_id, brand_id),
    FOREIGN KEY (ammo_id)  REFERENCES ammo(id)   ON DELETE CASCADE,
    FOREIGN KEY (brand_id) REFERENCES brand(id)  ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS firearm_attachment (
    firearm_id INT,
    attachment_id INT,
    PRIMARY KEY (firearm_id, attachment_id),
    FOREIGN KEY (firearm_id)   REFERENCES firearm(id)     ON DELETE CASCADE,
    FOREIGN KEY (attachment_id) REFERENCES attachment(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS firearm_manufacturer (
    firearm_id INT,
    manufacturer_id INT,
    PRIMARY KEY (firearm_id, manufacturer_id),
    FOREIGN KEY (firearm_id)     REFERENCES firearm(id)      ON DELETE CASCADE,
    FOREIGN KEY (manufacturer_id) REFERENCES manufacturer(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS firearm_picture (
    id INT PRIMARY KEY AUTO_INCREMENT,
    firearm_id INT NOT NULL,
    picture_id INT NOT NULL,
    FOREIGN KEY (firearm_id) REFERENCES firearm(id) ON DELETE CASCADE,
    FOREIGN KEY (picture_id) REFERENCES picture(id) ON DELETE CASCADE
);

-- =========================
-- SEED DATA
-- =========================

INSERT INTO firearm_type (type) VALUES
('Pistol'), ('Rifle'), ('Shotgun'), ('Submachine Gun'), ('Sniper Rifle');

INSERT INTO fire_mode (mode) VALUES
('Semi-Auto'), ('Full-Auto'), ('Burst'), ('Single Shot');

INSERT INTO brand (brand) VALUES
('Federal'), ('Hornady'), ('Winchester'), ('Remington'), ('PMC');

INSERT INTO ammo (calibre, type) VALUES
('9mm',          'FMJ'),
('9mm',          'JHP'),
('.45 ACP',      'FMJ'),
('5.56mm NATO',  'FMJ'),
('7.62mm NATO',  'FMJ'),
('12 Gauge',     '00 Buckshot'),
('.308 Win',     'BTHP'),
('.50 AE',       'JHP');

INSERT INTO manufacturer (name, img_path) VALUES
('Glock',                'assets/images/mfr_glock.jpg'),
('Colt',                 'assets/images/mfr_colt.jpg'),
('Heckler & Koch',       'assets/images/mfr_hk.jpg'),
('Smith & Wesson',       'assets/images/mfr_sw.jpg'),
('Kalashnikov Concern',  'assets/images/mfr_ak.jpg'),
('FN Herstal',           'assets/images/mfr_fn.jpg'),
('Remington',            'assets/images/mfr_remington.jpg'),
('IMI / Magnum Research','assets/images/mfr_imi.jpg');

INSERT INTO attachment (name, type, img_path) VALUES
('ACOG 4x Scope',    'Scope',      'assets/images/att_acog.jpg'),
('Red Dot Sight',    'Scope',      'assets/images/att_rds.jpg'),
('Suppressor 9mm',   'Suppressor', 'assets/images/att_sup9mm.jpg'),
('Suppressor .308',  'Suppressor', 'assets/images/att_sup308.jpg'),
('Vertical Foregrip','Grip',       'assets/images/att_foregrip.jpg'),
('Flashlight',       'Light',      'assets/images/att_flashlight.jpg'),
('Laser Sight',      'Laser',      'assets/images/att_laser.jpg'),
('Extended Magazine', 'Magazine',  'assets/images/att_extmag.jpg');

INSERT INTO picture (img_path) VALUES
('assets/images/glock17_1.jpg'),
('assets/images/glock17_2.jpg'),
('assets/images/m1911_1.jpg'),
('assets/images/m4a1_1.jpg'),
('assets/images/m4a1_2.jpg'),
('assets/images/ak47_1.jpg'),
('assets/images/ak47_2.jpg'),
('assets/images/mp5_1.jpg'),
('assets/images/rem870_1.jpg'),
('assets/images/m24_1.jpg'),
('assets/images/hk416_1.jpg'),
('assets/images/deagle_1.jpg'),
('assets/images/ump45_1.jpg');

INSERT INTO firearm (name, rate_of_fire, capacity, effective_range, barrel_length, weight, firearm_type_id) VALUES
('Glock 17',         NULL, 17,  50, 4.49, 0.620, 1),
('M1911A1',          NULL,  7,  50, 5.03, 1.105, 1),
('M4A1',              800, 30, 500,14.5,  3.000, 2),
('AK-47',             600, 30, 400,16.3,  4.300, 2),
('MP5A3',             800, 30, 200, 8.85, 2.880, 4),
('Remington 870',    NULL,  8,  50,18.0,  3.600, 3),
('M24 SWS',          NULL,  5, 800,24.0,  5.490, 5),
('HK416',             850, 30, 500,14.5,  3.490, 2),
('Desert Eagle .50', NULL,  7,  50, 6.0,  1.998, 1),
('UMP45',             600, 25, 200, 7.87, 2.270, 4);

-- FIREARM ↔ FIRE MODE
INSERT INTO firearm_fire_mode VALUES
(1,1),(2,1),(3,1),(3,2),(4,1),(4,2),
(5,1),(5,2),(5,3),(6,4),(7,4),
(8,1),(8,2),(9,1),(10,1),(10,2);

-- FIREARM ↔ AMMO
INSERT INTO firearm_ammo VALUES
(1,1),(1,2),(2,3),(3,4),(4,5),(5,1),(5,2),
(6,6),(7,7),(8,4),(9,8),(10,3);

-- AMMO ↔ BRAND
INSERT INTO ammo_brand VALUES
(1,1),(1,5),(2,2),(2,1),(3,3),(3,1),
(4,1),(4,5),(5,2),(5,5),(6,4),(6,3),
(7,2),(7,1),(8,2);

-- FIREARM ↔ MANUFACTURER
INSERT INTO firearm_manufacturer VALUES
(1,1),(2,2),(3,6),(4,5),(5,3),(6,7),(7,4),(8,3),(9,8),(10,3);

-- FIREARM ↔ ATTACHMENT
INSERT INTO firearm_attachment VALUES
(1,2),(1,3),(1,6),(1,7),(1,8),
(2,7),
(3,1),(3,2),(3,4),(3,5),(3,6),(3,7),(3,8),
(4,2),(4,5),(4,6),(4,8),
(5,2),(5,3),(5,6),(5,7),
(6,6),(6,7),
(7,1),(7,4),
(8,1),(8,2),(8,5),(8,6),(8,8),
(9,7),
(10,2),(10,3),(10,6);

-- FIREARM ↔ PICTURE
INSERT INTO firearm_picture (firearm_id, picture_id) VALUES
(1,1),(1,2),(2,3),(3,4),(3,5),(4,6),(4,7),
(5,8),(6,9),(7,10),(8,11),(9,12),(10,13);
