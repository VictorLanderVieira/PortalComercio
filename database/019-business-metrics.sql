CREATE TABLE business_metric_days (
  business_id INTEGER NOT NULL,
  metric_date DATE NOT NULL,
  profile_views INTEGER NOT NULL DEFAULT 0,
  whatsapp_clicks INTEGER NOT NULL DEFAULT 0,
  map_clicks INTEGER NOT NULL DEFAULT 0,
  offer_clicks INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY (business_id, metric_date)
);
