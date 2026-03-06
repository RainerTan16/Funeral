-- Y2J Funeral Service Database


-- Caskets / Products
CREATE TABLE IF NOT EXISTS caskets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    material VARCHAR(100),
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    image_url VARCHAR(255),
    stock INT DEFAULT 10,
    low_stock_threshold INT DEFAULT 2,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admin users
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Client reservations
CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(150) NOT NULL,
    client_address TEXT NOT NULL,
    client_phone VARCHAR(20) NOT NULL,
    reservation_date DATE NOT NULL,
    deceased_name VARCHAR(150) NOT NULL,
    deceased_age INT NOT NULL,
    deceased_dod DATE NOT NULL,
    casket_id INT NOT NULL,
    casket_color VARCHAR(50),
    quantity INT DEFAULT 1,
    payment_type ENUM('Cash') NOT NULL,
    total_amount DECIMAL(10,2),
    remarks TEXT NULL,
    admin_notes TEXT NULL,
    status ENUM('Pending','Approved','Rejected','Delivered') DEFAULT 'Pending',
    rejection_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (casket_id) REFERENCES caskets(id)
);

-- Bundles / Service Packages
CREATE TABLE IF NOT EXISTS bundles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    inclusions TEXT,
    image_url VARCHAR(255) DEFAULT 'images/bundle_placeholder.jpg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bundle Reservations
CREATE TABLE IF NOT EXISTS bundle_reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(150) NOT NULL,
    client_address TEXT NOT NULL,
    client_phone VARCHAR(20) NOT NULL,
    reservation_date DATE NOT NULL,
    deceased_name VARCHAR(150) NOT NULL,
    deceased_age INT NOT NULL,
    deceased_dod DATE NOT NULL,
    bundle_id INT NOT NULL,
    payment_type ENUM('Cash') NOT NULL,
    total_amount DECIMAL(10,2),
    remarks TEXT NULL,
    admin_notes TEXT NULL,
    status ENUM('Pending','Approved','Rejected','Delivered') DEFAULT 'Pending',
    rejection_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bundle_id) REFERENCES bundles(id)
);

-- Sample bundles
INSERT INTO bundles (name, price, description, inclusions, image_url) VALUES
('Basic Package', 15000.00, 'An affordable, dignified farewell for your loved one.',
 'Entry-level casket|Basic embalming|Death certificate assistance|1-day wake',
 'images/basic_bundle.jpg'),
('Standard Package', 35000.00, 'Our most popular package — complete care at a reasonable price.',
 'Mid-range casket|Embalming|Death certificate assistance|3-day wake|Flower arrangement|Transportation & delivery',
 'images/standard_bundle.jpg'),
('Premium Package', 75000.00, 'Full-service, premium care with everything your family needs.',
 'Premium casket|Embalming|Death certificate assistance|5-day wake|Flower arrangement|Transportation & delivery|Catering service|Memorial video & photo display',
 'images/premium_bundle.jpg');

-- Default admin (password: password)
INSERT INTO admins (username, password) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Sample caskets
INSERT INTO caskets (name, material, price, description, image_url, stock) VALUES
('Premium Oak', 'Solid Wood', 5000.00, 'Beautifully crafted solid oak casket with plush white interior lining.', 'images/oak.jpg', 10),
('Classic Metal', 'Steel Construction', 4500.00, 'Durable steel casket with elegant finish and cushioned interior.', 'images/metal.jpg', 10),
('Elegant Mahogany', 'Premium Finish', 10000.00, 'Premium mahogany casket with brass accents and luxury interior.', 'images/mahogany.jpg', 10);

-- ── ARCHIVE TABLES ────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS archived_reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_id INT,
    client_name VARCHAR(150),
    client_address TEXT,
    client_phone VARCHAR(20),
    reservation_date DATE,
    deceased_name VARCHAR(150),
    deceased_age INT,
    deceased_dod DATE,
    casket_name VARCHAR(100),
    casket_color VARCHAR(50),
    quantity INT,
    total_amount DECIMAL(10,2),
    remarks TEXT,
    admin_notes TEXT,
    status VARCHAR(30),
    rejection_reason TEXT,
    original_created_at TIMESTAMP,
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS archived_bundle_reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_id INT,
    client_name VARCHAR(150),
    client_address TEXT,
    client_phone VARCHAR(20),
    reservation_date DATE,
    deceased_name VARCHAR(150),
    deceased_age INT,
    deceased_dod DATE,
    bundle_name VARCHAR(100),
    total_amount DECIMAL(10,2),
    remarks TEXT,
    admin_notes TEXT,
    status VARCHAR(30),
    rejection_reason TEXT,
    original_created_at TIMESTAMP,
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS archived_caskets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_id INT,
    name VARCHAR(100),
    material VARCHAR(100),
    price DECIMAL(10,2),
    description TEXT,
    image_url VARCHAR(255),
    stock INT,
    original_created_at TIMESTAMP,
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS archived_bundles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_id INT,
    name VARCHAR(100),
    price DECIMAL(10,2),
    description TEXT,
    inclusions TEXT,
    image_url VARCHAR(255),
    original_created_at TIMESTAMP,
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
