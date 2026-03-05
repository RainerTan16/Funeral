# Y2J Funeral Service — v2
## Setup Instructions

### Requirements
- PHP 7.4+ with MySQLi extension
- MySQL / MariaDB
- Apache or Nginx with mod_rewrite
- A local server like XAMPP, WAMP, or Laragon

---

### 1. Database Setup
1. Open phpMyAdmin or your MySQL client.
2. Import `database.sql` to create the `y2j_funeral` database with all tables and seed data.
3. Default admin credentials:
   - **Username:** `admin`
   - **Password:** `admin123`

---

### 2. Configure Database Connection
Edit `includes/db.php` and update:
```php
define('DB_HOST', 'localhost');   // your DB host
define('DB_USER', 'root');        // your DB username
define('DB_PASS', '');            // your DB password
define('DB_NAME', 'y2j_funeral'); // leave as-is
```

---

### 3. File Structure
```
y2j/
├── index.php           ← Home (casket listing)
├── reserve.php         ← Reservation form (2-step)
├── contact.php         ← Contact info
├── css/
│   └── style.css
├── images/
│   ├── oak.png         ← Upload your casket images here
│   ├── metal.png
│   ├── mahogany.png
│   └── logo.png        ← Y2J logo
├── includes/
│   ├── db.php
│   └── nav.php
├── admin/
│   ├── login.php       ← Admin login
│   ├── dashboard.php   ← Stats overview
│   ├── reservations.php← Manage & approve reservations
│   ├── deliveries.php  ← Track deliveries
│   ├── sales.php       ← Sales reports
│   ├── caskets.php     ← Add/edit/delete caskets
│   ├── logout.php
│   ├── auth.php        ← Session guard
│   └── sidebar.php
└── database.sql
```

---

### 4. Add Images
Place your casket images and logo inside the `images/` folder:
- `images/oak.png`
- `images/metal.png`
- `images/mahogany.png`
- `images/logo.png`

---

### 5. Access
- **User site:** `http://localhost/y2j/`
- **Admin panel:** `http://localhost/y2j/admin/login.php`

---

### Admin Features
| Feature | Description |
|---|---|
| Dashboard | Overview stats: total reservations, pending, approved, delivered, revenue |
| Reservations | View all reservations, filter by status, approve/reject/mark delivered |
| Deliveries | Focused view of approved orders, one-click mark as delivered |
| Sales Report | Monthly revenue breakdown + per-casket sales summary |
| Manage Caskets | Add new caskets, update stock levels, delete caskets |

---

### Password Hashing Note
The default admin password hash uses PHP's `password_hash()` with `PASSWORD_BCRYPT`.  
To change the password, run this in PHP:
```php
echo password_hash('your_new_password', PASSWORD_DEFAULT);
```
Then update the hash in the `admins` table.
