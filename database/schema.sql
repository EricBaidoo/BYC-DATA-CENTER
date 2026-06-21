-- BYC Data Center Database Schema
-- Relational Services & Sessions
-- Designed for MySQL

CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    email VARCHAR(255),
    address TEXT,
    birthday DATE,
    gender VARCHAR(20),
    join_date DATE,
    department_id INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    date DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS member_attendance (
    member_id INT,
    attendance_id INT,
    status VARCHAR(50) NOT NULL, -- 'Present' or 'Absent'
    PRIMARY KEY (member_id, attendance_id),
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (attendance_id) REFERENCES attendance(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default departments if they don't exist
INSERT IGNORE INTO departments (name, description) VALUES ('Choir', 'Praise and Worship Team');
INSERT IGNORE INTO departments (name, description) VALUES ('Media & Tech', 'Audio, Visual, and Social Media Team');
INSERT IGNORE INTO departments (name, description) VALUES ('Protocol & Ushers', 'Greeting and seating members');
INSERT IGNORE INTO departments (name, description) VALUES ('Children Ministry', 'Teaching and managing the kids section');
INSERT IGNORE INTO departments (name, description) VALUES ('None', 'General membership / No department assigned');

-- Seed default services if they don't exist
INSERT IGNORE INTO services (name, description) VALUES ('Sunday Worship Service', 'Main weekly church congregational service');
INSERT IGNORE INTO services (name, description) VALUES ('Wednesday Communion Service', 'Midweek prayer and holy communion service');
INSERT IGNORE INTO services (name, description) VALUES ('Friday Youth Cell Meeting', 'Weekly youth fellowship and Bible study');
