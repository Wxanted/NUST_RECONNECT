-- NUST Reconnect database


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
    ItemStatus    ENUM('pending','approved','rejected','resolved') NOT NULL DEFAULT 'pending',
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
    ClaimStatus ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
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
VALUES ('Site Admin', 'admin@nust.local', 'admin2026', 'Admin');
