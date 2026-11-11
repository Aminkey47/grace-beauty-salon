# Grace Beauty Salon – Salon Appointment Booking System

## 1. Project Overview

Grace Beauty Salon is a web-based salon appointment booking system developed as part of a Full Stack Web Development assignment.

The system digitises salon appointment management by providing customers with an online booking experience while allowing salonists and administrators to manage appointments and salon operations through dedicated dashboards.

## 2. Problem Statement

Traditional salon appointment management can involve manual booking processes, communication delays and difficulties coordinating customer appointments with available salonists.

Grace Beauty Salon aims to address these challenges through a centralised platform that simplifies appointment booking, improves communication and supports efficient salon management.

## 3. Key Features

### Customer
- Account registration and email OTP verification.
- Secure login and logout.
- Browse available salon services.
- Book appointments by selecting a service, date and time.
- Select a preferred salonist or choose any available salonist.
- View appointment history.
- Manage profile information.
- Receive appointment confirmation and status notifications by email.

### Salonist
- Secure authentication.
- Dedicated salonist dashboard.
- View assigned appointments.
- Accept or decline appointment requests.
- Manage profile information.
- Receive email notifications about new bookings.

### Administrator
- Dedicated administration dashboard.
- Manage salon services.
- Register and manage salonists.
- Manage customer accounts.
- Assign salonists to services.
- View and manage appointments.
- Update appointment statuses.

## 4. Technologies Used

| Technology | Purpose |
|---|---|
| HTML5 | Website structure |
| Tailwind CSS | User interface styling |
| Vanilla JavaScript | Client-side interactivity and validation |
| PHP | Backend development |
| MySQL | Database management |
| Composer | PHP dependency management |
| PHPMailer | Email delivery |
| Git and GitHub | Version control and project hosting |

## 5. Installation and Setup

### Prerequisites
- PHP
- MySQL
- Apache
- Composer
- XAMPP or an equivalent local development environment

### Clone the repository

```bash
git clone https://github.com/Aminkey47/grace-beauty-salon.git
```

Move into the project directory:

```bash
cd grace-beauty-salon
```

### Install dependencies

```bash
composer install
```

### Database configuration

1. Start Apache and MySQL.
2. Open phpMyAdmin.
3. Create a database named `grace_beauty_salon`.
4. Import the project's database SQL file.
5. Configure the database connection in `config/database.php`.

### Email configuration

Configure the SMTP credentials in `config/mail.php`.

For Gmail, use an App Password rather than your normal Google account password.

**Important:** Never commit actual database credentials, SMTP passwords or other secrets to GitHub.

## 6. Project Structure

```text
grace-beauty-salon/
│
├── admin/
├── auth/
├── customer/
├── salonist/
├── src/
├── config/
├── includes/
├── vendor/
│
├── composer.json
├── composer.lock
├── index.php
└── .gitignore
```

Note: Configuration files and Composer dependencies are excluded from version control where appropriate. Composer dependencies can be installed using `composer install`.

## 7. Challenges Encountered and Solutions

### GitHub Authentication Failure

**Challenge:**

During the initial attempt to push the local project to GitHub, authentication failed with the following error:

```text
remote: Invalid username or token.
Password authentication is not supported for Git operations.
fatal: Authentication failed
```

The issue occurred because GitHub does not support normal account password authentication for Git operations over HTTPS.

**Attempted solution:**

A Personal Access Token was generated and an attempt was made to authenticate using HTTPS. However, authentication continued to fail because Git was not successfully using the intended credentials.

**Final solution:**

GitHub CLI was installed on Ubuntu and used to authenticate through the browser.

The process involved:

1. Installing GitHub CLI.
2. Running `gh auth login`.
3. Selecting GitHub.com and HTTPS.
4. Choosing browser-based authentication.
5. Authorising the computer through GitHub.
6. Configuring Git to use GitHub CLI credentials.

The following commands completed the authentication and push process:

```bash
gh auth setup-git
git push -u origin main
```

**Lesson learnt:**

Git version control and GitHub authentication are separate processes. A successful local commit does not automatically mean that a remote push will succeed. GitHub CLI provides a convenient way to authenticate a development environment.

### Protecting Sensitive Configuration Files

**Challenge:**

The application requires database and SMTP credentials for its functionality.

**Solution:**

A `.gitignore` file was configured to exclude sensitive configuration files, environment files and Composer dependencies.

This helped prevent accidental exposure of credentials when pushing the project to GitHub.

## 8. Security Considerations

- Passwords are stored using secure password hashing.
- Email OTP verification is implemented.
- Role-based access control separates customer, salonist and administrator functionality.
- Session-based authentication is used.
- Sensitive configuration files are excluded from version control.
- Server-side validation is applied to important booking operations.

## 9. Future Improvements

- Online payment integration.
- Appointment reminders.
- Improved reporting and analytics.
- Enhanced appointment availability management.
- Additional automated testing.

## 10. Author

Developed by **Aminkey47** as part of a Full Stack Web Development academic assignment.

GitHub Repository:  
https://github.com/Aminkey47/grace-beauty-salon
