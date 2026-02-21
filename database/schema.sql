-- Database Schema for KKYF Fellowship Management System
-- Verified for component 001

-- 1. Tents Table (Lookup for Fellowship Centers)
CREATE TABLE IF NOT EXISTS Tents (
    Tent_ID INT AUTO_INCREMENT PRIMARY KEY,
    Tent_Name VARCHAR(100) NOT NULL UNIQUE,
    Location_Description VARCHAR(255)
) ENGINE=InnoDB;

-- 2. Admin_User Table (Authentication)
-- Renamed from Admin_Users to singular to match login.php
CREATE TABLE IF NOT EXISTS Admin_User (
    ID INT AUTO_INCREMENT PRIMARY KEY, -- Renamed from Admin_ID
    Username VARCHAR(50) NOT NULL UNIQUE,
    Email VARCHAR(255) UNIQUE, -- Added Phase 8
    Password_Hash VARCHAR(255) NOT NULL,
    Role ENUM('Super Admin', 'Tent Admin') NOT NULL,
    Assigned_Tent_ID INT NULL,
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Assigned_Tent_ID) REFERENCES Tents(Tent_ID) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 3. Sessions Table (Academic/Fellowship Years)
CREATE TABLE IF NOT EXISTS Sessions (
    Session_ID INT AUTO_INCREMENT PRIMARY KEY,
    Session_Name VARCHAR(50) NOT NULL, -- e.g., "2025/2026"
    Start_Date DATE NOT NULL,
    End_Date DATE NULL,
    Is_Active BOOLEAN DEFAULT 1
) ENGINE=InnoDB;

-- 4. Members Table (Core Profile Data)
CREATE TABLE IF NOT EXISTS Members (
    Member_ID INT AUTO_INCREMENT PRIMARY KEY,
    Member_UUID VARCHAR(36) NOT NULL UNIQUE, -- For decentralized sync/genericity
    Full_Name VARCHAR(100) NOT NULL,
    Status ENUM('Student', 'Worker', 'Alumni') NOT NULL DEFAULT 'Student',
    School VARCHAR(100) NULL, -- Conditional: Only if Status = Student
    Birthdate DATE NULL,
    Phone VARCHAR(20) NULL,
    Address TEXT NULL,
    Current_Tent_ID INT NOT NULL,
    Join_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Current_Tent_ID) REFERENCES Tents(Tent_ID)
) ENGINE=InnoDB;

-- 5. Attendance_Log (Transactional Data)
CREATE TABLE IF NOT EXISTS Attendance_Log (
    Log_ID INT AUTO_INCREMENT PRIMARY KEY,
    Member_UUID VARCHAR(36) NOT NULL,
    Session_ID INT NOT NULL,
    Tent_ID INT NOT NULL, -- Recorded at time of attendance to track history
    Attendance_Date DATE NOT NULL,
    Is_First_Timer BOOLEAN DEFAULT 0,
    Check_In_Time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Member_UUID) REFERENCES Members(Member_UUID) ON DELETE CASCADE,
    FOREIGN KEY (Session_ID) REFERENCES Sessions(Session_ID),
    FOREIGN KEY (Tent_ID) REFERENCES Tents(Tent_ID),
    UNIQUE KEY unique_attendance (Member_UUID, Attendance_Date) -- Prevent duplicate scans
) ENGINE=InnoDB;

-- 6. Audit_Log (Security & Tracking)
CREATE TABLE IF NOT EXISTS Audit_Log (
    Log_ID INT AUTO_INCREMENT PRIMARY KEY,
    Admin_ID INT NULL,
    Action_Type VARCHAR(50) NOT NULL, -- e.g., "LOGIN", "UPDATE_MEMBER", "TENT_TRANSFER"
    Details TEXT NULL,
    IP_Address VARCHAR(45) NULL,
    Timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Admin_ID) REFERENCES Admin_User(ID) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Initial Seed Data: Tents (10 Specific Tents)
INSERT IGNORE INTO Tents (Tent_Name) VALUES 
('Amazing'),
('Exceptional'),
('Elevators'),
('Generals'),
('Highflyers'),
('House of Eden'),
('Lacasa De Kratos'),
('Otis'),
('Pathfinders'),
('Seal of Love');

-- Initial Seed Data: Default Active Session
('2026', CURDATE(), 1);

-- 7. Password_Resets (Added Phase 8)
CREATE TABLE IF NOT EXISTS Password_Resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (token),
    INDEX (email)
) ENGINE=InnoDB;
