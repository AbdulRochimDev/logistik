-- MySQL 8 schema for Monitoring Logistik Gudang

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(191) NOT NULL,
  email VARCHAR(191) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  default_role VARCHAR(50) NOT NULL,
  email_verified_at DATETIME NULL,
  remember_token VARCHAR(100) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE roles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  description VARCHAR(191) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE role_user (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  role_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user_role (user_id, role_id),
  CONSTRAINT fk_role_user_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_role_user_role FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE TABLE driver_profiles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL UNIQUE,
  license_number VARCHAR(100) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  photo_path VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_driver_profiles_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE warehouses (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(191) NOT NULL,
  address TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE locations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  warehouse_id BIGINT UNSIGNED NOT NULL,
  code VARCHAR(100) NOT NULL,
  name VARCHAR(191) NOT NULL,
  type VARCHAR(50) NOT NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY warehouse_location (warehouse_id, code),
  CONSTRAINT fk_locations_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id)
) ENGINE=InnoDB;

CREATE TABLE items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sku VARCHAR(100) NOT NULL UNIQUE,
  name VARCHAR(191) NOT NULL,
  description TEXT NULL,
  default_uom VARCHAR(20) NOT NULL,
  is_lot_tracked TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE item_lots (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  item_id BIGINT UNSIGNED NOT NULL,
  lot_no VARCHAR(100) NOT NULL,
  production_date DATE NULL,
  expiry_date DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_item_lot (item_id, lot_no),
  CONSTRAINT fk_item_lots_item FOREIGN KEY (item_id) REFERENCES items(id)
) ENGINE=InnoDB;

CREATE TABLE stocks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  warehouse_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NOT NULL,
  item_id BIGINT UNSIGNED NOT NULL,
  item_lot_id BIGINT UNSIGNED NULL,
  qty_on_hand DECIMAL(16,3) NOT NULL DEFAULT 0,
  qty_allocated DECIMAL(16,3) NOT NULL DEFAULT 0,
  qty_available DECIMAL(16,3) AS (qty_on_hand - qty_allocated) STORED,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_stock_lookup (warehouse_id, location_id, item_id, item_lot_id),
  CONSTRAINT fk_stocks_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
  CONSTRAINT fk_stocks_location FOREIGN KEY (location_id) REFERENCES locations(id),
  CONSTRAINT fk_stocks_item FOREIGN KEY (item_id) REFERENCES items(id),
  CONSTRAINT fk_stocks_item_lot FOREIGN KEY (item_lot_id) REFERENCES item_lots(id)
) ENGINE=InnoDB;

CREATE TABLE stock_movements (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  stock_id BIGINT UNSIGNED NOT NULL,
  type VARCHAR(50) NOT NULL,
  warehouse_id BIGINT UNSIGNED NOT NULL,
  item_id BIGINT UNSIGNED NOT NULL,
  item_lot_id BIGINT UNSIGNED NULL,
  from_location_id BIGINT UNSIGNED NULL,
  to_location_id BIGINT UNSIGNED NULL,
  quantity DECIMAL(16,3) NOT NULL,
  uom VARCHAR(20) NOT NULL,
  ref_type VARCHAR(100) NOT NULL,
  ref_id VARCHAR(100) NOT NULL,
  actor_user_id BIGINT UNSIGNED NULL,
  remarks VARCHAR(255) NULL,
  moved_at TIMESTAMP NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_ref (ref_type, ref_id, type),
  CONSTRAINT fk_movements_stock FOREIGN KEY (stock_id) REFERENCES stocks(id),
  CONSTRAINT fk_movements_actor FOREIGN KEY (actor_user_id) REFERENCES users(id),
  CONSTRAINT fk_movements_from_location FOREIGN KEY (from_location_id) REFERENCES locations(id),
  CONSTRAINT fk_movements_to_location FOREIGN KEY (to_location_id) REFERENCES locations(id),
  CONSTRAINT fk_movements_item FOREIGN KEY (item_id) REFERENCES items(id),
  CONSTRAINT fk_movements_item_lot FOREIGN KEY (item_lot_id) REFERENCES item_lots(id)
) ENGINE=InnoDB;

CREATE TABLE suppliers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(100) NOT NULL UNIQUE,
  name VARCHAR(191) NOT NULL,
  contact_name VARCHAR(191) NULL,
  phone VARCHAR(30) NULL,
  email VARCHAR(191) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE purchase_orders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  supplier_id BIGINT UNSIGNED NOT NULL,
  warehouse_id BIGINT UNSIGNED NOT NULL,
  po_no VARCHAR(100) NOT NULL UNIQUE,
  status VARCHAR(30) NOT NULL,
  eta DATE NULL,
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NOT NULL,
  approved_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_pos_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
  CONSTRAINT fk_pos_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
  CONSTRAINT fk_pos_created_by FOREIGN KEY (created_by) REFERENCES users(id),
  CONSTRAINT fk_pos_approved_by FOREIGN KEY (approved_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE po_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  purchase_order_id BIGINT UNSIGNED NOT NULL,
  item_id BIGINT UNSIGNED NOT NULL,
  ordered_qty DECIMAL(16,3) NOT NULL,
  received_qty DECIMAL(16,3) NOT NULL DEFAULT 0,
  uom VARCHAR(20) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_po_items_po FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id),
  CONSTRAINT fk_po_items_item FOREIGN KEY (item_id) REFERENCES items(id)
) ENGINE=InnoDB;

CREATE TABLE inbound_shipments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  purchase_order_id BIGINT UNSIGNED NOT NULL,
  asn_no VARCHAR(100) NULL,
  status VARCHAR(30) NOT NULL,
  scheduled_at DATETIME NULL,
  remarks TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_inbound_po FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id)
) ENGINE=InnoDB;

CREATE TABLE grn_headers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  inbound_shipment_id BIGINT UNSIGNED NOT NULL,
  grn_no VARCHAR(100) NOT NULL UNIQUE,
  received_at DATETIME NOT NULL,
  status VARCHAR(30) NOT NULL,
  received_by BIGINT UNSIGNED NOT NULL,
  verified_by BIGINT UNSIGNED NULL,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_grn_inbound FOREIGN KEY (inbound_shipment_id) REFERENCES inbound_shipments(id),
  CONSTRAINT fk_grn_received_by FOREIGN KEY (received_by) REFERENCES users(id),
  CONSTRAINT fk_grn_verified_by FOREIGN KEY (verified_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE grn_lines (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  grn_header_id BIGINT UNSIGNED NOT NULL,
  po_item_id BIGINT UNSIGNED NOT NULL,
  item_lot_id BIGINT UNSIGNED NULL,
  putaway_location_id BIGINT UNSIGNED NULL,
  received_qty DECIMAL(16,3) NOT NULL,
  rejected_qty DECIMAL(16,3) NOT NULL DEFAULT 0,
  uom VARCHAR(20) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_grn_lines_header FOREIGN KEY (grn_header_id) REFERENCES grn_headers(id),
  CONSTRAINT fk_grn_lines_po_item FOREIGN KEY (po_item_id) REFERENCES po_items(id),
  CONSTRAINT fk_grn_lines_item_lot FOREIGN KEY (item_lot_id) REFERENCES item_lots(id),
  CONSTRAINT fk_grn_lines_location FOREIGN KEY (putaway_location_id) REFERENCES locations(id)
) ENGINE=InnoDB;

CREATE TABLE customers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(100) NOT NULL UNIQUE,
  name VARCHAR(191) NOT NULL,
  phone VARCHAR(30) NULL,
  email VARCHAR(191) NULL,
  address TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE sales_orders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  warehouse_id BIGINT UNSIGNED NOT NULL,
  so_no VARCHAR(100) NOT NULL UNIQUE,
  status VARCHAR(30) NOT NULL,
  ship_by DATE NULL,
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NOT NULL,
  approved_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_so_customer FOREIGN KEY (customer_id) REFERENCES customers(id),
  CONSTRAINT fk_so_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
  CONSTRAINT fk_so_created_by FOREIGN KEY (created_by) REFERENCES users(id),
  CONSTRAINT fk_so_approved_by FOREIGN KEY (approved_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE so_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sales_order_id BIGINT UNSIGNED NOT NULL,
  item_id BIGINT UNSIGNED NOT NULL,
  ordered_qty DECIMAL(16,3) NOT NULL,
  allocated_qty DECIMAL(16,3) NOT NULL DEFAULT 0,
  uom VARCHAR(20) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_so_items_so FOREIGN KEY (sales_order_id) REFERENCES sales_orders(id),
  CONSTRAINT fk_so_items_item FOREIGN KEY (item_id) REFERENCES items(id)
) ENGINE=InnoDB;

CREATE TABLE outbound_shipments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sales_order_id BIGINT UNSIGNED NOT NULL,
  wave_no VARCHAR(100) NULL,
  status VARCHAR(30) NOT NULL,
  dispatched_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_outbound_so FOREIGN KEY (sales_order_id) REFERENCES sales_orders(id)
) ENGINE=InnoDB;

CREATE TABLE pick_lists (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  outbound_shipment_id BIGINT UNSIGNED NOT NULL,
  picklist_no VARCHAR(100) NOT NULL,
  picker_id BIGINT UNSIGNED NOT NULL,
  status VARCHAR(30) NOT NULL,
  started_at DATETIME NULL,
  completed_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_picklist (picklist_no),
  CONSTRAINT fk_pick_lists_outbound FOREIGN KEY (outbound_shipment_id) REFERENCES outbound_shipments(id),
  CONSTRAINT fk_pick_lists_picker FOREIGN KEY (picker_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE pick_lines (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pick_list_id BIGINT UNSIGNED NOT NULL,
  so_item_id BIGINT UNSIGNED NOT NULL,
  item_lot_id BIGINT UNSIGNED NULL,
  from_location_id BIGINT UNSIGNED NOT NULL,
  picked_qty DECIMAL(16,3) NOT NULL,
  confirmed_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_pick_lines_list FOREIGN KEY (pick_list_id) REFERENCES pick_lists(id),
  CONSTRAINT fk_pick_lines_so_item FOREIGN KEY (so_item_id) REFERENCES so_items(id),
  CONSTRAINT fk_pick_lines_item_lot FOREIGN KEY (item_lot_id) REFERENCES item_lots(id),
  CONSTRAINT fk_pick_lines_location FOREIGN KEY (from_location_id) REFERENCES locations(id),
  CONSTRAINT fk_pick_lines_confirmed FOREIGN KEY (confirmed_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE packages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  outbound_shipment_id BIGINT UNSIGNED NOT NULL,
  package_no VARCHAR(100) NOT NULL,
  weight DECIMAL(10,2) NULL,
  dimensions VARCHAR(100) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_package (outbound_shipment_id, package_no),
  CONSTRAINT fk_packages_outbound FOREIGN KEY (outbound_shipment_id) REFERENCES outbound_shipments(id)
) ENGINE=InnoDB;

CREATE TABLE shipments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  outbound_shipment_id BIGINT UNSIGNED NOT NULL,
  carrier VARCHAR(100) NULL,
  tracking_no VARCHAR(191) NULL,
  shipped_at DATETIME NULL,
  departed_at DATETIME NULL,
  delivered_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_tracking (tracking_no),
  CONSTRAINT fk_shipments_outbound FOREIGN KEY (outbound_shipment_id) REFERENCES outbound_shipments(id)
) ENGINE=InnoDB;

CREATE TABLE pods (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  shipment_id BIGINT UNSIGNED NOT NULL,
  signed_by VARCHAR(191) NOT NULL,
  signed_at DATETIME NOT NULL,
  photo_path VARCHAR(255) NULL,
  signature_path VARCHAR(255) NULL,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_pods_shipment FOREIGN KEY (shipment_id) REFERENCES shipments(id)
) ENGINE=InnoDB;

CREATE TABLE adjustments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  warehouse_id BIGINT UNSIGNED NOT NULL,
  adjustment_no VARCHAR(100) NOT NULL UNIQUE,
  type VARCHAR(30) NOT NULL,
  reason VARCHAR(191) NOT NULL,
  status VARCHAR(30) NOT NULL,
  requested_by BIGINT UNSIGNED NOT NULL,
  approved_by BIGINT UNSIGNED NULL,
  posted_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_adjustments_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
  CONSTRAINT fk_adjustments_requested FOREIGN KEY (requested_by) REFERENCES users(id),
  CONSTRAINT fk_adjustments_approved FOREIGN KEY (approved_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE adjustment_lines (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  adjustment_id BIGINT UNSIGNED NOT NULL,
  item_id BIGINT UNSIGNED NOT NULL,
  item_lot_id BIGINT UNSIGNED NULL,
  location_id BIGINT UNSIGNED NOT NULL,
  quantity_diff DECIMAL(16,3) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_adjustment_lines_adjustment FOREIGN KEY (adjustment_id) REFERENCES adjustments(id),
  CONSTRAINT fk_adjustment_lines_item FOREIGN KEY (item_id) REFERENCES items(id),
  CONSTRAINT fk_adjustment_lines_item_lot FOREIGN KEY (item_lot_id) REFERENCES item_lots(id),
  CONSTRAINT fk_adjustment_lines_location FOREIGN KEY (location_id) REFERENCES locations(id)
) ENGINE=InnoDB;

CREATE TABLE cycle_counts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  warehouse_id BIGINT UNSIGNED NOT NULL,
  cycle_no VARCHAR(100) NOT NULL UNIQUE,
  status VARCHAR(30) NOT NULL,
  scheduled_for DATETIME NULL,
  executed_by BIGINT UNSIGNED NULL,
  completed_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_cycle_counts_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
  CONSTRAINT fk_cycle_counts_executed FOREIGN KEY (executed_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE cycle_count_lines (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cycle_count_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NOT NULL,
  item_id BIGINT UNSIGNED NOT NULL,
  item_lot_id BIGINT UNSIGNED NULL,
  system_qty DECIMAL(16,3) NOT NULL,
  counted_qty DECIMAL(16,3) NOT NULL,
  variance_qty DECIMAL(16,3) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_cycle_count_lines_cycle FOREIGN KEY (cycle_count_id) REFERENCES cycle_counts(id),
  CONSTRAINT fk_cycle_count_lines_location FOREIGN KEY (location_id) REFERENCES locations(id),
  CONSTRAINT fk_cycle_count_lines_item FOREIGN KEY (item_id) REFERENCES items(id),
  CONSTRAINT fk_cycle_count_lines_item_lot FOREIGN KEY (item_lot_id) REFERENCES item_lots(id)
) ENGINE=InnoDB;

CREATE TABLE vehicles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  plate_no VARCHAR(50) NOT NULL UNIQUE,
  type VARCHAR(50) NULL,
  capacity VARCHAR(100) NULL,
  status VARCHAR(30) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE driver_assignments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  assignment_no VARCHAR(100) NOT NULL UNIQUE,
  driver_profile_id BIGINT UNSIGNED NOT NULL,
  vehicle_id BIGINT UNSIGNED NOT NULL,
  outbound_shipment_id BIGINT UNSIGNED NOT NULL,
  assigned_at DATETIME NOT NULL,
  status VARCHAR(30) NOT NULL,
  completed_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_driver_assignments_driver FOREIGN KEY (driver_profile_id) REFERENCES driver_profiles(id),
  CONSTRAINT fk_driver_assignments_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
  CONSTRAINT fk_driver_assignments_outbound FOREIGN KEY (outbound_shipment_id) REFERENCES outbound_shipments(id)
) ENGINE=InnoDB;

-- Convenience view to expose stock availability (optional)
CREATE OR REPLACE VIEW view_stock_balances AS
SELECT
  s.id AS stock_id,
  s.warehouse_id,
  s.location_id,
  s.item_id,
  s.item_lot_id,
  s.qty_on_hand,
  s.qty_allocated,
  s.qty_available
FROM stocks s;
