-- =========================
-- SEED DATA FOR WIKIGN
-- =========================

-- Firearm Types
INSERT INTO firearm_type (type) VALUES
('Pistol'),
('Rifle'),
('Shotgun'),
('Submachine Gun'),
('Sniper Rifle');

-- Fire Modes
INSERT INTO fire_mode (mode) VALUES
('Semi-Auto'),
('Full-Auto'),
('Burst'),
('Single Shot');

-- Ammo Brands
INSERT INTO brand (brand) VALUES
('Federal'),
('Hornady'),
('Winchester'),
('Remington'),
('PMC');

-- Ammo
INSERT INTO ammo (calibre, type) VALUES
('9mm', 'FMJ'),
('9mm', 'JHP'),
('.45 ACP', 'FMJ'),
('5.56mm NATO', 'FMJ'),
('7.62mm NATO', 'FMJ'),
('12 Gauge', '00 Buckshot'),
('.308 Win', 'BTHP'),
('.50 AE', 'JHP');

-- Manufacturers
INSERT INTO manufacturer (name, img_path) VALUES
('Glock', 'assets/images/Glock.png'),
('Colt', 'assets/images/Colt_logo.png'),
('Heckler & Koch', 'assets/images/HK_Logo.svg.png'),
('Smith & Wesson', 'assets/images/Smith & Wesson.png'),
('Kalashnikov Concern', 'assets/images/Kalashnikov Concern.png'),
('FN Herstal', 'assets/images/FN_Logo.png'),
('Remington', 'assets/images/Remingtonlogo.svg.png'),
('IMI / Magnum Research', 'assets/images/Magnum Research.png');

-- Attachments
INSERT INTO attachment (name, type, img_path) VALUES
('ACOG 4x Scope', 'Scope', 'assets/images/ACOG 4x Scope.jpg'),
('Red Dot Sight', 'Scope', 'assets/images/Red Dot Sight.jpg'),
('Suppressor 9mm', 'Suppressor', 'assets/images/Suppressor 9mm.jpeg'),
('Suppressor .308', 'Suppressor', 'assets/images/Suppressor .308.jpeg'),
('Vertical Foregrip', 'Grip', 'assets/images/mag412-blk_magpul_rvg_rail_vertical_grip_01.jpg'),
('Flashlight', 'Light', 'assets/images/Flashlight.jpeg'),
('Laser Sight', 'Laser', 'assets/images/laser-sight.jpg'),
('Extended Magazine', 'Magazine', 'assets/images/P226 9mm 20 RD Extended Magazine.jpeg');

-- Pictures
INSERT INTO picture (img_path) VALUES
('assets/images/Glock_17.jpg'),        -- 1
('assets/images/M1911A1.jpg'),         -- 2
('assets/images/M4A1.png'),            -- 3
('assets/images/AK-47.jpg'),           -- 4
('assets/images/mp5a3.jpg'),           -- 5
('assets/images/Remington 870.png'),   -- 6
('assets/images/M24 SWS.jpg'),         -- 7
('assets/images/HK416.png'),           -- 8
('assets/images/desert-eagle.jpg'),    -- 9
('assets/images/UMP45.jpeg');          -- 10

-- Firearms
-- (type_id: 1=Pistol, 2=Rifle, 3=Shotgun, 4=SMG, 5=Sniper)
INSERT INTO firearm (name, rate_of_fire, capacity, effective_range, barrel_length, weight, firearm_type_id) VALUES
('Glock 17',        NULL,  17,  50,   4.49,  0.620, 1),
('M1911A1',         NULL,   7,  50,   5.03,  1.105, 1),
('M4A1',            800,   30, 500,  14.5,   3.000, 2),
('AK-47',           600,   30, 400,  16.3,   4.300, 2),
('MP5A3',           800,   30, 200,   8.85,  2.880, 4),
('Remington 870',   NULL,   8,  50,  18.0,   3.600, 3),
('M24 SWS',         NULL,   5, 800,  24.0,   5.490, 5),
('HK416',           850,   30, 500,  14.5,   3.490, 2),
('Desert Eagle .50',NULL,   7,  50,   6.0,   1.998, 1),
('UMP45',           600,   25, 200,   7.87,  2.270, 4);

-- =========================
-- FIREARM ↔ FIRE MODE
-- =========================
-- Glock 17 (id=1): Semi-Auto
INSERT INTO firearm_fire_mode VALUES (1,1);
-- M1911A1 (id=2): Semi-Auto
INSERT INTO firearm_fire_mode VALUES (2,1);
-- M4A1 (id=3): Semi-Auto, Full-Auto
INSERT INTO firearm_fire_mode VALUES (3,1),(3,2);
-- AK-47 (id=4): Semi-Auto, Full-Auto
INSERT INTO firearm_fire_mode VALUES (4,1),(4,2);
-- MP5A3 (id=5): Semi-Auto, Full-Auto, Burst
INSERT INTO firearm_fire_mode VALUES (5,1),(5,2),(5,3);
-- Remington 870 (id=6): Single Shot
INSERT INTO firearm_fire_mode VALUES (6,4);
-- M24 SWS (id=7): Single Shot
INSERT INTO firearm_fire_mode VALUES (7,4);
-- HK416 (id=8): Semi-Auto, Full-Auto
INSERT INTO firearm_fire_mode VALUES (8,1),(8,2);
-- Desert Eagle (id=9): Semi-Auto
INSERT INTO firearm_fire_mode VALUES (9,1);
-- UMP45 (id=10): Semi-Auto, Full-Auto
INSERT INTO firearm_fire_mode VALUES (10,1),(10,2);

-- =========================
-- FIREARM ↔ AMMO
-- =========================
-- Glock 17: 9mm FMJ (1), 9mm JHP (2)
INSERT INTO firearm_ammo VALUES (1,1),(1,2);
-- M1911A1: .45 ACP FMJ (3)
INSERT INTO firearm_ammo VALUES (2,3);
-- M4A1: 5.56mm NATO (4)
INSERT INTO firearm_ammo VALUES (3,4);
-- AK-47: 7.62mm NATO (5)
INSERT INTO firearm_ammo VALUES (4,5);
-- MP5A3: 9mm FMJ (1), 9mm JHP (2)
INSERT INTO firearm_ammo VALUES (5,1),(5,2);
-- Remington 870: 12 Gauge (6)
INSERT INTO firearm_ammo VALUES (6,6);
-- M24 SWS: .308 Win (7)
INSERT INTO firearm_ammo VALUES (7,7);
-- HK416: 5.56mm NATO (4)
INSERT INTO firearm_ammo VALUES (8,4);
-- Desert Eagle: .50 AE (8)
INSERT INTO firearm_ammo VALUES (9,8);
-- UMP45: .45 ACP FMJ (3)
INSERT INTO firearm_ammo VALUES (10,3);

-- =========================
-- AMMO ↔ BRAND
-- =========================
-- 9mm FMJ: Federal(1), PMC(5)
INSERT INTO ammo_brand VALUES (1,1),(1,5);
-- 9mm JHP: Hornady(2), Federal(1)
INSERT INTO ammo_brand VALUES (2,2),(2,1);
-- .45 ACP: Winchester(3), Federal(1)
INSERT INTO ammo_brand VALUES (3,3),(3,1);
-- 5.56mm: Federal(1), PMC(5)
INSERT INTO ammo_brand VALUES (4,1),(4,5);
-- 7.62mm NATO: Hornady(2), PMC(5)
INSERT INTO ammo_brand VALUES (5,2),(5,5);
-- 12 Gauge: Remington(4), Winchester(3)
INSERT INTO ammo_brand VALUES (6,4),(6,3);
-- .308 Win: Hornady(2), Federal(1)
INSERT INTO ammo_brand VALUES (7,2),(7,1);
-- .50 AE: Hornady(2)
INSERT INTO ammo_brand VALUES (8,2);

-- =========================
-- FIREARM ↔ MANUFACTURER
-- =========================
INSERT INTO firearm_manufacturer VALUES (1,1);  -- Glock 17 → Glock
INSERT INTO firearm_manufacturer VALUES (2,2);  -- M1911 → Colt
INSERT INTO firearm_manufacturer VALUES (3,6);  -- M4A1 → FN Herstal
INSERT INTO firearm_manufacturer VALUES (4,5);  -- AK-47 → Kalashnikov
INSERT INTO firearm_manufacturer VALUES (5,3);  -- MP5 → HK
INSERT INTO firearm_manufacturer VALUES (6,7);  -- Rem 870 → Remington
INSERT INTO firearm_manufacturer VALUES (7,4);  -- M24 → Smith & Wesson
INSERT INTO firearm_manufacturer VALUES (8,3);  -- HK416 → HK
INSERT INTO firearm_manufacturer VALUES (9,8);  -- Desert Eagle → IMI
INSERT INTO firearm_manufacturer VALUES (10,3); -- UMP45 → HK

-- =========================
-- FIREARM ↔ ATTACHMENT
-- =========================
-- Glock 17: Red Dot(2), Suppressor 9mm(3), Flashlight(6), Laser(7), Extended Mag(8)
INSERT INTO firearm_attachment VALUES (1,2),(1,3),(1,6),(1,7),(1,8);
-- M1911: Laser(7)
INSERT INTO firearm_attachment VALUES (2,7);
-- M4A1: ACOG(1), Red Dot(2), Suppressor .308(4), Foregrip(5), Flashlight(6), Laser(7), Extended Mag(8)
INSERT INTO firearm_attachment VALUES (3,1),(3,2),(3,4),(3,5),(3,6),(3,7),(3,8);
-- AK-47: Red Dot(2), Foregrip(5), Flashlight(6), Extended Mag(8)
INSERT INTO firearm_attachment VALUES (4,2),(4,5),(4,6),(4,8);
-- MP5: Suppressor 9mm(3), Red Dot(2), Flashlight(6), Laser(7)
INSERT INTO firearm_attachment VALUES (5,2),(5,3),(5,6),(5,7);
-- Remington 870: Flashlight(6), Laser(7)
INSERT INTO firearm_attachment VALUES (6,6),(6,7);
-- M24: ACOG(1), Suppressor .308(4)
INSERT INTO firearm_attachment VALUES (7,1),(7,4);
-- HK416: ACOG(1), Red Dot(2), Foregrip(5), Flashlight(6), Extended Mag(8)
INSERT INTO firearm_attachment VALUES (8,1),(8,2),(8,5),(8,6),(8,8);
-- Desert Eagle: Laser(7)
INSERT INTO firearm_attachment VALUES (9,7);
-- UMP45: Red Dot(2), Suppressor 9mm(3), Flashlight(6)
INSERT INTO firearm_attachment VALUES (10,2),(10,3),(10,6);

-- =========================
-- FIREARM ↔ PICTURE
-- =========================
INSERT INTO firearm_picture (firearm_id, picture_id) VALUES
(1,1),   -- Glock 17
(2,2),   -- M1911
(3,3),   -- M4A1
(4,4),   -- AK-47
(5,5),   -- MP5
(6,6),   -- Remington 870
(7,7),   -- M24
(8,8),   -- HK416
(9,9),   -- Desert Eagle
(10,10); -- UMP45
