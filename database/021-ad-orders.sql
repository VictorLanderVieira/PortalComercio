CREATE TABLE ad_orders (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 business_id INTEGER NOT NULL,
 amount_cents INTEGER NOT NULL,
 provider VARCHAR(20) NOT NULL,
 provider_id VARCHAR(100) NULL UNIQUE,
 status VARCHAR(20) NOT NULL DEFAULT 'creating',
 payload TEXT NULL,
 due_date DATE NOT NULL,
 expires_at DATETIME NOT NULL,
 image_url VARCHAR(255) NOT NULL DEFAULT '',
 logo_url VARCHAR(255) NOT NULL DEFAULT '',
 paid_at DATETIME NULL,
 refunded_at DATETIME NULL,
 bank_reference VARCHAR(100) NULL UNIQUE,
 confirmed_by INTEGER NULL,
 created_at DATETIME NOT NULL,
 FOREIGN KEY(business_id) REFERENCES businesses(id),
 FOREIGN KEY(confirmed_by) REFERENCES users(id)
);
CREATE INDEX ad_orders_business_status ON ad_orders(business_id,status,id);
ALTER TABLE advertisements ADD COLUMN order_id INTEGER NULL;
CREATE UNIQUE INDEX advertisements_order ON advertisements(order_id);
