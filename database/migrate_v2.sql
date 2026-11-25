SET NAMES utf8mb4;

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

INSERT INTO company (company_id, name, description, logo, phone, email, address)
SELECT 1,
  COALESCE(MAX(CASE WHEN setting_key = 'company_name'        THEN setting_value END), 'شركة بيسان للتكييف والتبريد'),
  MAX(CASE WHEN setting_key = 'company_description' THEN setting_value END),
  MAX(CASE WHEN setting_key = 'logo'                THEN setting_value END),
  COALESCE(MAX(CASE WHEN setting_key = 'phone'   THEN setting_value END), ''),
  COALESCE(MAX(CASE WHEN setting_key = 'email'   THEN setting_value END), ''),
  COALESCE(MAX(CASE WHEN setting_key = 'address' THEN setting_value END), '')
FROM setting;

DELETE FROM setting WHERE setting_key IN ('company_name', 'company_description', 'logo', 'phone', 'email', 'address');

ALTER TABLE admin ADD COLUMN role ENUM('super_admin','admin') NOT NULL DEFAULT 'admin' AFTER password_hash;
UPDATE admin SET role = 'super_admin';

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

INSERT INTO contact_message (customer_id, subject, message, date_created, status)
SELECT customer_id, COALESCE(subject, 'بدون موضوع'), COALESCE(message, ''), date_created,
       CASE status WHEN 'new' THEN 'unread' WHEN 'in_progress' THEN 'read' WHEN 'completed' THEN 'replied' ELSE 'archived' END
FROM service_request WHERE request_type = 'contact' ORDER BY date_created;

UPDATE invoice SET request_id = NULL WHERE request_id IN (SELECT request_id FROM (SELECT request_id FROM service_request WHERE request_type = 'contact') x);
DELETE FROM service_request WHERE request_type = 'contact';

ALTER TABLE service_request
  ADD COLUMN phone          VARCHAR(30)  NULL AFTER priority,
  ADD COLUMN address        VARCHAR(255) NULL AFTER phone,
  ADD COLUMN preferred_date DATE NULL AFTER address;

UPDATE service_request r JOIN customer c ON c.customer_id = r.customer_id
SET r.phone = c.phone, r.address = c.address;

UPDATE service_request r
SET r.preferred_date = (SELECT MIN(v.scheduled_date) FROM visit v WHERE v.request_id = r.request_id);

UPDATE service_request SET service_id = (SELECT MIN(service_id) FROM service) WHERE service_id IS NULL;

ALTER TABLE service_request DROP FOREIGN KEY fk_req_service;
ALTER TABLE service_request
  DROP COLUMN subject,
  MODIFY request_type ENUM('maintenance','installation','emergency','inquiry') NOT NULL DEFAULT 'maintenance',
  MODIFY service_id INT UNSIGNED NOT NULL,
  MODIFY message TEXT NULL;
ALTER TABLE service_request
  ADD CONSTRAINT fk_req_service FOREIGN KEY (service_id) REFERENCES service(service_id) ON DELETE RESTRICT;

DELETE FROM visit WHERE technician_id IS NULL;
ALTER TABLE visit DROP FOREIGN KEY fk_visit_technician;
ALTER TABLE visit MODIFY technician_id INT UNSIGNED NOT NULL;
ALTER TABLE visit
  ADD CONSTRAINT fk_visit_technician FOREIGN KEY (technician_id) REFERENCES technician(technician_id) ON DELETE RESTRICT;

UPDATE product SET category_id = (SELECT MIN(category_id) FROM category) WHERE category_id IS NULL;
ALTER TABLE product DROP FOREIGN KEY fk_product_category;
ALTER TABLE product MODIFY category_id INT UNSIGNED NOT NULL;
ALTER TABLE product
  ADD CONSTRAINT fk_product_category FOREIGN KEY (category_id) REFERENCES category(category_id) ON DELETE RESTRICT;
