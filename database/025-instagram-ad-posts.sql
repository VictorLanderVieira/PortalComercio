CREATE TABLE instagram_ad_posts (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 advertisement_id INTEGER NOT NULL UNIQUE,
 status VARCHAR(20) NOT NULL DEFAULT 'pending',
 caption TEXT NOT NULL,
 image_url VARCHAR(255) NOT NULL,
 container_id VARCHAR(100) NULL,
 media_id VARCHAR(100) NULL,
 permalink VARCHAR(500) NULL,
 attempts INTEGER NOT NULL DEFAULT 0,
 last_error VARCHAR(500) NULL,
 consent_at DATETIME NOT NULL,
 created_at DATETIME NOT NULL,
 published_at DATETIME NULL,
 FOREIGN KEY(advertisement_id) REFERENCES advertisements(id)
);
CREATE INDEX instagram_ad_posts_queue ON instagram_ad_posts(status,id);
