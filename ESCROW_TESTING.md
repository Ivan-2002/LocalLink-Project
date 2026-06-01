# Testing & Verification SQL Queries

## Create Test Data

### Create Test Buyer and Seller Users

```sql
-- Buyer account
INSERT INTO users (name, email, password, role, status, location, created_at)
VALUES ('Test Buyer', 'buyer@test.local', '$2y$10$...', 'user', 'active', 'Test Location', NOW());

-- Seller account
INSERT INTO users (name, email, password, role, status, location, created_at)
VALUES ('Test Seller', 'seller@test.local', '$2y$10$...', 'user', 'active', 'Test Location', NOW());

-- Admin account
INSERT INTO users (name, email, password, role, status, location, created_at)
VALUES ('Test Admin', 'admin@test.local', '$2y$10$...', 'admin', 'active', 'Test Location', NOW());
```

### Create Test Product

```sql
-- Add product for seller (seller_id = 2)
INSERT INTO products (seller_id, category_id, title, description, price, location, status, created_at)
VALUES (2, 1, 'Test Item', 'A product for testing escrow', 450.00, 'Test Location', 'available', NOW());
```

## Verify Escrow Tables

### Check Table Structure

```sql
DESCRIBE escrow_transactions;
DESCRIBE escrow_status_log;
DESCRIBE escrow_disputes;
```

### Verify Table Exists

```sql
SHOW TABLES LIKE 'escrow_%';
```

Expected result:

```
escrow_disputes
escrow_status_log
escrow_transactions
```

## View Transaction Data

### List All Transactions

```sql
SELECT
    et.id,
    et.product_id,
    et.buyer_id,
    et.seller_id,
    et.amount,
    et.platform_fee,
    et.seller_receives,
    et.status,
    et.created_at
FROM escrow_transactions et
ORDER BY et.created_at DESC;
```

### View Transaction with Product Details

```sql
SELECT
    et.id as transaction_id,
    p.title as product,
    b.name as buyer,
    s.name as seller,
    et.amount,
    et.platform_fee,
    et.status,
    et.created_at
FROM escrow_transactions et
JOIN products p ON et.product_id = p.id
JOIN users b ON et.buyer_id = b.id
JOIN users s ON et.seller_id = s.id
ORDER BY et.created_at DESC;
```

### View Transaction Status History

```sql
SELECT
    esl.id,
    esl.transaction_id,
    esl.old_status,
    esl.new_status,
    esl.note,
    esl.created_at
FROM escrow_status_log esl
WHERE esl.transaction_id = 1
ORDER BY esl.created_at DESC;
```

### View Disputes

```sql
SELECT
    ed.id,
    ed.transaction_id,
    u.name as raised_by,
    ed.reason,
    ed.status,
    ed.resolution,
    ed.created_at
FROM escrow_disputes ed
JOIN users u ON ed.raised_by = u.id
ORDER BY ed.created_at DESC;
```

## Test Scenarios

### Scenario 1: Verify Transaction Creation

```sql
-- After buyer initiates purchase
SELECT * FROM escrow_transactions WHERE id = [LAST_ESCROW_ID];

-- Should show:
-- - status = 'pending'
-- - buyer_id = [BUYER_ID]
-- - seller_id = [SELLER_ID]
-- - amount = [PRODUCT_PRICE]
-- - platform_fee = [CALCULATED_FEE]
-- - seller_receives = [AMOUNT - FEE]
-- - expires_at = [5 DAYS FROM NOW]
```

### Scenario 2: Verify Payment Success

```sql
-- After simulating payment success
SELECT * FROM escrow_transactions WHERE id = [ESCROW_ID];

-- Should show:
-- - status = 'funded'
-- - funded_at = [CURRENT_TIMESTAMP]
-- - payment_reference = 'MOCK-XXXXXXXX'
-- - release_pin = [6-DIGIT NUMBER]

-- Check status log
SELECT * FROM escrow_status_log WHERE transaction_id = [ESCROW_ID];

-- Should have entry: pending → funded
```

### Scenario 3: Verify Trade Completion

```sql
-- After buyer confirms with correct PIN
SELECT * FROM escrow_transactions WHERE id = [ESCROW_ID];

-- Should show:
-- - status = 'completed'
-- - buyer_confirmed = 1
-- - completed_at = [CURRENT_TIMESTAMP]

-- Check product status
SELECT status FROM products WHERE id = [PRODUCT_ID];

-- Should be: 'sold'
```

### Scenario 4: Verify Dispute Creation

```sql
-- After buyer raises dispute
SELECT * FROM escrow_disputes WHERE transaction_id = [ESCROW_ID];

-- Should show:
-- - status = 'open'
-- - raised_by = [BUYER_ID]
-- - reason = [DISPUTE_REASON]

-- Check escrow transaction
SELECT * FROM escrow_transactions WHERE id = [ESCROW_ID];

-- Should show:
-- - status = 'disputed'
```

### Scenario 5: Verify Dispute Resolution

```sql
-- After admin resolves dispute
SELECT * FROM escrow_disputes WHERE transaction_id = [ESCROW_ID];

-- Should show:
-- - status = 'resolved'
-- - resolution = 'released_to_seller' OR 'refunded_to_buyer'
-- - resolved_at = [CURRENT_TIMESTAMP]

-- Check escrow transaction
SELECT * FROM escrow_transactions WHERE id = [ESCROW_ID];

-- Should show:
-- - status = 'resolved' OR 'refunded' (depending on resolution)
```

## Fee Calculation Tests

### Test Fee Calculation (Under R500)

```sql
-- Fee should be R5
SELECT 5 as platform_fee, 195 - 5 as seller_receives;
-- Result: platform_fee=5, seller_receives=190
```

### Test Fee Calculation (R500+)

```sql
-- Fee should be 2%
SELECT 1000 * 0.02 as platform_fee, 1000 - (1000 * 0.02) as seller_receives;
-- Result: platform_fee=20.00, seller_receives=980.00
```

### Test Fee Calculation (Exactly R500)

```sql
-- Fee should be 2%
SELECT 500 * 0.02 as platform_fee, 500 - (500 * 0.02) as seller_receives;
-- Result: platform_fee=10.00, seller_receives=490.00
```

## Cleanup Queries (Test Only)

### Delete Test Transactions

```sql
-- CAUTION: Only use in test environment
DELETE FROM escrow_disputes WHERE transaction_id IN (
    SELECT id FROM escrow_transactions
    WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
);

DELETE FROM escrow_status_log WHERE transaction_id IN (
    SELECT id FROM escrow_transactions
    WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
);

DELETE FROM escrow_transactions
WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR);
```

### Reset Product Status After Test

```sql
-- Mark test product as available again
UPDATE products SET status = 'available' WHERE id = [TEST_PRODUCT_ID];
```

## Monitoring Queries

### All Transactions Summary

```sql
SELECT
    status,
    COUNT(*) as count,
    SUM(amount) as total_amount,
    SUM(platform_fee) as total_fees,
    SUM(seller_receives) as total_to_sellers
FROM escrow_transactions
GROUP BY status;
```

### Recent Activity (Last 24 Hours)

```sql
SELECT
    et.id,
    p.title,
    b.name as buyer,
    s.name as seller,
    et.status,
    et.created_at
FROM escrow_transactions et
JOIN products p ON et.product_id = p.id
JOIN users b ON et.buyer_id = b.id
JOIN users s ON et.seller_id = s.id
WHERE et.created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
ORDER BY et.created_at DESC;
```

### Disputed Transactions Pending Resolution

```sql
SELECT
    et.id,
    p.title,
    b.name as buyer,
    s.name as seller,
    ed.reason,
    ed.created_at
FROM escrow_transactions et
JOIN escrow_disputes ed ON et.id = ed.transaction_id
JOIN products p ON et.product_id = p.id
JOIN users b ON et.buyer_id = b.id
JOIN users s ON et.seller_id = s.id
WHERE ed.status = 'open'
ORDER BY ed.created_at ASC;
```

### Seller Revenue Report

```sql
SELECT
    s.id,
    s.name as seller,
    COUNT(et.id) as transactions,
    SUM(et.seller_receives) as total_received,
    AVG(et.amount) as avg_transaction
FROM escrow_transactions et
JOIN users s ON et.seller_id = s.id
WHERE et.status = 'completed'
GROUP BY s.id, s.name
ORDER BY total_received DESC;
```

## Performance Index Queries

### Create Recommended Indexes

```sql
-- Index for status filtering
CREATE INDEX idx_escrow_status ON escrow_transactions(status);

-- Index for buyer/seller lookups
CREATE INDEX idx_escrow_buyer ON escrow_transactions(buyer_id);
CREATE INDEX idx_escrow_seller ON escrow_transactions(seller_id);

-- Index for date range queries
CREATE INDEX idx_escrow_created ON escrow_transactions(created_at);

-- Composite index for common queries
CREATE INDEX idx_escrow_status_log ON escrow_status_log(transaction_id, created_at DESC);

-- Index for dispute lookups
CREATE INDEX idx_escrow_disputes ON escrow_disputes(transaction_id, status);
```

### Verify Indexes

```sql
SHOW INDEXES FROM escrow_transactions;
SHOW INDEXES FROM escrow_status_log;
SHOW INDEXES FROM escrow_disputes;
```
