-- Sarzedo por perto: MySQL 8, banco vazio.
SET NAMES utf8mb4;
-- Compatible MySQL 8 / SQLite; scripts/migrate.php converts identity columns for MySQL.
CREATE TABLE IF NOT EXISTS users (
 id INTEGER PRIMARY KEY AUTO_INCREMENT, name VARCHAR(100) NOT NULL, email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL, role VARCHAR(20) NOT NULL DEFAULT 'user', privacy_version VARCHAR(30) NOT NULL DEFAULT '2026-09-v1', created_at DATETIME NOT NULL
);
CREATE TABLE IF NOT EXISTS categories (id INTEGER PRIMARY KEY, name VARCHAR(80) NOT NULL, icon VARCHAR(40) NOT NULL);
CREATE TABLE IF NOT EXISTS plans (
 id INTEGER PRIMARY KEY, name VARCHAR(60) NOT NULL, price_cents INTEGER NOT NULL,
 photo_limit INTEGER NOT NULL, post_limit INTEGER NOT NULL, priority INTEGER NOT NULL, promotion_limit INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS businesses (
 id INTEGER PRIMARY KEY AUTO_INCREMENT, user_id INTEGER NOT NULL UNIQUE, category_id INTEGER NOT NULL, plan_id INTEGER NOT NULL,
 name VARCHAR(150) NOT NULL, description TEXT NOT NULL, purpose TEXT NOT NULL, neighborhood VARCHAR(100) NOT NULL,
 address VARCHAR(255) NOT NULL, phone VARCHAR(20) NOT NULL, website VARCHAR(500) NOT NULL DEFAULT '',
 instagram VARCHAR(500) NOT NULL DEFAULT '', hours VARCHAR(255) NOT NULL DEFAULT '',
 logo_url VARCHAR(500) NOT NULL DEFAULT '', cover_url VARCHAR(500) NOT NULL DEFAULT '',
 status VARCHAR(30) NOT NULL DEFAULT 'pending', paid_until DATETIME NULL, provider_customer_id VARCHAR(100) NULL,
 is_demo INTEGER NOT NULL DEFAULT 0, kind VARCHAR(20) NOT NULL DEFAULT 'company', maps_url VARCHAR(500) NOT NULL DEFAULT '', publish_address INTEGER NOT NULL DEFAULT 1, whatsapp_consent INTEGER NOT NULL DEFAULT 0, consent_at DATETIME NULL, trial_until DATETIME NULL, manual_until DATETIME NULL, activation_deadline DATETIME NULL, created_at DATETIME NOT NULL,
 FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(category_id) REFERENCES categories(id), FOREIGN KEY(plan_id) REFERENCES plans(id)
);
CREATE TABLE IF NOT EXISTS photos (
 id INTEGER PRIMARY KEY AUTO_INCREMENT, business_id INTEGER NOT NULL, url VARCHAR(500) NOT NULL,
 FOREIGN KEY(business_id) REFERENCES businesses(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS posts (
 id INTEGER PRIMARY KEY AUTO_INCREMENT, business_id INTEGER NOT NULL, title VARCHAR(100) NOT NULL, body TEXT NOT NULL, created_at DATETIME NOT NULL,
 FOREIGN KEY(business_id) REFERENCES businesses(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS reviews (
 id INTEGER PRIMARY KEY AUTO_INCREMENT, business_id INTEGER NOT NULL, user_id INTEGER NOT NULL,
 rating INTEGER NOT NULL CHECK(rating BETWEEN 1 AND 5), comment TEXT NOT NULL, created_at DATETIME NOT NULL,
 UNIQUE(business_id,user_id), FOREIGN KEY(business_id) REFERENCES businesses(id), FOREIGN KEY(user_id) REFERENCES users(id)
);
CREATE TABLE IF NOT EXISTS subscriptions (
 id INTEGER PRIMARY KEY AUTO_INCREMENT, business_id INTEGER NOT NULL, plan_id INTEGER NOT NULL,
 provider_id VARCHAR(100) UNIQUE, billing_type VARCHAR(20) NOT NULL, status VARCHAR(30) NOT NULL,
 created_at DATETIME NOT NULL, FOREIGN KEY(business_id) REFERENCES businesses(id), FOREIGN KEY(plan_id) REFERENCES plans(id)
);
CREATE TABLE IF NOT EXISTS payments (
 id INTEGER PRIMARY KEY AUTO_INCREMENT, subscription_id INTEGER NOT NULL, provider_id VARCHAR(100) NOT NULL UNIQUE,
 amount_cents INTEGER NOT NULL, status VARCHAR(30) NOT NULL, due_date VARCHAR(10) NOT NULL, paid_at DATETIME NULL, valid_until DATETIME NULL,
 invoice_url VARCHAR(500) NULL, FOREIGN KEY(subscription_id) REFERENCES subscriptions(id)
);
CREATE TABLE IF NOT EXISTS webhook_events (id VARCHAR(150) PRIMARY KEY, event_type VARCHAR(80) NOT NULL, processed_at DATETIME NOT NULL);
CREATE TABLE IF NOT EXISTS rate_limits (bucket VARCHAR(100) PRIMARY KEY, attempts INTEGER NOT NULL, reset_at INTEGER NOT NULL);
CREATE INDEX idx_business_visibility ON businesses(status,paid_until,plan_id);
CREATE INDEX idx_posts_business ON posts(business_id,created_at);
CREATE INDEX idx_subscriptions_business ON subscriptions(business_id,status);

CREATE TABLE IF NOT EXISTS settings (setting_key VARCHAR(80) PRIMARY KEY, value TEXT NOT NULL);
CREATE TABLE IF NOT EXISTS promotions (id INTEGER PRIMARY KEY AUTO_INCREMENT, business_id INTEGER NOT NULL, title VARCHAR(100) NOT NULL, description TEXT NOT NULL, price_cents INTEGER NOT NULL, original_price_cents INTEGER NULL, image_url VARCHAR(500) NOT NULL, starts_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL, FOREIGN KEY(business_id) REFERENCES businesses(id));
CREATE TABLE IF NOT EXISTS promotion_images (id INTEGER PRIMARY KEY AUTO_INCREMENT, business_id INTEGER NOT NULL, url VARCHAR(500) NOT NULL UNIQUE, created_at DATETIME NOT NULL, FOREIGN KEY(business_id) REFERENCES businesses(id));
CREATE TABLE IF NOT EXISTS admin_audit (id INTEGER PRIMARY KEY AUTO_INCREMENT, admin_id INTEGER NOT NULL, business_id INTEGER NULL, action VARCHAR(80) NOT NULL, reason TEXT NOT NULL, created_at DATETIME NOT NULL, FOREIGN KEY(admin_id) REFERENCES users(id));
CREATE TABLE IF NOT EXISTS notifications (id INTEGER PRIMARY KEY AUTO_INCREMENT, business_id INTEGER NOT NULL, dedup_key VARCHAR(190) NOT NULL UNIQUE, message TEXT NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'pending', created_at DATETIME NOT NULL, FOREIGN KEY(business_id) REFERENCES businesses(id));
CREATE TABLE IF NOT EXISTS privacy_requests (id INTEGER PRIMARY KEY AUTO_INCREMENT, user_id INTEGER NOT NULL, request_type VARCHAR(40) NOT NULL, details TEXT NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'open', resolution TEXT NULL, created_at DATETIME NOT NULL, resolved_at DATETIME NULL, FOREIGN KEY(user_id) REFERENCES users(id));
CREATE INDEX idx_promotion_dates ON promotions(business_id,expires_at);
CREATE INDEX idx_promotion_images_business ON promotion_images(business_id);

ALTER TABLE users ADD COLUMN google_sub VARCHAR(255) NULL;
CREATE UNIQUE INDEX idx_users_google_sub ON users(google_sub);
ALTER TABLE businesses ADD COLUMN interest_plan_id INTEGER NULL;
ALTER TABLE businesses ADD COLUMN facebook_url VARCHAR(500) NOT NULL DEFAULT '';
ALTER TABLE businesses ADD COLUMN notification_channel VARCHAR(20) NOT NULL DEFAULT 'email';
UPDATE businesses SET interest_plan_id=plan_id WHERE interest_plan_id IS NULL;
ALTER TABLE payments ADD COLUMN refunded_at DATETIME NULL;
CREATE TABLE IF NOT EXISTS deliveries (
 id INTEGER PRIMARY KEY AUTO_INCREMENT, notification_id INTEGER NOT NULL, channel VARCHAR(20) NOT NULL,
 status VARCHAR(30) NOT NULL DEFAULT 'pending', attempts INTEGER NOT NULL DEFAULT 0,
 provider_id VARCHAR(150) NULL, last_error VARCHAR(255) NULL, next_attempt_at DATETIME NULL,
 sent_at DATETIME NULL, updated_at DATETIME NOT NULL, UNIQUE(notification_id,channel),
 FOREIGN KEY(notification_id) REFERENCES notifications(id)
);
CREATE INDEX idx_deliveries_pending ON deliveries(status,next_attempt_at);

ALTER TABLE plans ADD COLUMN promo_price_cents INTEGER NULL;
ALTER TABLE plans ADD COLUMN promo_start DATETIME NULL;
ALTER TABLE plans ADD COLUMN promo_end DATETIME NULL;
ALTER TABLE subscriptions ADD COLUMN amount_cents INTEGER NULL;
UPDATE subscriptions SET amount_cents=COALESCE((SELECT p.amount_cents FROM payments p WHERE p.subscription_id=subscriptions.id ORDER BY p.id DESC LIMIT 1),(SELECT price_cents FROM plans WHERE plans.id=subscriptions.plan_id));

CREATE TABLE IF NOT EXISTS manual_pix_payments (payment_id INTEGER PRIMARY KEY, payload TEXT NOT NULL, recipient_name VARCHAR(25) NOT NULL, txid VARCHAR(25) NOT NULL UNIQUE, bank_reference VARCHAR(100) NULL UNIQUE, confirmed_by INTEGER NULL, confirmed_at DATETIME NULL, FOREIGN KEY(payment_id) REFERENCES payments(id), FOREIGN KEY(confirmed_by) REFERENCES users(id));

CREATE TABLE IF NOT EXISTS portal_metric_totals (id INTEGER PRIMARY KEY, visits INTEGER NOT NULL DEFAULT 0, searches INTEGER NOT NULL DEFAULT 0, started_at DATETIME NOT NULL);
CREATE TABLE IF NOT EXISTS portal_metric_days (metric_date DATE PRIMARY KEY, visits INTEGER NOT NULL DEFAULT 0, searches INTEGER NOT NULL DEFAULT 0);

INSERT INTO plans(id,name,price_cents,photo_limit,post_limit,priority,promotion_limit) VALUES(4,'Free',0,0,0,0,0);

ALTER TABLE businesses ADD COLUMN reviews_opt_in INTEGER NOT NULL DEFAULT 1;

ALTER TABLE businesses ADD COLUMN free_cover_key VARCHAR(40) NOT NULL DEFAULT '';
INSERT INTO categories(id,name,icon) VALUES(9,'Academia / Personal Trainer','fitness');

INSERT INTO categories VALUES (1,'Alimentação','coffee'),(2,'Saúde e bem-estar','heart'),(3,'Beleza','sparkles'),(4,'Serviços','tool'),(5,'Compras','bag'),(6,'Casa e construção','home'),(7,'Autônomos','user'),(8,'Farmácias','plus');
INSERT INTO plans(id,name,price_cents,photo_limit,post_limit,priority,promotion_limit) VALUES (1,'Essencial',5000,5,2,1,0),(2,'Destaque',7500,12,8,2,2),(3,'Premium',10000,25,20,3,5);
INSERT INTO settings VALUES ('launch_start',''),('launch_end',''),('privacy_email',''),('controller_name',''),('support_whatsapp','31987671102');
CREATE TABLE IF NOT EXISTS schema_migrations (version INTEGER PRIMARY KEY, applied_at DATETIME NOT NULL);
INSERT INTO schema_migrations VALUES(2,NOW()),(3,NOW()),(4,NOW()),(5,NOW()),(6,NOW()),(7,NOW()),(8,NOW()),(9,NOW());
INSERT INTO portal_metric_totals(id,started_at) VALUES(1,NOW());

