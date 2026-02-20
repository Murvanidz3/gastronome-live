# 🍽 Gastronome.live — Product Database System

A modern, production-ready product management admin system with Glassmorphism UI.

## 🚀 Quick Setup (XAMPP)

### 1. Place the Project
Copy the `Gastronome.live` folder into your XAMPP `htdocs` directory:
```
C:\xampp\htdocs\Gastronome.live\
```

### 2. Start XAMPP
Start **Apache** and **MySQL** from the XAMPP Control Panel.

### 3. Create the Database
- Open [phpMyAdmin](http://localhost/phpmyadmin)
- Click **Import** or go to the **SQL** tab
- Paste / import the contents of `sql/schema.sql`
- Click **Go**

### 4. Configure Database (if needed)
Edit `config/database.php` if your MySQL credentials differ from the defaults:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'gastronome');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 5. Open in Browser
Navigate to: [http://localhost/](http://localhost/)

### 6. Login
| Field    | Value     |
|----------|-----------|
| Username | `admin`   |
| Password | `admin123`|

---

## 📁 Project Structure
```
Gastronome.live/
├── config/database.php       # PDO connection
├── auth/
│   ├── login.php             # Login page
│   ├── logout.php            # Session destroy
│   └── guard.php             # Auth middleware
├── includes/header.php       # Shared HTML + nav
├── api/
│   ├── search.php            # Live search endpoint
│   ├── products.php          # Product listing API
│   └── import.php            # CSV import handler
├── css/style.css             # Glassmorphism styles
├── js/
│   ├── app.js                # Dashboard search
│   ├── csv.js                # CSV import logic
│   └── invoice.js            # Invoice generator
├── sql/schema.sql            # Database schema
├── uploads/                  # Temp CSV storage
├── index.php                 # Dashboard
├── csv-import.php            # CSV Import page
└── invoice.php               # Invoice generator
```

## 📥 CSV Import Format
| Column    | Required | Description        |
|-----------|----------|--------------------|
| barcode   | ✅       | Unique product code|
| name      | ✅       | Product name       |
| image_url | ❌       | Image URL          |
| quantity  | ❌       | Stock quantity     |
| price     | ❌       | Unit price         |
| comment   | ❌       | Notes              |

A sample CSV file is provided: `sample_products.csv`

---

## 🌐 Hostinger Deployment
1. Upload all files to `public_html/` via File Manager or FTP
2. Create a MySQL database in Hostinger hPanel
3. Import `sql/schema.sql` into the database
4. Update `config/database.php` with your Hostinger DB credentials
5. Point your domain (`gastronome.live`) to the hosting
