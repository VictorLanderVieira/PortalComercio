CREATE TABLE IF NOT EXISTS advertisements (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 business_id INTEGER NOT NULL,
 title VARCHAR(100) NOT NULL,
 description VARCHAR(600) NOT NULL,
 image_url VARCHAR(255) NOT NULL DEFAULT '',
 logo_url VARCHAR(255) NOT NULL DEFAULT '',
 offer_price_cents INTEGER NULL,
 monthly_amount_cents INTEGER NOT NULL,
 status VARCHAR(20) NOT NULL DEFAULT 'draft',
 starts_at DATETIME NULL,
 expires_at DATETIME NULL,
 paid_at DATETIME NULL,
 bank_reference VARCHAR(100) NULL UNIQUE,
 confirmed_by INTEGER NULL,
 created_at DATETIME NOT NULL,
 FOREIGN KEY(business_id) REFERENCES businesses(id),
 FOREIGN KEY(confirmed_by) REFERENCES users(id)
);
CREATE INDEX advertisements_visibility ON advertisements(status,starts_at,expires_at);
INSERT INTO settings(setting_key,value) VALUES('advertising_price_cents','3500');
