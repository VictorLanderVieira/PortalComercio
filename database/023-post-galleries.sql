ALTER TABLE plans ADD COLUMN post_image_limit INTEGER NOT NULL DEFAULT 1;
UPDATE plans SET post_image_limit=CASE id WHEN 1 THEN 4 WHEN 2 THEN 6 WHEN 3 THEN 12 ELSE 1 END;
UPDATE plans SET post_limit=1 WHERE id=4;
CREATE TABLE IF NOT EXISTS post_images (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 post_id INTEGER NOT NULL,
 url VARCHAR(500) NOT NULL,
 position INTEGER NOT NULL,
 FOREIGN KEY(post_id) REFERENCES posts(id) ON DELETE CASCADE
);
CREATE INDEX idx_post_images_post ON post_images(post_id,position);
INSERT INTO post_images(post_id,url,position) SELECT id,image_url,0 FROM posts WHERE image_url<>'';
