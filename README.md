# Project ERP 🎓

A modern, web-based College Management System designed to digitize administrative and academic operations.

## 🚀 Features
- **Role-Based Access**: 4 distinct dashboards for Admin, Teacher, Student, and Parent.
- **Admission Management**: Digital workflow for student applications and approval.
- **Attendance Tracking**: Subject-wise attendance with heatmap analytics.
- **Marks & Results**: Automated grade calculation and PDF marksheet generation.
- **Fee Management**: Track collections, pending dues, and auto-generate receipts.
- **Certificate Generation**: Online application and auto-generation of Bonafide, TC, etc.
- **Real-time Analytics**: Admin dashboard with live charts and activity feeds.

## 🛠️ Tech Stack
- **Frontend**: HTML5, CSS3, Bootstrap 5, JavaScript, Chart.js
- **Backend**: PHP 8.1
- **Database**: MySQL 8.0
- **UI Theme**: Professional Dark Navy Blue (#0b1120)

## 📦 Installation
1. **Local Server**: Install XAMPP or WAMP.
2. **Database Setup**:
   - Open `phpMyAdmin`.
   - Create a new database named `smart_college_erp`.
   - Import the `schema.sql` file provided in the root directory.
3. **Configuration**:
   - Ensure the database credentials in `config/db.php` match your local setup.
4. **Run Application**:
   - Place the project folder in `htdocs` (XAMPP) or `www` (WAMP).
   - Access via `http://localhost/ERP_SYSTEM/login.php`.

## 🔑 Default Credentials (Admin)
- **Email**: `admin@college.com`
- **Password**: `admin123`
- **Role**: Admin

## 📁 Project Structure
- `/admin`, `/teacher`, `/student`, `/parent`: Role-specific modules.
- `/auth`: Login/session logic.
- `/assets`: CSS, JS, and high-fidelity themes.
- `/config`: Cloud-ready DB connections.

## ☁️ Local Setup Script
For a one-click database setup after installation, simply navigate to:
`http://localhost/ERP_SYSTEM/setup.php`

---
*Created with High-Fidelity Engineering for Project ERP.*
