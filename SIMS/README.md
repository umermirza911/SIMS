# Student Information Management System (SIMS)

## 📚 Project Overview

**SIMS** is a secure, full-featured Student Information Management System developed as an academic project to demonstrate comprehensive implementation of **Secure Software Design and Development** principles.

The system manages student records, academic programs, course assignments, and timetables with a strong focus on security, implementing all six foundational security principles: **Confidentiality**, **Integrity**, **Authentication**, **Authorization**, **Audit/Accountability**, and **Availability**.

---

## 🔐 Key Security Features

### 1. **Confidentiality**
- Password hashing using bcrypt
- Secure session management with HttpOnly, Secure, and SameSite flags
- Role-based access control (RBAC)
- Input sanitization to prevent data leakage

### 2. **Integrity**
- Prepared statements (PDO) to prevent SQL injection
- CSRF token validation on all state-changing operations
- Foreign key constraints for referential integrity
- Unique constraints on critical fields (email, registration numbers)

### 3. **Authentication**
- Secure login with email and password
- Session-based authentication with automatic timeout (30 minutes)
- Account lockout after 5 failed login attempts (15-minute cooldown)
- Session regeneration to prevent fixation attacks

### 4. **Authorization**
- Three distinct user roles: MIS Manager, Coordinator, Teacher
- Role-based access enforcement on all pages
- Comprehensive permission checking

### 5. **Audit/Accountability**
- Complete audit logging of all sensitive operations
- IP address and timestamp tracking
- User action tracing
- Filterable audit log viewer for MIS Managers

### 6. **Availability**
- Error handling that doesn't crash the system
- Database connection pooling
- Input validation to prevent malformed data

---

## 🛡️ Defensive Coding Practices Implemented

- ✅ **Input Validation**: All inputs validated (type, length, format)
- ✅ **Sanitization**: XSS prevention via `htmlspecialchars()`
- ✅ **Error Handling**: Generic error messages, detailed logging
- ✅ **Safe APIs**: PDO prepared statements, no dynamic SQL
- ✅ **Session Management**: Secure flags, regeneration, timeout
- ✅ **Cryptography**: bcrypt for passwords
- ✅ **CSRF Protection**: Token-based validation
- ✅ **Exception Management**: Try-catch blocks with safe defaults

---

## 👥 User Roles & Permissions

### MIS Manager
- Create, update, delete departments, programs, and batches
- Register students and teachers
- Manage user accounts (enable/disable)
- View complete audit logs

### Program Coordinator
- View students and academic data
- Assign subjects to teachers
- Manage course offerings and timetable
- Generate reports

### Teacher
- View assigned courses and students
- Access personal timetable
- Read-only access (attendance/results marked as future scope)

---

## 🗂️ Database Schema

### Core Tables
- `users` - All system users with role differentiation
- `departments` → `programs` → `batches` → `students`
- `subjects` - Course catalog
- `subject_assignments` - Teacher to subject to batch mapping
- `timetable` - Class schedules

### Security Tables
- `audit_logs` - Complete activity tracking
- `login_attempts` - Failed login tracking for lockout
- `sessions` - Server-side session storage

---

## 🚀 Installation & Setup

### Prerequisites
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Web server (Apache/Nginx) or PHP built-in server

### Step 1: Database Setup
```bash
# Create database and import schema
mysql -u root -p < database/database.sql
```

### Step 2: Configure Database Connection
Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'sims_db');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
```

### Step 3: Start Web Server

**Option A: PHP Built-in Server**
```bash
cd warped-planetary/public
php -S localhost:8000
```

**Option B: Apache/Nginx**
- Point document root to `public/` directory
- Ensure `.htaccess` or equivalent rewrite rules are enabled

### Step 4: Access the System
Navigate to: `http://localhost:8000/login.php`

---

## 🔑 Default Login Credentials

| Role | Email | Password |
|------|-------|----------|
| **MIS Manager** | admin@sims.edu | admin123 |
| **Coordinator** | coordinator@sims.edu | admin123 |
| **Teacher** | teacher@sims.edu | admin123 |

> ⚠️ **IMPORTANT**: Change these passwords after first login in a production environment!

---

## 📁 Project Structure

```
warped-planetary/
├── config/
│   ├── database.php          # Database configuration
│   └── security.php           # Security settings
├── includes/
│   ├── auth.php               # Authentication functions
│   ├── csrf.php               # CSRF protection
│   ├── validation.php         # Input validation
│   ├── audit.php              # Audit logging
│   ├── header.php             # Page header component
│   └── footer.php             # Page footer component
├── public/
│   ├── index.php              # Main entry point
│   ├── login.php              # Login page
│   ├── logout.php             # Logout handler
│   └── assets/
│       ├── css/style.css      # Premium design system
│       └── js/main.js         # Client-side utilities
├── mis_manager/
│   ├── dashboard.php
│   ├── departments.php
│   ├── programs.php
│   ├── batches.php
│   ├── students.php
│   ├── teachers.php
│   ├── users.php
│   └── logs.php
├── coordinator/
│   ├── dashboard.php
│   ├── view_students.php
│   ├── subject_assignments.php
│   ├── course_offerings.php
│   ├── timetable.php
│   └── reports.php
├── teacher/
│   ├── dashboard.php
│   ├── my_courses.php
│   ├── students.php
│   └── schedule.php
├── database/
│   └── database.sql           # Complete schema
└── docs/
    ├── risk_assessment.md
    └── security_controls.md
```

---

## 🧪 Testing

### Security Tests Performed
1. ✅ SQL Injection prevention (parameterized queries)
2. ✅ XSS prevention (output escaping)
3. ✅ CSRF protection (token validation)
4. ✅ Session security (timeout, regeneration)
5. ✅ RBAC enforcement (unauthorized access prevention)
6. ✅ Account lockout (brute force protection)

### Functional Tests
- Complete CRUD operations for all entities
- Role-based navigation and access
- Audit logging verification
- Data integrity constraints

---

## 📖 Documentation

- **[Risk Assessment](docs/risk_assessment.md)** - Threat analysis and mitigation strategies
- **[Security Controls](docs/security_controls.md)** - Detailed security implementation

---

## 🛠️ Technology Stack

- **Backend**: PHP 8.x
- **Database**: MySQL 8.x
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Security**: PDO, bcrypt, CSRF tokens, Session management
- **Design**: Custom CSS with dark theme and glassmorphism

---

## 📝 License & Academic Use

This project is developed for academic purposes as part of a Secure Software Design course. It demonstrates industry-level security practices suitable for educational evaluation and learning.

**Developer**: Student Project  
**Instructor**: Allah Rakha  
**Course**: Secure Software Development

---

## 🎯 Project Achievements

✅ Implemented all six security principles (CIA + AAA)  
✅ Applied defensive coding practices throughout  
✅ Complete SDLC documentation  
✅ Role-based access control with three distinct roles  
✅ Comprehensive audit logging  
✅ Modern, premium UI/UX design  
✅ Database security with prepared statements  
✅ Input validation and sanitization  
✅ Error handling with psychological acceptability  
✅ Session security with timeout and regeneration  

---

## 🙏 Acknowledgments

Special thanks to **Allah Rakha** for comprehensive guidance on secure software development principles and best practices.
