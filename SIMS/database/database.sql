-- =====================================================
-- Student Information Management System (SIMS)
-- Database Schema - MySQL 8.x
-- =====================================================

-- Drop existing database if exists (for fresh installation)
DROP DATABASE IF EXISTS sims_db;
CREATE DATABASE sims_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sims_db;

-- =====================================================
-- USER MANAGEMENT
-- =====================================================

-- Users table: handles MIS Manager, Coordinator, and Teacher roles
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- bcrypt hash
    role ENUM('mis_manager', 'coordinator', 'teacher') NOT NULL,
    department_id INT DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- Login attempts tracking for account lockout
CREATE TABLE login_attempts (
    attempt_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success BOOLEAN DEFAULT FALSE,
    INDEX idx_email_time (email, attempted_at)
) ENGINE=InnoDB;

-- Session management (server-side session storage)
CREATE TABLE sessions (
    session_id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_activity (last_activity)
) ENGINE=InnoDB;

-- =====================================================
-- ACADEMIC STRUCTURE
-- =====================================================

-- Departments
CREATE TABLE departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Programs (Degree programs belonging to departments)
CREATE TABLE programs (
    program_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    level ENUM('Undergraduate', 'Graduate', 'Postgraduate') NOT NULL,
    duration_years INT NOT NULL,
    department_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE RESTRICT,
    INDEX idx_department (department_id)
) ENGINE=InnoDB;

-- Batches (Student cohorts)
CREATE TABLE batches (
    batch_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL, -- e.g., "Fall 2024"
    start_year INT NOT NULL,
    end_year INT NOT NULL,
    program_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES programs(program_id) ON DELETE RESTRICT,
    UNIQUE KEY unique_batch_program (name, program_id),
    INDEX idx_program (program_id),
    INDEX idx_years (start_year, end_year)
) ENGINE=InnoDB;

-- Students
CREATE TABLE students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    reg_number VARCHAR(50) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    date_of_birth DATE,
    batch_id INT NOT NULL,
    current_semester INT DEFAULT 1,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES batches(batch_id) ON DELETE RESTRICT,
    INDEX idx_batch (batch_id),
    INDEX idx_email (email),
    INDEX idx_reg (reg_number)
) ENGINE=InnoDB;

-- =====================================================
-- ACADEMIC MANAGEMENT
-- =====================================================

-- Subjects/Courses catalog
CREATE TABLE subjects (
    subject_id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    credit_hours INT NOT NULL,
    department_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE RESTRICT,
    INDEX idx_code (code),
    INDEX idx_department (department_id)
) ENGINE=InnoDB;

-- Course offerings (which subjects are offered in which semester/year)
CREATE TABLE course_offerings (
    offering_id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    program_id INT NOT NULL,
    semester INT NOT NULL, -- 1, 2, 3, etc.
    academic_year VARCHAR(20) NOT NULL, -- e.g., "2024-2025"
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
    FOREIGN KEY (program_id) REFERENCES programs(program_id) ON DELETE CASCADE,
    UNIQUE KEY unique_offering (subject_id, program_id, semester, academic_year),
    INDEX idx_subject (subject_id),
    INDEX idx_program (program_id)
) ENGINE=InnoDB;

-- Subject assignments (teacher to subject to batch)
CREATE TABLE subject_assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    batch_id INT NOT NULL,
    semester INT NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (batch_id) REFERENCES batches(batch_id) ON DELETE CASCADE,
    UNIQUE KEY unique_assignment (subject_id, batch_id, semester, academic_year),
    INDEX idx_teacher (teacher_id),
    INDEX idx_subject (subject_id),
    INDEX idx_batch (batch_id)
) ENGINE=InnoDB;

-- Timetable/Schedule
CREATE TABLE timetable (
    timetable_id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    room VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES subject_assignments(assignment_id) ON DELETE CASCADE,
    INDEX idx_assignment (assignment_id),
    INDEX idx_day (day_of_week)
) ENGINE=InnoDB;

-- =====================================================
-- SECURITY & AUDIT
-- =====================================================

-- Audit logs for accountability
CREATE TABLE audit_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL, -- e.g., "LOGIN", "CREATE_STUDENT", "DELETE_DEPARTMENT"
    resource VARCHAR(100), -- e.g., "students", "departments"
    resource_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_timestamp (created_at)
) ENGINE=InnoDB;

-- =====================================================
-- INITIAL DATA
-- =====================================================

-- Create default MIS Manager account
-- Password: admin123 (hashed with bcrypt)
-- IMPORTANT: Change this password after first login!
INSERT INTO users (name, email, password, role, is_active) VALUES
('System Administrator', 'admin@sims.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mis_manager', TRUE);

-- Sample departments
INSERT INTO departments (name) VALUES
('Computer Science'),
('Electrical Engineering'),
('Business Administration');

-- Sample programs
INSERT INTO programs (name, level, duration_years, department_id) VALUES
('BS Computer Science', 'Undergraduate', 4, 1),
('BS Electrical Engineering', 'Undergraduate', 4, 2),
('MBA', 'Graduate', 2, 3);

-- Sample batches
INSERT INTO batches (name, start_year, end_year, program_id) VALUES
('Fall 2024', 2024, 2028, 1),
('Spring 2024', 2024, 2028, 1),
('Fall 2024', 2024, 2028, 2);

-- Sample subjects
INSERT INTO subjects (code, name, credit_hours, department_id) VALUES
('CS101', 'Introduction to Programming', 3, 1),
('CS201', 'Data Structures', 3, 1),
('CS301', 'Database Systems', 3, 1),
('EE101', 'Circuit Analysis', 3, 2),
('BUS101', 'Principles of Management', 3, 3);

-- Sample coordinator
INSERT INTO users (name, email, password, role, department_id, is_active) VALUES
('Dr. Umar Coordinator', 'coordinator@sims.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'coordinator', 1, TRUE);

-- Sample teacher
INSERT INTO users (name, email, password, role, department_id, is_active) VALUES
('Prof. Saif jutt Teacher', 'teacher@sims.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 1, TRUE);

-- Sample students
INSERT INTO students (reg_number, first_name, last_name, email, date_of_birth, batch_id, current_semester) VALUES
('2024-CS-001', 'Alice', 'Johnson', 'alice.johnson@student.sims.edu', '2005-03-15', 1, 1),
('2024-CS-002', 'Bob', 'Smith', 'bob.smith@student.sims.edu', '2005-07-22', 1, 1),
('2024-CS-003', 'Charlie', 'Brown', 'charlie.brown@student.sims.edu', '2005-11-10', 2, 1);

-- Sample subject assignment
INSERT INTO subject_assignments (subject_id, teacher_id, batch_id, semester, academic_year) VALUES
(1, 3, 1, 1, '2024-2025');

-- Sample timetable entry
INSERT INTO timetable (assignment_id, day_of_week, start_time, end_time, room) VALUES
(1, 'Monday', '09:00:00', '10:30:00', 'Room 101');

-- Log initial setup
INSERT INTO audit_logs (user_id, action, resource, details, ip_address) VALUES
(1, 'SYSTEM_INIT', 'database', 'Initial database setup completed', '127.0.0.1');

-- =====================================================
-- VIEWS FOR REPORTING (Optional - for easier queries)
-- =====================================================

-- View: Complete student information with batch and program details
CREATE VIEW v_student_details AS
SELECT 
    s.student_id,
    s.reg_number,
    CONCAT(s.first_name, ' ', s.last_name) AS full_name,
    s.email,
    s.current_semester,
    s.is_active,
    b.name AS batch_name,
    p.name AS program_name,
    d.name AS department_name
FROM students s
JOIN batches b ON s.batch_id = b.batch_id
JOIN programs p ON b.program_id = p.program_id
JOIN departments d ON p.department_id = d.department_id;

-- View: Teacher workload
CREATE VIEW v_teacher_workload AS
SELECT 
    u.user_id,
    u.name AS teacher_name,
    COUNT(sa.assignment_id) AS total_assignments,
    GROUP_CONCAT(DISTINCT subj.name SEPARATOR ', ') AS subjects_taught
FROM users u
LEFT JOIN subject_assignments sa ON u.user_id = sa.teacher_id
LEFT JOIN subjects subj ON sa.subject_id = subj.subject_id
WHERE u.role = 'teacher'
GROUP BY u.user_id, u.name;

-- View: Complete timetable with all details
CREATE VIEW v_timetable_complete AS
SELECT 
    t.timetable_id,
    t.day_of_week,
    t.start_time,
    t.end_time,
    t.room,
    subj.code AS subject_code,
    subj.name AS subject_name,
    u.name AS teacher_name,
    b.name AS batch_name,
    p.name AS program_name
FROM timetable t
JOIN subject_assignments sa ON t.assignment_id = sa.assignment_id
JOIN subjects subj ON sa.subject_id = subj.subject_id
JOIN users u ON sa.teacher_id = u.user_id
JOIN batches b ON sa.batch_id = b.batch_id
JOIN programs p ON b.program_id = p.program_id;

-- =====================================================
-- END OF SCHEMA
-- =====================================================
