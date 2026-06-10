-- ============================================================
-- Migration 002 — Type-specific routing + item catalog
-- MariaDB 10.4 compatible
-- Run in phpMyAdmin: reqon_db → SQL tab → execute all at once
-- ============================================================

USE reqon_db;

-- ── 1. Rebuild approval_levels ────────────────────────────────────────────
-- Drop the FK on approval_history that references approval_levels,
-- clear both tables, re-seed approval_levels, then restore the FK.

ALTER TABLE approval_history
    DROP FOREIGN KEY approval_history_ibfk_3;

DELETE FROM approval_history;
DELETE FROM approval_levels;
ALTER TABLE approval_levels AUTO_INCREMENT = 1;

-- IT Asset: IT Dept Head (Elizabeth, always dept 1) → Procurement Head → Finance Dir → MD
INSERT INTO approval_levels (requisition_type, level_number, role_id, description) VALUES
('it_asset',    1, 3, 'IT Dept Head (Elizabeth — always dept 1)'),
('it_asset',    2, 3, 'Procurement Head (Mary)'),
('it_asset',    3, 3, 'Finance Director (David)'),
('it_asset',    4, 3, 'Managing Director (James)');

-- Merchandise: Submitter dept head → Procurement Head → Finance Dir → MD
INSERT INTO approval_levels (requisition_type, level_number, role_id, description) VALUES
('merchandise', 1, 3, 'Submitter Dept Head'),
('merchandise', 2, 3, 'Procurement Head (Mary)'),
('merchandise', 3, 3, 'Finance Director (David)'),
('merchandise', 4, 3, 'Managing Director (James)');

-- Personnel: Submitter dept head → HR Director → Finance Dir → MD
INSERT INTO approval_levels (requisition_type, level_number, role_id, description) VALUES
('personnel',   1, 3, 'Submitter Dept Head'),
('personnel',   2, 2, 'HR Director (Grace)'),
('personnel',   3, 3, 'Finance Director (David)'),
('personnel',   4, 3, 'Managing Director (James)');

-- Procurement: Submitter dept head → Procurement Head → Finance Dir → MD
INSERT INTO approval_levels (requisition_type, level_number, role_id, description) VALUES
('procurement', 1, 3, 'Submitter Dept Head'),
('procurement', 2, 3, 'Procurement Head (Mary)'),
('procurement', 3, 3, 'Finance Director (David)'),
('procurement', 4, 3, 'Managing Director (James)');

-- Restore the FK (now pointing at the new rows)
ALTER TABLE approval_history
    ADD CONSTRAINT approval_history_ibfk_3
        FOREIGN KEY (level_id) REFERENCES approval_levels (level_id);


-- ── 2. item_catalog table ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS item_catalog (
    catalog_id         INT           NOT NULL AUTO_INCREMENT,
    item_name          VARCHAR(255)  NOT NULL,
    category           ENUM('it_asset','procurement','merchandise','personnel') NOT NULL,
    description        TEXT,
    standard_unit_cost DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    unit_label         VARCHAR(30)   DEFAULT 'unit',
    is_active          TINYINT(1)    DEFAULT 1,
    created_at         DATETIME      DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (catalog_id),
    INDEX idx_catalog_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ── 3. Add catalog_id + is_custom to requisition_items ───────────────────
-- Guard with IF NOT EXISTS equivalent: only runs if columns are absent.
-- In phpMyAdmin just run as-is; if columns already exist it will error
-- on those two lines only — safe to ignore those specific errors.

ALTER TABLE requisition_items
    ADD COLUMN catalog_id INT        DEFAULT NULL,
    ADD COLUMN is_custom  TINYINT(1) DEFAULT 0,
    ADD CONSTRAINT fk_ri_catalog
        FOREIGN KEY (catalog_id) REFERENCES item_catalog (catalog_id)
        ON DELETE SET NULL;


-- ── 4. Seed item_catalog ──────────────────────────────────────────────────

-- IT Asset (12 items)
INSERT INTO item_catalog (item_name, category, description, standard_unit_cost, unit_label) VALUES
('Dell Latitude 5540 Laptop',           'it_asset', '15.6" FHD, Intel i7, 16GB RAM, 512GB SSD, Win 11 Pro',  100000.00, 'unit'),
('HP EliteBook 840 G10 Laptop',         'it_asset', '14" FHD, Intel i5, 8GB RAM, 256GB SSD',                  85000.00, 'unit'),
('Dell UltraSharp 27" Monitor',         'it_asset', 'U2722D, 4K, USB-C, IPS panel',                           45000.00, 'unit'),
('Cisco Catalyst 2960-X 24-Port Switch','it_asset', '24x GigE, PoE+, LAN Base',                               85000.00, 'unit'),
('Uninterruptible Power Supply (UPS)',  'it_asset', 'APC Smart-UPS 1500VA, rack-mount',                        35000.00, 'unit'),
('HP LaserJet Pro M408dn Printer',      'it_asset', 'Mono laser, duplex, network-ready',                       28000.00, 'unit'),
('Logitech MX Keys Keyboard + Mouse',   'it_asset', 'Wireless combo, multi-device',                             8500.00, 'unit'),
('External Hard Drive 2TB',             'it_asset', 'Seagate Backup Plus, USB 3.0',                             6500.00, 'unit'),
('USB-C Docking Station',               'it_asset', 'Dell WD19S, 130W, dual display',                          18000.00, 'unit'),
('Webcam HD 1080p',                     'it_asset', 'Logitech C920, with mic',                                   5500.00, 'unit'),
('Network Patch Panel 24-Port',         'it_asset', 'Cat6, rack-mount, 1U',                                     4500.00, 'unit'),
('Structured Cabling (per point)',      'it_asset', 'Cat6 data point installation including faceplate',          3500.00, 'point');

-- Procurement (12 items)
INSERT INTO item_catalog (item_name, category, description, standard_unit_cost, unit_label) VALUES
('A4 Printing Paper (500 sheets)',      'procurement', 'Double A brand, 80gsm',                                   800.00, 'ream'),
('Ballpoint Pens (box of 50)',          'procurement', 'Blue ink, medium tip',                                     600.00, 'box'),
('Stapler Heavy Duty',                  'procurement', '26/6 staples, desktop',                                   1200.00, 'unit'),
('Printer Ink Cartridge Set',           'procurement', 'Compatible with HP LaserJet M408',                        3500.00, 'set'),
('Whiteboard Markers (set of 4)',       'procurement', 'Assorted colours, dry-erase',                              450.00, 'set'),
('Lever Arch File A4',                  'procurement', '70mm spine, assorted colours',                             280.00, 'unit'),
('Sticky Notes 76x76mm (pack of 12)',  'procurement', '3M Post-it, assorted neon',                                650.00, 'pack'),
('Toner Cartridge — HP 26A',           'procurement', 'Black, ~3100 pages yield',                                4800.00, 'unit'),
('Desk Organiser Set',                  'procurement', '5-piece: pen holder, tray, file stand',                  1800.00, 'set'),
('Cleaning Supplies Monthly Pack',     'procurement', 'Disinfectant, wipes, hand sanitiser, bin liners',         3200.00, 'pack'),
('Tea & Coffee Supplies (monthly)',    'procurement', 'Tea bags, coffee, sugar, creamer — office kitchen',       4500.00, 'month'),
('Bottled Water (20L dispenser)',       'procurement', 'Keringet or equivalent, per bottle',                       350.00, 'bottle');

-- Merchandise (8 items)
INSERT INTO item_catalog (item_name, category, description, standard_unit_cost, unit_label) VALUES
('Branded Polo Shirt',                  'merchandise', 'Isuzu EA logo embroidered, polyester-cotton blend',       1800.00, 'unit'),
('Branded Cap',                         'merchandise', 'Structured 6-panel, embroidered logo',                     950.00, 'unit'),
('Branded Notebook A5',                 'merchandise', 'Hardcover, 200 pages, logo on cover',                      650.00, 'unit'),
('Branded Pen',                         'merchandise', 'Metal ballpoint, laser-engraved logo',                     350.00, 'unit'),
('Branded Tote Bag',                    'merchandise', 'Canvas, 38x42cm, screen-printed logo',                    1200.00, 'unit'),
('Branded Mug',                         'merchandise', 'Ceramic 350ml, dishwasher-safe, logo print',               750.00, 'unit'),
('Branded Lanyard',                     'merchandise', 'Polyester, 20mm wide, safety clip, logo print',            400.00, 'unit'),
('Branded USB Flash Drive 32GB',        'merchandise', 'Metal casing, USB 3.0, logo engraved',                    1500.00, 'unit');

-- Personnel — seniority levels (monthly salary budget)
INSERT INTO item_catalog (item_name, category, description, standard_unit_cost, unit_label) VALUES
('Intern',            'personnel', 'Entry-level internship position (3-6 months)',                              20000.00, 'month'),
('Junior Associate',  'personnel', 'Graduate / entry-level permanent or contract role',                         60000.00, 'month'),
('Associate',         'personnel', 'Mid-level individual contributor',                                         150000.00, 'month'),
('Senior Associate',  'personnel', 'Experienced individual contributor',                                       200000.00, 'month'),
('Senior 1',          'personnel', 'Senior specialist / team lead',                                            280000.00, 'month'),
('Senior 2',          'personnel', 'Senior specialist with broader scope',                                     350000.00, 'month'),
('Senior 3',          'personnel', 'Principal specialist / department expert',                                  430000.00, 'month'),
('Manager',           'personnel', 'People manager, single team',                                              500000.00, 'month'),
('Senior Manager',    'personnel', 'Multi-team or cross-functional manager',                                   650000.00, 'month'),
('Director',          'personnel', 'Department head / executive leadership',                                   900000.00, 'month');
