ALTER TABLE reviews ADD COLUMN hidden_at DATETIME NULL;
ALTER TABLE reviews ADD COLUMN hidden_by_user_id INTEGER NULL;
CREATE INDEX idx_reviews_business_visible ON reviews(business_id,hidden_at);
