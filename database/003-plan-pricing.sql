ALTER TABLE plans ADD COLUMN promo_price_cents INTEGER NULL;
ALTER TABLE plans ADD COLUMN promo_start DATETIME NULL;
ALTER TABLE plans ADD COLUMN promo_end DATETIME NULL;
ALTER TABLE subscriptions ADD COLUMN amount_cents INTEGER NULL;
UPDATE subscriptions SET amount_cents=COALESCE((SELECT p.amount_cents FROM payments p WHERE p.subscription_id=subscriptions.id ORDER BY p.id DESC LIMIT 1),(SELECT price_cents FROM plans WHERE plans.id=subscriptions.plan_id));
