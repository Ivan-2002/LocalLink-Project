# Escrow System Integration Guide

## Quick Start: Adding "Buy with Escrow" Button

To add the escrow buying option to your product page, insert this button in the product details section:

### HTML Button Placement

In `public/product.php` or wherever you display the product purchase button:

```html
<?php if ($_SESSION['user_id'] != $product['seller_id']): ?>
<form method="POST" action="<?= BASE_URL ?>escrow/initiate.php" class="mt-3">
  <input type="hidden" name="product_id" value="<?= $product['id'] ?>" />
  <button type="submit" class="btn btn-primary btn-lg w-100">
    <i class="bi bi-shield-check"></i> Buy with Escrow Protection
  </button>
</form>
<?php endif; ?>
```

### CSS Styling (Optional)

Add this to enhance the button appearance:

```css
.btn-escrow {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
}

.btn-escrow:hover {
  background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
  color: white;
}
```

---

## Transaction Flow Diagram

```
Buyer                       System                         Seller
  │                           │                              │
  │─ Click "Buy with Escrow"─→ │                              │
  │                           │─ initiate.php ─→ Create pending transaction
  │                           │                              │
  │ ← Redirect to Payment ────│                              │
  │                           │                              │
  │─ "Simulate Success" ─────→ │                              │
  │                           │─ mock_payment.php ─→ Status: funded
  │                           │─ Generate PIN                │
  │ ← Show Transaction ───────│─ Redirect to status          │
  │   Status & PIN Input      │                              │
  │                           │                              │
  │─ View & Enter PIN ───────→ │                              │
  │                           │─ confirm.php ─→ Validate PIN
  │                           │─ Status: completed ─────────→ Notification
  │                           │─ Mark product: sold          │
  │                           │─ Release funds              │
  │ ← Success Page ───────────│                              │
```

---

## Admin Workflow

### Accessing the Escrow Panel

1. Log in as admin
2. Navigate to `admin/escrow_panel.php`
3. View all escrow transactions

### Resolving a Dispute

1. Filter by "disputed" status
2. Click "Resolve" on the transaction
3. Review the dispute reason in the modal
4. Add an optional admin note
5. Choose action:
   - **Release to Seller**: Buyer receives nothing, seller gets full amount
   - **Refund to Buyer**: Buyer gets refund, seller receives nothing
6. Submit → Transaction marked resolved

---

## Database Setup Verification

Before using the escrow system, verify these tables exist:

```sql
-- Check tables are created
SHOW TABLES LIKE 'escrow_%';

-- Should return:
-- escrow_disputes
-- escrow_status_log
-- escrow_transactions

-- Verify table structure
DESCRIBE escrow_transactions;
DESCRIBE escrow_status_log;
DESCRIBE escrow_disputes;
```

If tables don't exist, run the creation script provided in your database setup file.

---

## Testing the System

### Test Case 1: Successful Trade

**Steps:**

1. Log in as Buyer (not seller of product)
2. Navigate to a product owned by another user
3. Click "Buy with Escrow"
4. On mock payment page, click "Simulate Payment Success"
5. Note the release PIN shown
6. Go to Status page
7. Enter the PIN and click "Confirm & Release Funds"

**Expected Result:**

- Transaction status: completed
- Product status: sold
- Success page displays

### Test Case 2: Cancel Purchase

**Steps:**

1. Log in as Buyer
2. Navigate to a product
3. Click "Buy with Escrow"
4. On mock payment page, click "Cancel"

**Expected Result:**

- Transaction status: cancelled
- Redirected back to product page

### Test Case 3: Raise Dispute

**Steps:**

1. Complete a transaction up to "funded" status
2. Go back to Status page
3. Click "Raise Dispute"
4. Enter a reason (e.g., "Item doesn't match description")
5. Submit

**Expected Result:**

- Transaction status: disputed
- Dispute record created
- Confirmation page shows

### Test Case 4: Admin Resolves Dispute

**Steps:**

1. Log in as admin
2. Go to `admin/escrow_panel.php`
3. Filter by "disputed"
4. Click "Resolve"
5. In modal, choose "Release to Seller" or "Refund to Buyer"
6. Submit

**Expected Result:**

- Dispute status: resolved
- Transaction status: resolved or refunded
- Status log updated

---

## Session Requirements

The system requires these session variables to be set:

```php
$_SESSION['user_id']    // User's database ID
$_SESSION['role']       // Either 'user' or 'admin'
```

These are typically set in `includes/auth.php` during login. Ensure your auth system provides these.

---

## Error Handling

### Common Errors and Solutions

| Error                                    | Cause                                | Solution                   |
| ---------------------------------------- | ------------------------------------ | -------------------------- |
| "Only regular users can initiate escrow" | User has admin role                  | Log in as regular user     |
| "You cannot buy your own product"        | Seller trying to buy own product     | Use different user account |
| "Product is no longer available"         | Product status not 'available'       | Choose available product   |
| "Invalid PIN"                            | Wrong PIN entered                    | Ask seller for correct PIN |
| "Unauthorized access"                    | User not buyer/seller of transaction | Use correct user account   |

---

## Security Notes

1. **PINs**: 6-digit numeric, regenerated for each transaction, stored in database
2. **Database Transactions**: All multi-step operations use BEGIN/COMMIT/ROLLBACK for atomicity
3. **Access Control**: Functions verify user ownership before allowing actions
4. **Prepared Statements**: All SQL queries use PDO prepared statements
5. **Session Authentication**: All pages require valid session

---

## Performance Considerations

- Status history queries use ORDER BY created_at DESC - ensure index exists:

  ```sql
  CREATE INDEX idx_escrow_log_tx ON escrow_status_log(transaction_id, created_at DESC);
  ```

- Transaction listings in admin panel use GROUP BY - verify database has GROUP BY optimization enabled

- For high-volume systems, consider:
  - Archiving old disputes (6+ months)
  - Caching status badge colors
  - Pagination on admin panel

---

## Future Enhancements

1. **Email Notifications**
   - Send PIN to seller
   - Notify of dispute raised
   - Confirm when funds released

2. **SMS Alerts**
   - SMS PIN to buyer's phone
   - Alert seller of confirmation

3. **Real Payment Gateway**
   - Replace mock_payment.php with actual PayFast integration
   - Handle payment_reference from actual transactions
   - Webhook handling for payment notifications

4. **Refund Processing**
   - Real payment reversal logic
   - Track refunds in financial reports

5. **Dispute Evidence**
   - File upload field (receipt photos, messages)
   - Admin can view evidence before resolving

6. **Transaction Reports**
   - Export disputes to CSV
   - Monthly escrow summary
   - Revenue tracking by status

---

## Support

For issues or questions about the escrow system:

1. Check transaction history in `escrow_status_log` table
2. Review error logs in server error_log
3. Verify database tables exist and have correct structure
4. Check session variables are properly set
5. Ensure BASE_URL constant is correct

---

**System Created**: May 2024
**Version**: 1.0
**Compatibility**: PHP 7.4+, MySQL 5.7+, PDO
