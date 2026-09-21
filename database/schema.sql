-- Compatible MySQL 8 / SQLite; scripts/migrate.php converts identity columns for MySQL.
CREATE TABLE IF NOT EXISTS users (
 id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(100) NOT NULL, email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL, role VARCHAR(20) NOT NULL DEFAULT 'user', privacy_version VARCHAR(30) NOT NULL DEFAULT '2026-09-v1', created_at DATETIME NOT NULL
);
CREATE TABLE IF NOT EXISTS categories (id INTEGER PRIMARY KEY, name VARCHAR(80) NOT NULL, icon VARCHAR(40) NOT NULL);
CREATE TABLE IF NOT EXISTS plans (
 id INTEGER PRIMARY KEY, name VARCHAR(60) NOT NULL, price_cents INTEGER NOT NULL,
 photo_limit INTEGER NOT NULL, post_limit INTEGER NOT NULL, priority INTEGER NOT NULL, promotion_limit INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS businesses (
 id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL UNIQUE, category_id INTEGER NOT NULL, plan_id INTEGER NOT NULL,
 name VARCHAR(150) NOT NULL, description TEXT NOT NULL, purpose TEXT NOT NULL, neighborhood VARCHAR(100) NOT NULL,
 address VARCHAR(255) NOT NULL, phone VARCHAR(20) NOT NULL, website VARCHAR(500) NOT NULL DEFAULT '',
 instagram VARCHAR(500) NOT NULL DEFAULT '', hours VARCHAR(255) NOT NULL DEFAULT '',
 logo_url VARCHAR(500) NOT NULL DEFAULT '', cover_url VARCHAR(500) NOT NULL DEFAULT '',
 status VARCHAR(30) NOT NULL DEFAULT 'pending', paid_until DATETIME NULL, provider_customer_id VARCHAR(100) NULL,
 is_demo INTEGER NOT NULL DEFAULT 0, kind VARCHAR(20) NOT NULL DEFAULT 'company', maps_url VARCHAR(500) NOT NULL DEFAULT '', publish_address INTEGER NOT NULL DEFAULT 1, whatsapp_consent INTEGER NOT NULL DEFAULT 0, consent_at DATETIME NULL, trial_until DATETIME NULL, manual_until DATETIME NULL, activation_deadline DATETIME NULL, created_at DATETIME NOT NULL,
 FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(category_id) REFERENCES categories(id), FOREIGN KEY(plan_id) REFERENCES plans(id)
);
CREATE TABLE IF NOT EXISTS photos (
 id INTEGER PRIMARY KEY AUTOINCREMENT, business_id INTEGER NOT NULL, url VARCHAR(500) NOT NULL,
 FOREIGN KEY(business_id) REFERENCES businesses(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS posts (
 id INTEGER PRIMARY KEY AUTOINCREMENT, business_id INTEGER NOT NULL, title VARCHAR(100) NOT NULL, body TEXT NOT NULL, created_at DATETIME NOT NULL,
 FOREIGN KEY(business_id) REFERENCES businesses(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS reviews (
 id INTEGER PRIMARY KEY AUTOINCREMENT, business_id INTEGER NOT NULL, user_id INTEGER NOT NULL,
 rating INTEGER NOT NULL CHECK(rating BETWEEN 1 AND 5), comment TEXT NOT NULL, created_at DATETIME NOT NULL,
 UNIQUE(business_id,user_id), FOREIGN KEY(business_id) REFERENCES businesses(id), FOREIGN KEY(user_id) REFERENCES users(id)
);
CREATE TABLE IF NOT EXISTS subscriptions (
 id INTEGER PRIMARY KEY AUTOINCREMENT, business_id INTEGER NOT NULL, plan_id INTEGER NOT NULL,
 provider_id VARCHAR(100) UNIQUE, billing_type VARCHAR(20) NOT NULL, status VARCHAR(30) NOT NULL,
 created_at DATETIME NOT NULL, FOREIGN KEY(business_id) REFERENCES businesses(id), FOREIGN KEY(plan_id) REFERENCES plans(id)
);
CREATE TABLE IF NOT EXISTS payments (
 id INTEGER PRIMARY KEY AUTOINCREMENT, subscription_id INTEGER NOT NULL, provider_id VARCHAR(100) NOT NULL UNIQUE,
 amount_cents INTEGER NOT NULL, status VARCHAR(30) NOT NULL, due_date VARCHAR(10) NOT NULL, paid_at DATETIME NULL, valid_until DATETIME NULL,
 invoice_url VARCHAR(500) NULL, FOREIGN KEY(subscription_id) REFERENCES subscriptions(id)
);
CREATE TABLE IF NOT EXISTS webhook_events (id VARCHAR(150) PRIMARY KEY, event_type VARCHAR(80) NOT NULL, processed_at DATETIME NOT NULL);
CREATE TABLE IF NOT EXISTS rate_limits (bucket VARCHAR(100) PRIMARY KEY, attempts INTEGER NOT NULL, reset_at INTEGER NOT NULL);
CREATE INDEX idx_business_visibility ON businesses(status,paid_until,plan_id);
CREATE INDEX idx_posts_business ON posts(business_id,created_at);
CREATE INDEX idx_subscriptions_business ON subscriptions(business_id,status);

CREATE TABLE IF NOT EXISTS settings (setting_key VARCHAR(80) PRIMARY KEY, value TEXT NOT NULL);
CREATE TABLE IF NOT EXISTS promotions (id INTEGER PRIMARY KEY AUTOINCREMENT, business_id INTEGER NOT NULL, title VARCHAR(100) NOT NULL, description TEXT NOT NULL, price_cents INTEGER NOT NULL, original_price_cents INTEGER NULL, image_url VARCHAR(500) NOT NULL, starts_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL, FOREIGN KEY(business_id) REFERENCES businesses(id));
CREATE TABLE IF NOT EXISTS promotion_images (id INTEGER PRIMARY KEY AUTOINCREMENT, business_id INTEGER NOT NULL, url VARCHAR(500) NOT NULL UNIQUE, created_at DATETIME NOT NULL, FOREIGN KEY(business_id) REFERENCES businesses(id));
CREATE TABLE IF NOT EXISTS admin_audit (id INTEGER PRIMARY KEY AUTOINCREMENT, admin_id INTEGER NOT NULL, business_id INTEGER NULL, action VARCHAR(80) NOT NULL, reason TEXT NOT NULL, created_at DATETIME NOT NULL, FOREIGN KEY(admin_id) REFERENCES users(id));
CREATE TABLE IF NOT EXISTS notifications (id INTEGER PRIMARY KEY AUTOINCREMENT, business_id INTEGER NOT NULL, dedup_key VARCHAR(190) NOT NULL UNIQUE, message TEXT NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'pending', created_at DATETIME NOT NULL, FOREIGN KEY(business_id) REFERENCES businesses(id));
CREATE TABLE IF NOT EXISTS privacy_requests (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, request_type VARCHAR(40) NOT NULL, details TEXT NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'open', resolution TEXT NULL, created_at DATETIME NOT NULL, resolved_at DATETIME NULL, FOREIGN KEY(user_id) REFERENCES users(id));
CREATE INDEX idx_promotion_dates ON promotions(business_id,expires_at);
CREATE INDEX idx_promotion_images_business ON promotion_images(business_id);
