SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS visit_part, visit, invoice, contact_message, service_request, warranty, ac_unit,
  autocad_plan, project, spare_part, technician, service, customer,
  product, category, admin, company, setting;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE customer (
  customer_id  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(150) NOT NULL,
  type         ENUM('individual','company','institution') NOT NULL DEFAULT 'individual',
  address      VARCHAR(255) NULL,
  email        VARCHAR(150) NULL,
  phone        VARCHAR(30)  NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_customer_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE service (
  service_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  service_name  VARCHAR(150) NOT NULL,
  description   TEXT NULL,
  category      ENUM('installation','maintenance','gas','central','emergency','contract') NOT NULL DEFAULT 'maintenance',
  base_price    DECIMAL(10,2) NULL,
  price_unit    VARCHAR(40) NULL,
  icon          VARCHAR(40) NOT NULL DEFAULT 'build_circle',
  is_active     TINYINT(1) NOT NULL DEFAULT 1,
  sort_order    INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE service_request (
  request_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id   INT UNSIGNED NOT NULL,
  service_id    INT UNSIGNED NOT NULL,
  request_type  ENUM('maintenance','installation','emergency','inquiry') NOT NULL DEFAULT 'maintenance',
  status        ENUM('new','in_progress','completed','cancelled') NOT NULL DEFAULT 'new',
  priority      ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  phone         VARCHAR(30)  NULL,
  address       VARCHAR(255) NULL,
  preferred_date DATE NULL,
  date_created  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  message       TEXT NULL,
  CONSTRAINT fk_req_customer FOREIGN KEY (customer_id) REFERENCES customer(customer_id) ON DELETE CASCADE,
  CONSTRAINT fk_req_service  FOREIGN KEY (service_id)  REFERENCES service(service_id)  ON DELETE RESTRICT,
  KEY idx_req_type_status (request_type, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contact_message (
  message_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id   INT UNSIGNED NOT NULL,
  subject       VARCHAR(200) NOT NULL,
  message       TEXT NOT NULL,
  date_created  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status        ENUM('unread','read','replied','archived') NOT NULL DEFAULT 'unread',
  CONSTRAINT fk_msg_customer FOREIGN KEY (customer_id) REFERENCES customer(customer_id) ON DELETE CASCADE,
  KEY idx_msg_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE technician (
  technician_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(150) NOT NULL,
  phone         VARCHAR(30) NOT NULL,
  specialty     VARCHAR(150) NULL,
  availability  ENUM('available','busy','off') NOT NULL DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE visit (
  visit_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_id      INT UNSIGNED NOT NULL,
  technician_id   INT UNSIGNED NOT NULL,
  scheduled_date  DATE NOT NULL,
  arrival_time    TIME NULL,
  notes           TEXT NULL,
  CONSTRAINT fk_visit_request    FOREIGN KEY (request_id)    REFERENCES service_request(request_id) ON DELETE CASCADE,
  CONSTRAINT fk_visit_technician FOREIGN KEY (technician_id) REFERENCES technician(technician_id)    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE spare_part (
  part_id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  part_number     VARCHAR(60) NOT NULL,
  part_name       VARCHAR(150) NOT NULL,
  price           DECIMAL(10,2) NOT NULL DEFAULT 0,
  stock_quantity  INT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_part_number (part_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE visit_part (
  visit_id  INT UNSIGNED NOT NULL,
  part_id   INT UNSIGNED NOT NULL,
  quantity  INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (visit_id, part_id),
  CONSTRAINT fk_vp_visit FOREIGN KEY (visit_id) REFERENCES visit(visit_id)      ON DELETE CASCADE,
  CONSTRAINT fk_vp_part  FOREIGN KEY (part_id)  REFERENCES spare_part(part_id)  ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE project (
  project_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id  INT UNSIGNED NOT NULL,
  title        VARCHAR(200) NOT NULL,
  location     VARCHAR(200) NULL,
  type         ENUM('residential','commercial','medical','industrial','governmental') NOT NULL DEFAULT 'residential',
  start_date   DATE NULL,
  end_date     DATE NULL,
  status       ENUM('planning','in_progress','completed','on_hold') NOT NULL DEFAULT 'planning',
  CONSTRAINT fk_project_customer FOREIGN KEY (customer_id) REFERENCES customer(customer_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE autocad_plan (
  plan_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id     INT UNSIGNED NOT NULL,
  file_url       VARCHAR(500) NOT NULL,
  version        VARCHAR(20) NOT NULL DEFAULT '1.0',
  approval_date  DATE NULL,
  CONSTRAINT fk_plan_project FOREIGN KEY (project_id) REFERENCES project(project_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ac_unit (
  unit_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id     INT UNSIGNED NOT NULL,
  type           ENUM('split','central_vrf','cassette','floor_standing','window','ducted') NOT NULL DEFAULT 'split',
  model          VARCHAR(100) NULL,
  brand          VARCHAR(80) NULL,
  capacity_btu   INT UNSIGNED NULL,
  energy_rating  VARCHAR(10) NULL,
  install_date   DATE NULL,
  CONSTRAINT fk_unit_project FOREIGN KEY (project_id) REFERENCES project(project_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE warranty (
  warranty_id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  unit_id                INT UNSIGNED NOT NULL,
  system_warranty_years  TINYINT UNSIGNED NOT NULL DEFAULT 2,
  motor_warranty_years   TINYINT UNSIGNED NOT NULL DEFAULT 5,
  start_date             DATE NOT NULL,
  end_date               DATE NULL,
  UNIQUE KEY uq_warranty_unit (unit_id),
  CONSTRAINT fk_warranty_unit FOREIGN KEY (unit_id) REFERENCES ac_unit(unit_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE invoice (
  invoice_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_id   INT UNSIGNED NULL,
  project_id   INT UNSIGNED NULL,
  customer_id  INT UNSIGNED NOT NULL,
  issue_date   DATE NOT NULL,
  amount       DECIMAL(12,2) NOT NULL DEFAULT 0,
  status       ENUM('unpaid','partial','paid','cancelled') NOT NULL DEFAULT 'unpaid',
  CONSTRAINT fk_inv_request  FOREIGN KEY (request_id)  REFERENCES service_request(request_id) ON DELETE SET NULL,
  CONSTRAINT fk_inv_project  FOREIGN KEY (project_id)  REFERENCES project(project_id)         ON DELETE SET NULL,
  CONSTRAINT fk_inv_customer FOREIGN KEY (customer_id) REFERENCES customer(customer_id)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE category (
  category_id  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(150) NOT NULL,
  description  VARCHAR(300) NULL,
  icon         VARCHAR(40) NOT NULL DEFAULT 'ac_unit',
  sort_order   INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE product (
  product_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id    INT UNSIGNED NOT NULL,
  name           VARCHAR(200) NOT NULL,
  brand          VARCHAR(80) NOT NULL,
  model          VARCHAR(100) NULL,
  capacity_btu   INT UNSIGNED NULL,
  energy_rating  VARCHAR(10) NULL,
  price          DECIMAL(10,2) NOT NULL DEFAULT 0,
  is_available   TINYINT(1) NOT NULL DEFAULT 1,
  is_featured    TINYINT(1) NOT NULL DEFAULT 0,
  description    TEXT NULL,
  specs          TEXT NULL,
  image          VARCHAR(500) NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_product_category FOREIGN KEY (category_id) REFERENCES category(category_id) ON DELETE RESTRICT,
  KEY idx_product_brand (brand)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin (
  admin_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(150) NOT NULL,
  email          VARCHAR(150) NOT NULL,
  password_hash  VARCHAR(255) NOT NULL,
  role           ENUM('super_admin','admin') NOT NULL DEFAULT 'admin',
  remember_token VARCHAR(64) NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_admin_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE company (
  company_id   TINYINT UNSIGNED PRIMARY KEY DEFAULT 1,
  name         VARCHAR(150) NOT NULL,
  description  TEXT NULL,
  logo         VARCHAR(500) NULL,
  phone        VARCHAR(30)  NOT NULL,
  email        VARCHAR(150) NOT NULL,
  address      VARCHAR(255) NOT NULL,
  CONSTRAINT chk_company_single CHECK (company_id = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE setting (
  setting_key    VARCHAR(60) PRIMARY KEY,
  setting_value  TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
