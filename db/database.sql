-- ============================================================
-- Transport Dashboard - Database Schema
-- Import this file first (phpMyAdmin -> Import, or via CLI):
--   mysql -u root -p < database.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS transport_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE transport_db;

CREATE TABLE IF NOT EXISTS transport_records (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_no       VARCHAR(50)  NOT NULL,
    driver_name      VARCHAR(100) NOT NULL,
    driver_contact   VARCHAR(20)  NOT NULL,
    source           VARCHAR(150) NOT NULL,
    destination      VARCHAR(150) NOT NULL,
    departure_date   DATE         NOT NULL,
    departure_time   TIME         NOT NULL,
    arrival_time     TIME         NULL,
    status           ENUM('Scheduled','In Transit','Completed','Cancelled') NOT NULL DEFAULT 'Scheduled',
    remarks          VARCHAR(255) NULL,
    supplier         VARCHAR(150) NULL,
    lr_number        VARCHAR(50)  NULL,
    invoice_number   VARCHAR(50)  NULL,
    gst_number       VARCHAR(20)  NULL,
    quantity         DECIMAL(10,2) NULL,
    rate             DECIMAL(10,2) NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Sample seed data (optional - remove if not needed)
INSERT INTO transport_records
(vehicle_no, driver_name, driver_contact, source, destination, departure_date, departure_time, arrival_time, status, remarks,
 supplier, lr_number, invoice_number, gst_number, quantity, rate)
VALUES
('MH12AB1234', 'Ramesh Patil',  '9876543210', 'Pune',    'Mumbai',    '2026-07-03', '06:00:00', '09:30:00', 'Completed', 'On time',
 'ABC Traders',   'LR-1001', 'INV-2001', '27ABCDE1234F1Z5', 12.50, 4500.00),
('MH14CD5678', 'Suresh Yadav',  '9823456789', 'Pune',    'Nashik',    '2026-07-03', '08:00:00', NULL,       'In Transit', 'Traffic near Sinnar',
 'XYZ Logistics', 'LR-1002', 'INV-2002', '27XYZAB5678G1Z2', 8.00,  3200.00),
('MH04EF9012', 'Anil Kumar',    '9765432109', 'Mumbai',  'Pune',      '2026-07-04', '14:00:00', NULL,       'Scheduled', NULL,
 'ABC Traders',   'LR-1003', 'INV-2003', '27ABCDE1234F1Z5', 15.00, 5000.00);

-- If you already created the table before this update, run this instead:
-- ALTER TABLE transport_records
--   ADD COLUMN supplier       VARCHAR(150)  NULL AFTER remarks,
--   ADD COLUMN lr_number      VARCHAR(50)   NULL AFTER supplier,
--   ADD COLUMN invoice_number VARCHAR(50)   NULL AFTER lr_number,
--   ADD COLUMN gst_number     VARCHAR(20)   NULL AFTER invoice_number,
--   ADD COLUMN quantity       DECIMAL(10,2) NULL AFTER gst_number,
--   ADD COLUMN rate           DECIMAL(10,2) NULL AFTER quantity;


-- ============================================================
-- Trip Log tab - diesel / KM / DEF / FASTag log sheet
-- ============================================================
CREATE TABLE IF NOT EXISTS trip_logs (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    trip_date          DATE          NOT NULL,
    return_date        DATE          NULL,
    lr_number          VARCHAR(50)   NOT NULL,
    vehicle_no         VARCHAR(50)   NOT NULL,
    location           VARCHAR(150)  NOT NULL,
    sakharwadi_diesel  DECIMAL(10,2) NOT NULL,
    rate               DECIMAL(10,2) NOT NULL,
    amount             DECIMAL(12,2) NOT NULL,
    advance            DECIMAL(10,2) NULL,
    driver_name        VARCHAR(100)  NOT NULL,
    before_diesel      DECIMAL(10,2) NULL,
    after_diesel       DECIMAL(10,2) NULL,
    before_km          DECIMAL(10,2) NULL,
    after_km           DECIMAL(10,2) NULL,
    total_km           DECIMAL(10,2) NULL,
    def_qty            DECIMAL(10,2) NOT NULL,
    kl_qty             DECIMAL(10,2) NOT NULL,
    fasttag_exp        DECIMAL(10,2) NULL,
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Sample seed data (optional - remove if not needed)
INSERT INTO trip_logs
(trip_date, return_date, lr_number, vehicle_no, location, sakharwadi_diesel, rate, amount, advance,
 driver_name, before_diesel, after_diesel, before_km, after_km, total_km, def_qty, kl_qty, fasttag_exp)
VALUES
('2026-07-05', '2026-07-06', 'LR-3001', 'MH12AB1234', 'Sakharwadi', 120.00, 92.50, 11100.00, 2000.00,
 'Ramesh Patil', 40.00, 20.00, 15230.00, 15480.00, 250.00, 8.00, 18.50, 150.00),
('2026-07-06', NULL, 'LR-3002', 'MH14CD5678', 'Sakharwadi', 95.00, 92.50, 8787.50, 1500.00,
 'Suresh Yadav', 35.00, 15.00, 22010.00, 22190.00, 180.00, 6.50, 14.00, 100.00);


-- ============================================================
-- Maintenance tab - vehicle repair/service log sheet
-- ============================================================
CREATE TABLE IF NOT EXISTS maintenance_records (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    service_date          DATE          NOT NULL,
    vehicle_no            VARCHAR(50)   NOT NULL,
    odometer_km           DECIMAL(10,2) NULL,
    work_type             ENUM(
                               'Servicing (Full)',
                               'Servicing (Half)',
                               'Welding',
                               'Main Tank Work',
                               'Tyre Work',
                               'Balancing Work',
                               'Battery Work',
                               'Oil Change',
                               'Clutch / Brake Work',
                               'Electrical Work',
                               'Denting / Painting',
                               'Puncture Repair',
                               'Engine Work',
                               'Suspension Work',
                               'Other'
                           ) NOT NULL,
    work_type_other       VARCHAR(150)  NULL,
    description           VARCHAR(500)  NULL,
    parts_replaced        VARCHAR(255)  NULL,
    garage_name           VARCHAR(150)  NULL,
    location               VARCHAR(150)  NULL,
    bill_number             VARCHAR(50)   NULL,
    parts_cost              DECIMAL(10,2) NULL,
    labour_cost              DECIMAL(10,2) NULL,
    total_cost                DECIMAL(10,2) NOT NULL,
    payment_mode                ENUM('Cash','UPI','Bank Transfer','Cheque','Credit') NULL,
    status                        ENUM('Pending','In Progress','Completed') NOT NULL DEFAULT 'Pending',
    next_service_due_date          DATE NULL,
    created_at                      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at                      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Sample seed data (optional - remove if not needed)
INSERT INTO maintenance_records
(service_date, vehicle_no, odometer_km, work_type, work_type_other, description, parts_replaced,
 garage_name, location, bill_number, parts_cost, labour_cost, total_cost, payment_mode, status,
 next_service_due_date)
VALUES
('2026-07-10', 'MH12AB1234', 15480.00, 'Servicing (Full)', NULL,
 'Full service including oil, filters, and general checkup', 'Engine oil, oil filter, air filter',
 'Shree Auto Garage', 'Pune', 'BILL-4001', 1800.00, 700.00, 2500.00, 'UPI', 'Completed',
 '2026-10-10'),
('2026-07-14', 'MH14CD5678', 22190.00, 'Tyre Work', NULL,
 'Rear left tyre punctured, replaced with new tyre', 'One rear tyre',
 'Highway Tyre Point', 'Nashik', 'BILL-4002', 6200.00, 300.00, 6500.00, 'Cash', 'Completed',
 NULL),
('2026-07-18', 'MH04EF9012', 8900.00, 'Other', 'AC Compressor Repair',
 'Cabin AC not cooling, compressor repaired', 'AC compressor seal kit',
 'Cool Care Auto', 'Mumbai', NULL, 2200.00, 500.00, 2700.00, 'Bank Transfer', 'In Progress',
 NULL);

-- If you already created maintenance_records with the older schema, run
-- this instead to drop the removed columns from your existing table:
-- ALTER TABLE maintenance_records
--   DROP COLUMN driver_name,
--   DROP COLUMN out_of_service_from,
--   DROP COLUMN back_in_service_on,
--   DROP COLUMN next_service_due_km,
--   DROP COLUMN remarks;



