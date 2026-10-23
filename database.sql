-- NUST LostAndFound database


CREATE DATABASE IF NOT EXISTS nust_reconnect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nust_reconnect;

CREATE TABLE IF NOT EXISTS Users (
    userId        INT AUTO_INCREMENT PRIMARY KEY,
    fullName      VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    studentNumber VARCHAR(30) NULL,
    passwordHash  VARCHAR(255) NOT NULL,
    role          ENUM('User','Admin') NOT NULL DEFAULT 'User',
    TimeCreated   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Campus (
    campusId   INT AUTO_INCREMENT PRIMARY KEY,
    campusName VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Location (
    locationId   INT AUTO_INCREMENT PRIMARY KEY,
    locationName VARCHAR(100) NOT NULL,
    campusId     INT NOT NULL,
    FOREIGN KEY (campusId) REFERENCES Campus(campusId) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Categories (
    categoryId   INT AUTO_INCREMENT PRIMARY KEY,
    categoryName VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Items (
    itemId        INT AUTO_INCREMENT PRIMARY KEY,
    userId        INT NOT NULL,
    categoryId    INT NOT NULL,
    locationId    INT NOT NULL,
    type          ENUM('lost','found') NOT NULL,
    title         VARCHAR(150) NOT NULL,
    description   TEXT NOT NULL,
    dateLost      DATE NOT NULL,
    imagePath     VARCHAR(255) NULL,
    proofQuestion VARCHAR(255) NULL,
    proofAnswer   VARCHAR(255) NULL,
    ItemStatus    ENUM('active','resolved') NOT NULL DEFAULT 'active',
    TimeCreated   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userId)     REFERENCES Users(userId)           ON DELETE CASCADE,
    FOREIGN KEY (categoryId) REFERENCES Categories(categoryId),
    FOREIGN KEY (locationId) REFERENCES Location(locationId)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Claims (
    claimId     INT AUTO_INCREMENT PRIMARY KEY,
    itemId      INT NOT NULL,
    claimantId  INT NOT NULL,
    answer      VARCHAR(255) NULL,
    message     VARCHAR(500) NULL,
    ClaimStatus ENUM('pending','completed','rejected','withdrawn') NOT NULL DEFAULT 'pending',
    TimeCreated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (itemId)     REFERENCES Items(itemId) ON DELETE CASCADE,
    FOREIGN KEY (claimantId) REFERENCES Users(userId) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Messages (
    messageId   INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL,
    message     TEXT NOT NULL,
    TimeCreated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Seed data (only run once) ----------
INSERT INTO Campus (campusName) VALUES ('Main Campus'), ('Engineering Campus'), ('Health Sciences Campus');

INSERT INTO Location (locationName, campusId) VALUES
 ('Library', 1), ('Cafeteria', 1), ('Lecture Halls', 1), ('Admin Block', 1),
 ('Sports Field', 1), ('Hostel', 1), ('Parking Area', 1),
 ('Engineering Labs', 2), ('Lecture Halls', 2), ('Cafeteria', 2),
 ('Clinic Building', 3), ('Lecture Halls', 3);

INSERT INTO Categories (categoryName) VALUES
 ('Student Card / ID'), ('Phone'), ('Laptop & Accessories'), ('Keys'),
 ('Wallet & Bank Cards'), ('Bag'), ('Clothing'), ('Books & Stationery'), ('Other');

-- Admin account: admin@nust.local / Admin@12345  (change this password after first login)
INSERT INTO Users (fullName, email, passwordHash, role)
VALUES ('Site Admin', 'admin@nust.local', '$2y$10$wi1cYI16QsWx.PdW81aUQ.HQTzipdbb8CWHiipgr9ckrqxXCOh/.q', 'Admin');

USE nust_reconnect;

-- 1. Make sure both campuses exist (by name, not by id)
INSERT INTO Campus (campusName)
SELECT 'Lower Campus' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM Campus WHERE campusName = 'Lower Campus');

INSERT INTO Campus (campusName)
SELECT 'Upper Campus' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM Campus WHERE campusName = 'Upper Campus');

SET @lower = (SELECT MIN(campusId) FROM Campus WHERE campusName = 'Lower Campus');
SET @upper = (SELECT MIN(campusId) FROM Campus WHERE campusName = 'Upper Campus');

-- 2. Remove any locations already added to these two campuses (prevents duplicates)
DELETE FROM Location WHERE campusId IN (@lower, @upper);

-- 3. Add your locations
INSERT INTO Location (locationName, campusId) VALUES
 ('Library', @lower), ('MTC', @lower), ('Tuckshop', @lower), ('Hotel School', @lower),
 ('Security Gate / Entrance', @lower), ('Parking Area', @lower),
 ('Engineering Building', @lower), ('Science and Tech Building', @lower),
 ('Postgraduate Building', @lower), ('Medleb Building', @lower),
 ('Architecture Building', @lower), ('Old Engineering Building', @lower),
 ('Auditorium Building', @lower), ('Other', @lower),

 ('Lecture Building', @upper), ('Computer Labs', @upper), ('Office Building', @upper),
 ('Student Centre / SRC Office', @upper), ('Admin Offices / Registry', @upper),
 ('Main Gate Entrance', @upper), ('Small Gate Entrance', @upper),
 ('Parking Area', @upper), ('Basketball Court', @upper),
 ('Student Residence / Hostel', @upper), ('Clinic Building', @upper),
 ('PolyHeights', @upper), ('Yellow Benches', @upper), ('Green Benches', @upper),
 ('Other', @upper);

-- 4. Remove leftover empty campuses (for example the old "Health Sciences Campus")
DELETE FROM Campus
WHERE campusName NOT IN ('Lower Campus', 'Upper Campus')
  AND campusId NOT IN (SELECT campusId FROM Location);
