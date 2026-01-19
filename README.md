


---

# EduNotify – Absence Management and Notification System

## Project Title

EduNotify – Automated Student Absence Management and Parent Notification System

---

## Project Objectives

The objective of this project is to design and implement a web-based system that automates the management of student absences and notifies parents using modern communication channels.

The main goals of this project are:

* Automate the processing of student absences
* Import and analyze absence data from CSV files
* Validate and normalize data automatically
* Store absences in a structured database
* Notify parents via Email and WhatsApp
* Reduce manual administrative work
* Apply object-oriented programming principles in PHP
* Integrate external APIs (SMTP and Twilio)

---

## Technologies Used

* PHP 8+
* MySQL / MariaDB
* HTML5
* CSS3
* JavaScript
* PDO (PHP Data Objects)
* PHPMailer (SMTP Email)
* Twilio WhatsApp API
* Composer (Dependency Management)

---

## Project Structure

```
projet_absences_V2/
│
├── admin/                 # Admin dashboard and notification processing
├── assets/                # CSS and JavaScript files
├── classes/               # Core PHP classes (OOP)
├── config/                # Database and notification configuration
├── database/              # SQL schema files
├── test/                  # Test scripts
├── uploads/               # Uploaded CSV files
├── vendor/                # Composer dependencies
│
├── index.php
├── login.php
├── register.php
└── composer.json
```

The project is well structured, and the source code is clearly separated from configuration files, assets, tests, and external libraries.

---

## Instructions to Run the Project

### Prerequisites

* XAMPP or WAMP
* PHP 8 or higher
* MySQL or MariaDB
* Composer
* Internet connection (for Email and WhatsApp APIs)

---

### Installation Steps

1. Copy the project folder into:

   ```
   C:\xampp\htdocs\projet_absences_V2
   ```

2. Start **Apache** and **MySQL** from XAMPP Control Panel.

3. Install dependencies:

   ```bash
   composer install
   composer require twilio/sdk
   ```

4. Create the database and import:

   ```
   database/notifications_tables.sql
   ```

5. Configure database access in:

   ```
   config/database.php
   ```

6. Configure Email and WhatsApp credentials in:

   ```
   config/notification_config.php
   ```

7. Open the application in the browser:

   ```
   http://localhost/projet_absences_V2
   ```

---

## Testing

Test scripts are available in the `test/` directory:

* `test_email.php`
* `test_whatsapp.php`
* `test_absences.php`

These files allow independent testing of the system components.

## Additional Notes

* WhatsApp notifications work in Twilio Sandbox mode (development environment).
* Phone numbers must join the Twilio sandbox before receiving messages.
* Sensitive credentials must not be published in public repositories.

---

