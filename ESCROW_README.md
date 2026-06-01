# Escrow Payment System Documentation

## Overview

This is a simulated escrow payment system for the LocalLink C2C e-commerce platform. It handles secure transactions between buyers and sellers in a formal escrow flow with dispute resolution capabilities.

## System Architecture

### Database Tables

The system uses three escrow-related tables (pre-created):

1. **escrow_transactions**
   - Core transaction records with status tracking
   - Stores buyer, seller, product, amounts, and release PIN
   - Status flow: `pending` → `funded` → `in_progress` → `completed` (or `disputed` → `resolved`)

2. **escrow_status_log**
   - Audit trail of all status changes
   - Records who changed it and when
   - Supports dispute tracking

3. **escrow_disputes**
   - Raised by buyers for transaction issues
   - Contains reason and admin resolution
   - Statuses: `open` → `under_review` → `resolved`

### Platform Fees

- **Under R500**: Flat fee of R5
- **R500 and above**: 2% of transaction amount

### Release PIN

- 6-digit numeric PIN generated when payment is simulated
- Shown to seller only (confidential)
- Buyer must enter correct PIN to confirm trade complete

## File Structure

```
public/escrow/
├── initiate.php        # Start escrow transaction
├── mock_payment.php    # Simulated payment gateway
├── status.php          # View transaction status
├── confirm.php         # Buyer confirms with PIN
└── dispute.php         # Raise dispute

admin/
└── escrow_panel.php    # Admin dispute resolution
```

## API Reference

### 1. escrow/initiate.php

**Purpose**: Initiate an escrow transaction for a product

**Method**: POST

**Required Parameters**:

- `product_id` (int) - Product to purchase

**What it does**:

- Validates buyer is logged in and not the seller
- Fetches product details from products table
- Creates pending escrow_transactions record
- Calculates platform_fee and seller_receives
- Sets 5-day expiry (expires_at)
- Logs status change to escrow_status_log
- Redirects to mock_payment.php

**Response**: Redirect to `escrow/mock_payment.php?escrow_id=X`

**Example HTML Button**:

```html
<form method="POST" action="<?= BASE_URL ?>escrow/initiate.php">
  <input type="hidden" name="product_id" value="<?= $product['id'] ?>" />
  <button type="submit" class="btn btn-primary">Buy with Escrow</button>
</form>
```

---

### 2. escrow/mock_payment.php

**Purpose**: Simulated payment gateway replacing PayFast

**Method**: GET (display) or POST (process)

**Parameters**:

- GET: `escrow_id` - Transaction to pay for
- POST: `action` (`pay_success` or `cancel`)

**Display**:

- Order summary with product title, amount, fee breakdown
- Seller receives amount

**On "Simulate Payment Success"**:

- Updates status to `funded`
- Generates random payment_reference (MOCK-XXXXXXXX)
- Generates 6-digit numeric release_pin
- Sets funded_at timestamp
- Logs status change
- Redirects to status.php

**On "Cancel"**:

- Updates status to `cancelled`
- Logs the cancellation
- Redirects back to product page

---

### 3. escrow/status.php

**Purpose**: Display transaction status with buyer/seller options

**Method**: GET

**Parameters**:

- `escrow_id` - Transaction to view

**Access Control**:

- Only buyer or seller of transaction can view

**What it displays**:

- Current status with color-coded badge
- Transaction details (buyer, seller, amounts, dates)
- Release PIN (seller only)
- Full status history from escrow_status_log

**Buyer Actions** (if funded/in_progress):

- "Confirm Trade Complete" form with PIN input
- "Raise Dispute" form with reason

**Status Badges**:

- pending = gray
- funded/in_progress = blue
- completed = green
- disputed = red
- resolved/cancelled/refunded = orange

---

### 4. escrow/confirm.php

**Purpose**: Buyer confirms trade is complete and releases funds

**Method**: POST

**Required Parameters**:

- `escrow_id` (int)
- `release_pin` (string, 6 digits)

**Validation**:

- User must be the buyer
- PIN must match exactly
- Transaction must be funded or in_progress
- Uses database transaction for ACID compliance

**What it does**:

1. Verifies PIN is correct
2. Updates escrow_transactions:
   - Sets status to `completed`
   - Sets buyer_confirmed = 1
   - Sets completed_at = NOW()
3. Updates products table:
   - Sets product status to `sold`
4. Logs the status change
5. Displays success message with amount transferred

**Response**: Success page with redirect to marketplace

---

### 5. escrow/dispute.php

**Purpose**: Buyer raises a dispute

**Method**: POST

**Required Parameters**:

- `escrow_id` (int)
- `reason` (string) - Reason for dispute

**Validation**:

- User must be the buyer
- Transaction must be funded or in_progress
- Reason must not be empty

**What it does**:

1. Creates record in escrow_disputes table
2. Updates escrow_transactions status to `disputed`
3. Logs the status change
4. Displays confirmation message

**Dispute Record**:

```
- transaction_id
- raised_by (user_id)
- reason
- status (open)
- created_at
```

---

### 6. admin/escrow_panel.php

**Purpose**: Admin panel for managing escrow disputes

**Method**: GET (display) or POST (resolve dispute)

**Access Control**:

- Admin only (role = 'admin')

**Display Features**:

- Filterable list of all escrow transactions
- Status filter buttons for quick navigation
- Shows product title, buyer/seller names, amount, status
- Badge indicators for disputed transactions

**Filter Options**:

- All
- pending
- funded
- in_progress
- completed
- disputed (shows dispute badge)
- resolved
- cancelled
- refunded

**Dispute Resolution**:

- Modal dialog with dispute reason
- Two action buttons:
  - **Release to Seller**: Sets resolution to `released_to_seller`, status to `resolved`
  - **Refund to Buyer**: Sets resolution to `refunded_to_buyer`, status to `refunded`
- Optional admin note for record
- Logs the resolution with reason

**Admin Workflow for Disputed Transaction**:

1. Filter by "disputed" status
2. Click "Resolve" button
3. Review dispute reason in modal
4. Add optional note
5. Choose action (release or refund)
6. Submit → Status updated, escrow_disputes marked resolved

---

## Transaction Status Flow

### Happy Path (Successful Trade)

```
pending → funded → in_progress → completed
```

### Dispute Path

```
pending → funded → in_progress → disputed → resolved
                                                     ↓
                                          released_to_seller (completed)
                                          OR refunded_to_buyer
```

### Cancellation Path

```
pending → cancelled
```

---

## Key Design Features

### Security

- PDO prepared statements prevent SQL injection
- Session authentication required for all endpoints
- Access control: buyers/sellers can only see their transactions
- Admin-only access to dispute panel
- PIN validation with exact match

### Data Integrity

- Database transactions (BEGIN/COMMIT/ROLLBACK) for atomic operations
- Product status updated to "sold" only on successful completion
- Audit trail logs all status changes
- Timestamps for all events

### User Experience

- Clear status badges with color coding
- Complete transaction history visible
- PIN shown to seller only (confidentiality)
- Modal-based dispute resolution for admins
- Responsive design with Bootstrap 5

### Business Logic

- Automatic fee calculation based on amount
- 5-day expiry for pending transactions
- Release PIN prevents accidental fund release
- Full history tracking for compliance

---

## Integration Checklist

- [x] All 6 PHP files created with PDO database access
- [x] Sessions used for authentication ($\_SESSION['user_id'], $\_SESSION['role'])
- [x] Existing db.php config file used for database connection
- [x] Prepared statements for all queries (SQL injection prevention)
- [x] Status badge colors implemented
- [x] Platform fee calculation (R5 flat or 2%)
- [x] 6-digit numeric PIN generation
- [x] 5-day expiry calculation
- [x] Admin dispute resolution with two outcomes
- [x] Complete audit trail in escrow_status_log
- [x] Access control for buyers, sellers, and admins
- [x] Bootstrap 5 styling for consistency
- [x] Error handling and validation

---

## Testing Scenarios

### Scenario 1: Successful Trade

1. User clicks "Buy with Escrow" on product
2. Redirected to mock_payment.php
3. Click "Simulate Payment Success"
4. Transaction moves to "funded" status
5. Buyer enters PIN from status page
6. Transaction marked "completed", product marked "sold"

### Scenario 2: Trade Cancellation

1. User clicks "Buy with Escrow" on product
2. Redirected to mock_payment.php
3. Click "Cancel"
4. Transaction moved to "cancelled", redirected to product page

### Scenario 3: Dispute Resolution

1. Transaction in "funded" status
2. Buyer clicks "Raise Dispute" and provides reason
3. Status changes to "disputed"
4. Admin goes to admin/escrow_panel.php
5. Filters by "disputed"
6. Clicks "Resolve"
7. Chooses "Release to Seller" or "Refund to Buyer"
8. Transaction status updated, resolution recorded

---

## Database Queries Reference

### Create Transaction

```sql
INSERT INTO escrow_transactions
(product_id, buyer_id, seller_id, amount, platform_fee, seller_receives,
 status, expires_at, created_at)
VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
```

### Update to Funded

```sql
UPDATE escrow_transactions
SET status = 'funded', funded_at = NOW(),
    payment_reference = ?, release_pin = ?
WHERE id = ?
```

### Log Status Change

```sql
INSERT INTO escrow_status_log
(transaction_id, old_status, new_status, changed_by, note, created_at)
VALUES (?, ?, ?, ?, ?, NOW())
```

### Create Dispute

```sql
INSERT INTO escrow_disputes
(transaction_id, raised_by, reason, status, created_at)
VALUES (?, ?, ?, 'open', NOW())
```

### Resolve Dispute

```sql
UPDATE escrow_disputes
SET status = 'resolved', resolution = ?, resolved_at = NOW()
WHERE id = ?
```

---

## Notes

- All monetary amounts are stored as DECIMAL(10,2) for precision
- Timestamps use NOW() for server time consistency
- Email notifications could be added to status changes
- SMS alerts could notify seller of fund release
- Refund mechanism would integrate with actual payment gateway in production
- All code follows the existing project conventions and styling
