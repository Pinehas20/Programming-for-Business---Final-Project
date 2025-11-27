# HRMS - Human Resources Management System

## Overview
A comprehensive PHP-based Human Resources Management System with overtime (lembur) management as the core feature, expanded to include attendance tracking, leave requests, payslip viewing, inbox messaging, and a home dashboard with KPI progress tracking and company feed. The system uses Indonesian language interface with role-based access for employees and administrators.

## Features
### Core Features
- **Home Dashboard**: Quick shortcuts, KPI progress diagrams, company feed
- **Attendance Management**: WFO (Work From Office) / WFH (Work From Home) check-in/out tracking
- **Overtime Management**: Employee submission with admin approval workflow
- **Leave Requests**: Multiple leave types (sick, annual, personal, etc.)
- **Payslip**: Monthly salary slip viewing with detailed breakdown
- **Inbox**: Internal messaging system from HR/Admin to employees

### Admin Features
- Manage overtime requests (approve/reject)
- Manage leave requests (approve/reject)
- View all employee attendance records
- Send broadcast messages to employees

## Project Structure
```
├── config/
│   ├── database.php      # Database connection class
│   └── init_db.php       # Database initialization and migrations
├── includes/
│   ├── header.php        # Common header with navigation
│   ├── footer.php        # Common footer
│   └── session.php       # Session management utilities
├── css/
│   └── style.css         # Custom styles
├── js/
│   └── app.js            # JavaScript functions
├── images/
│   └── logo.png          # HRMS Logo
├── index.php             # Entry point (redirects to login/home)
├── login.php             # Login page
├── logout.php            # Logout handler
├── home.php              # Main dashboard with KPI & shortcuts
├── dashboard.php         # Overtime stats dashboard
├── attendance.php        # Employee attendance (check-in/out)
├── attendance_admin.php  # Admin attendance monitoring
├── overtime.php          # Employee overtime submission
├── overtime_admin.php    # Admin overtime approval
├── leave.php             # Employee leave request
├── leave_admin.php       # Admin leave approval
├── payslip.php           # Employee payslip viewing
├── inbox.php             # Employee inbox
└── inbox_compose.php     # Admin message composition
```

## Database Schema
Using PostgreSQL with the following tables:

### users
- id, username, password, full_name, email, department, position, salary, role, created_at

### overtime
- id, employee_id, date, start_time, end_time, duration, reason, status, approved_by, created_at, updated_at

### attendance
- id, employee_id, date, check_in, check_out, work_type (WFO/WFH), location, notes, status, created_at

### leave_requests
- id, employee_id, leave_type, start_date, end_date, reason, status, approved_by, created_at, updated_at

### payslips
- id, employee_id, period_month, period_year, basic_salary, overtime_pay, allowances, deductions, tax, net_salary, payment_date, status, created_at

### inbox
- id, recipient_id, sender_id, sender_name, subject, message, is_read, is_important, message_type, created_at

### kpi
- id, employee_id, period_month, period_year, target_value, actual_value, category, description, created_at

### company_feeds
- id, author_id, title, content, feed_type, is_pinned, created_at

## Demo Accounts
- **Admin**: username `admin`, password `admin123`
- **Employee 1**: username `employee1`, password `employee123`
- **Employee 2**: username `employee2`, password `employee123`

## Technology Stack
- PHP 8.2
- PostgreSQL (Neon-backed)
- Bootstrap 5.3
- Chart.js (for KPI diagrams)
- Bootstrap Icons

## Running the Application
The application runs on PHP's built-in server on port 5000.

## Recent Changes
- November 27, 2025: Rebranded to HRMS with custom logo
- November 27, 2025: Added comprehensive HRMS features
  - Home page with shortcuts, KPI progress, and company feed
  - Attendance tracking with WFO/WFH support
  - Leave management system
  - Payslip viewing
  - Inbox messaging system
  - Admin panels for attendance, leave, and message composition

## User Preferences
- Interface language: Indonesian
- Uses Bootstrap 5 for responsive design
- Chart.js for data visualization
- Custom HRMS logo branding
