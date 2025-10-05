# WifiHub - Mikrotik Voucher Hotspot System

A complete web application for managing Mikrotik hotspot vouchers with user authentication, package management, and voucher generation.

## Directory Structure

```
WifiHub/
├── api/                    # API endpoints and database files
│   ├── api.php            # Main API handler
│   ├── schema_mysql.sql   # Database schema
│   └── setup_db.php       # Database setup script
├── src/                   # Source files
│   ├── css/              # Stylesheet files
│   │   └── styles.css    # Main stylesheet
│   ├── js/               # JavaScript files
│   │   └── script.js     # Main JavaScript application
│   └── img/              # Image assets
│       ├── favicon.ico
│       └── logo.png
├── index.html            # Main application page
├── test.html             # Test page
└── README.md             # This file
```

## Features

- User registration and authentication
- Multiple voucher packages with different durations
- Profile management
- Voucher viewing
- Responsive design with dark/light mode
- Mikrotik integration (configured in api.php)

## Setup

1. Ensure XAMPP is running with Apache and MySQL
2. To use the proper project name, rename the folder from `voucher-hotspot` to `WifiHub` in your htdocs directory
3. Access the database setup at: `http://localhost/myApps/WifiHub/api/setup_db.php`
4. Configure your Mikrotik settings in `api/api.php`
5. Update database credentials in `api/api.php` if necessary