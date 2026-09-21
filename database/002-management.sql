ALTER TABLE users ADD COLUMN google_sub VARCHAR(255) NULL;
CREATE UNIQUE INDEX idx_users_google_sub ON users(google_sub);
ALTER TABLE businesses ADD COLUMN interest_plan_id INTEGER NULL;
ALTER TABLE businesses ADD COLUMN facebook_url VARCHAR(500) NOT NULL DEFAULT '';
ALTER TABLE businesses ADD COLUMN notification_channel VARCHAR(20) NOT NULL DEFAULT 'email';
UPDATE businesses SET interest_plan_id=plan_id WHERE interest_plan_id IS NULL;
ALTER TABLE payments ADD COLUMN refunded_at DATETIME NULL;
CREATE TABLE IF NOT EXISTS deliveries (
 id INTEGER PRIMARY KEY AUTOINCREMENT, notification_id INTEGER NOT NULL, channel VARCHAR(20) NOT NULL,
 status VARCHAR(30) NOT NULL DEFAULT 'pending', attempts INTEGER NOT NULL DEFAULT 0,
 provider_id VARCHAR(150) NULL, last_error VARCHAR(255) NULL, next_attempt_at DATETIME NULL,
 sent_at DATETIME NULL, updated_at DATETIME NOT NULL, UNIQUE(notification_id,channel),
 FOREIGN KEY(notification_id) REFERENCES notifications(id)
);
CREATE INDEX idx_deliveries_pending ON deliveries(status,next_attempt_at);
