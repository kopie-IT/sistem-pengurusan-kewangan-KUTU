# PRODUCT REQUIREMENTS DOCUMENT (PRD)
# Sistem Pengurusan Main Kutu

**Version:** 1.0
**Status:** Draft for Development
**Target Stack:** Vanilla PHP + MySQL/MariaDB
**Deployment:** cPanel / Apache
**Frontend:** HTML5, CSS3, Vanilla JavaScript
**Architecture:** Modular PHP MVC-inspired architecture without framework

---

## 1. PRODUCT OVERVIEW

Sistem Pengurusan Main Kutu ialah aplikasi web untuk mengurus Plan Main Kutu secara digital.

Sistem mengurus:

- Member
- Plan
- Plan membership
- Contribution schedule
- Contribution payment
- Bulk payment
- Payment slip
- Payment verification
- Payout schedule
- Payout calendar
- Payout transaction
- Admin Fee
- Payout Mode
- Shortfall
- Member Reliability / Credit Score
- Score recovery
- Withdrawal
- Notification
- Financial ledger
- Reports
- Audit trail

Sistem mesti sesuai dipasang pada hosting biasa yang menggunakan cPanel, Apache, PHP dan MySQL/MariaDB.

---

# 2. DEVELOPMENT PRINCIPLES

1. Gunakan Vanilla PHP tanpa Laravel, CodeIgniter atau framework backend.
2. Gunakan MySQL/MariaDB.
3. Gunakan HTML5, CSS3 dan Vanilla JavaScript.
4. Jangan bergantung kepada Node.js untuk production.
5. Jangan memerlukan SSH/root access.
6. Semua configuration production mesti boleh dilakukan melalui `.env` atau configuration file yang sesuai untuk cPanel.
7. Gunakan PDO dan prepared statements.
8. Password mesti menggunakan `password_hash()` dan `password_verify()`.
9. Semua database query mesti parameterized.
10. Semua input pengguna mesti divalidate dan disanitize mengikut konteks.
11. Gunakan CSRF protection untuk semua state-changing request.
12. Gunakan session security.
13. Semua critical action mesti mempunyai audit trail.
14. Financial transaction tidak boleh dipadam secara destructive.
15. Gunakan adjustment/reversal transaction untuk membetulkan transaksi.
16. Gunakan database transaction (`BEGIN`, `COMMIT`, `ROLLBACK`) untuk operasi kewangan yang melibatkan banyak record.
17. Sistem mesti mobile responsive.
18. Code mesti modular dan mudah diselenggara oleh developer junior/intermediate.

---

# 3. TARGET ENVIRONMENT

Minimum production environment:

- Apache
- PHP 8.2+ disyorkan
- MySQL 8+ atau MariaDB 10.5+
- HTTPS
- cPanel
- PHP extensions:
  - PDO
  - PDO_MySQL
  - mbstring
  - openssl
  - fileinfo
  - json
  - session

Optional:

- GD
- ZIP
- Cron

Sistem tidak boleh bergantung kepada Composer untuk fungsi asas MVP kecuali dependency tersebut benar-benar diperlukan.

---

# 4. USER ROLES

## 4.1 Super Admin

Full access:

- User management
- Admin management
- Plan management
- Member management
- Payment
- Payout
- Fee configuration
- Credit Score rules
- Reports
- Audit trail
- System settings

## 4.2 Admin

Operational access:

- Manage members
- Manage plans
- Verify payments
- Manage payout
- Upload payout slip
- View financial reports
- Manage overdue cases
- Manage withdrawal requests

Permission mesti boleh dikembangkan kepada granular permission pada masa hadapan.

## 4.3 Member

Member boleh:

- Register/login
- Manage profile
- View available plans
- Join plan
- View joined plans
- View contribution calendar
- View payout calendar
- View payment history
- Make bulk payment
- Upload payment slip
- View payment verification status
- View payout history
- View payout slip
- View Credit Score
- View score history
- Submit withdrawal request

Member tidak boleh mengubah financial transaction yang telah dihantar.

---

# 5. CORE ENTITIES

Cadangan entity/database tables:

- users
- roles
- permissions
- role_permissions
- members
- plans
- plan_members
- plan_cycles
- contribution_schedules
- payment_batches
- payment_batch_items
- payment_slips
- payments
- payout_schedules
- payouts
- admin_fee_configs
- admin_fee_versions
- credit_scores
- credit_score_history
- credit_score_rules
- withdrawal_requests
- shortfalls
- ledger_transactions
- notifications
- audit_logs
- adjustments
- system_settings

---

# 6. PLAN MANAGEMENT

Admin boleh create Plan.

Plan configuration:

## Basic

- Plan Name
- Plan Code
- Description
- Number of Members
- Contribution Amount
- Payment Frequency
- Number of Cycles
- Start Date
- End Date
- Plan Status

## Membership

- Maximum Members
- Minimum Credit Score
- Approval Required
- Allow Multiple Plans
- Withdrawal Allowed

## Payout

- Payout Mode
- Fixed Payout Amount
- Payout Frequency
- Payout Day
- Recipient Order

## Admin Fee

- Enable Admin Fee
- Fee Type
- Fee Value
- Effective Date

## Credit Score

- Minimum Score
- Score rules reference
- Recovery rules reference

---

# 7. PLAN STATUS

Supported statuses:

- Draft
- Open
- Full
- Active
- Suspended
- Completed
- Cancelled

Rules:

- Draft: belum dibuka kepada member.
- Open: member boleh memohon.
- Full: capacity penuh.
- Active: plan sedang berjalan.
- Suspended: operasi sementara dihentikan.
- Completed: semua cycle selesai.
- Cancelled: plan dibatalkan mengikut policy.

---

# 8. MULTIPLE PLAN MEMBERSHIP

Seorang member boleh menyertai lebih daripada satu Plan.

Contoh:

Member A:

- Plan A = RM100
- Plan B = RM200
- Plan C = RM300

System mesti mempunyai dashboard yang menunjukkan semua active plans.

---

# 9. CONTRIBUTION SCHEDULE

System menjana contribution schedule berdasarkan Plan.

Setiap schedule mempunyai:

- Plan
- Cycle
- Member
- Due date
- Amount
- Status

Status:

- Pending
- Paid
- Partial
- Overdue
- Failed
- Cancelled
- Refunded

---

# 10. CONTRIBUTION PAYMENT

Contribution ialah bayaran member kepada Plan.

Admin Fee TIDAK dikenakan semasa contribution.

Contoh:

Contribution = RM100

Member membayar:

RM100

Bukan RM105.

Admin Fee hanya dipotong semasa member menerima payout.

---

# 11. BULK PAYMENT

Member boleh memilih beberapa outstanding contribution daripada beberapa Plan dan membayar semuanya menggunakan satu transaksi.

Example:

| Plan | Amount |
|---|---:|
| Plan A | RM100 |
| Plan B | RM200 |
| Plan C | RM300 |
| Total | RM600 |

Member membuat satu payment RM600.

System create:

`payment_batches`

Kemudian:

`payment_batch_items`

untuk setiap Plan/payment allocation.

---

# 12. PAYMENT SLIP

Semua contribution payment oleh member wajib mempunyai bukti pembayaran.

Allowed file types:

- JPG
- JPEG
- PNG
- PDF

Maximum file size mesti configurable.

Payment slip disimpan secara secure dan tidak boleh diakses secara direct public URL jika mengandungi data sensitif.

Gunakan authenticated download/view endpoint.

---

# 13. SAME SLIP FOR MULTIPLE PAYMENTS

Satu payment slip boleh digunakan untuk beberapa payment allocation.

Contoh:

Bulk payment:

RM600

Slip:

`transfer_600.pdf`

Allocation:

- Plan A = RM100
- Plan B = RM200
- Plan C = RM300

Slip dikaitkan kepada parent `payment_batch`.

Jangan duplicate physical file untuk setiap payment.

---

# 14. PAYMENT VERIFICATION

Payment workflow:

```text
Draft
  ↓
Submitted
  ↓
Pending Verification
  ↓
Approved
```

Alternative:

```text
Pending Verification
  ↓
Rejected
  ↓
Resubmission
  ↓
Pending Verification
```

Admin boleh:

- View slip
- Verify amount
- Verify reference
- Approve
- Reject
- Request resubmission
- Add verification note

Apabila approved:

- Payment status updated
- Contribution schedule updated
- Ledger updated
- Credit Score event generated if applicable
- Notification sent

---

# 15. PARTIAL PAYMENT

System mesti support partial payment.

Example:

Outstanding:

- Plan A = RM100
- Plan B = RM200
- Plan C = RM300

Member bayar RM400.

Allocation:

- Plan A = RM100 Paid
- Plan B = RM200 Paid
- Plan C = RM100 Partial

Plan C remaining:

RM200

Admin boleh review/edit allocation jika diperlukan, dengan audit trail.

---

# 16. CONTRIBUTION CALENDAR

Member boleh melihat contribution calendar untuk Plan yang disertai.

Calendar read-only.

Member boleh melihat:

- Contribution date
- Cycle
- Amount
- Payment status

Member tidak boleh mengubah calendar.

---

# 17. PAYOUT CALENDAR

Member boleh melihat payout calendar bagi Plan yang disertai.

Calendar read-only.

Member boleh melihat:

- Cycle
- Payout date
- Recipient
- Gross payout
- Admin Fee
- Net payout
- Payout status
- Payment slip selepas payout disahkan

Contoh:

| Date | Cycle | Recipient | Gross | Status |
|---|---:|---|---:|---|
| 1 Sep | 1 | Ali | RM1,000 | Paid |
| 1 Oct | 2 | Abu | RM1,000 | Paid |
| 1 Nov | 3 | Siti | RM1,000 | Upcoming |

---

# 18. PAYOUT SCHEDULE

Admin boleh menetapkan urutan penerima.

Setiap payout schedule mempunyai:

- Plan
- Cycle
- Recipient
- Payout date
- Expected amount
- Status

Recipient assignment mesti disimpan dan diaudit.

---

# 19. PAYOUT MODE

System mempunyai tepat dua payout mode:

1. Fixed Payout
2. Actual Collection

Default:

**Fixed Payout**

---

# 20. FIXED PAYOUT

Fixed Payout berdasarkan jumlah payout yang ditetapkan Plan.

Example:

Expected/Fixed Payout = RM1,000
Admin Fee = RM5

Member receives:

RM995

Walaupun actual collection hanya RM900.

Formula:

`Gross Payout = Fixed Payout`

`Net Payout = Gross Payout - Admin Fee`

Shortfall:

`Shortfall = Gross Payout - Actual Collection`

Jika Actual Collection = RM900:

Shortfall = RM100.

---

# 21. ACTUAL COLLECTION

Dalam Actual Collection:

`Gross Payout = Actual Collection`

`Net Payout = Gross Payout - Admin Fee`

Example:

Actual Collection = RM900
Admin Fee = RM5

Net Payout = RM895.

---

# 22. SHORTFALL

Shortfall direkodkan apabila:

`Actual Collection < Required Gross Payout`

Shortfall record:

- Plan
- Cycle
- Payout
- Expected amount
- Actual collection
- Shortfall amount
- Status
- Resolution
- Notes
- Created date
- Resolved date
- Approved by

Shortfall tidak boleh dihapuskan secara destructive.

---

# 23. SHORTFALL RESOLUTION

MVP mesti menyokong status shortfall:

- Open
- Under Review
- Resolved
- Written Off

Kaedah penyelesaian boleh dikembangkan kemudian.

Contoh future methods:

- Admin Advance
- Reserve Fund
- Recovery from outstanding member
- Carry Forward
- Manual Adjustment

Untuk MVP, sistem hanya perlu merekod shortfall dan resolution record tanpa hard-code satu kaedah settlement jika business rule belum dimuktamadkan.

---

# 24. ADMIN FEE

Admin Fee dikenakan hanya ketika payout.

Contribution tidak dikenakan Admin Fee.

Supported fee types:

## Fixed

Example:

Gross Payout = RM1,000
Fee = RM5
Net = RM995

## Percentage

Example:

Gross Payout = RM5,000
Fee = 2%
Fee = RM100
Net = RM4,900

---

# 25. ADMIN FEE CONFIGURATION

Admin menentukan fee semasa setup Plan.

Fields:

- Fee enabled
- Fee type
- Fee value
- Effective date
- Status

Fee mesti disimpan dengan configuration version.

---

# 26. ADMIN FEE VERSIONING

Historical payout mesti menggunakan fee configuration yang berkuat kuasa pada masa payout.

Example:

Version 1:
RM5
Effective 1 Jan

Version 2:
RM10
Effective 1 Jun

Payout sebelum 1 Jun:
RM5

Payout selepas 1 Jun:
RM10

---

# 27. PAYOUT TRANSACTION

Payout record mesti mempunyai:

- Payout ID
- Plan
- Cycle
- Recipient
- Gross payout
- Actual collection
- Admin Fee
- Net payout
- Payout mode
- Shortfall
- Status
- Payment reference
- Payment slip
- Paid date

---

# 28. PAYOUT SLIP

Admin mesti upload payment slip/bukti payout apabila payout telah dibuat.

Slip mesti dikaitkan terus kepada payout transaction.

Workflow:

```text
Scheduled
 ↓
Due
 ↓
Processing
 ↓
Admin makes payment
 ↓
Upload payout slip
 ↓
Confirm payout
 ↓
Paid
```

Payout slip boleh dilihat oleh member yang mempunyai access kepada Plan tersebut.

---

# 29. PAYOUT STATUS

- Scheduled
- Due
- Processing
- Paid
- Failed
- Delayed
- Cancelled
- Reversed

---

# 30. MEMBER RELIABILITY / CREDIT SCORE

Score mengukur reliability member dalam sistem.

Initial score:

100

Range:

0–100

Maximum:

100

Minimum:

0

Score tidak sama dengan CTOS/bank credit score.

Ia ialah internal Member Reliability Score.

---

# 31. SCORE COMPONENTS

Cadangan initial weighting:

| Component | Weight |
|---|---:|
| Payment Performance | 30% |
| Timeliness | 25% |
| Plan Completion | 20% |
| Consistency | 15% |
| Behaviour/Risk | 10% |

Total:

100%

Weight mesti configurable.

---

# 32. POSITIVE SCORE EVENTS

Contoh:

- On-time payment
- Early payment
- Consecutive on-time payments
- Complete cycle
- Complete plan
- Good payment consistency

---

# 33. NEGATIVE SCORE EVENTS

Contoh:

- Late payment
- Missed payment
- Failed payment
- Withdrawal
- Repeated overdue
- Other configured violation

Cadangan initial rules:

- Late Payment: -5
- Late > 7 days: -10
- Late > 14 days: -15
- Missed Payment: -20
- Withdrawal: -30

Nilai mesti configurable.

---

# 34. SCORE RECOVERY

Score boleh naik semula.

Recovery berlaku secara berperingkat apabila member menunjukkan consistent positive behaviour.

Example:

Score = 65

On-time payment:
+2

Next on-time:
+2

Next on-time:
+2

Plan completion:
configured bonus

Recovery cap mesti configurable.

Score tidak boleh melebihi 100.

---

# 35. SCORE HISTORY

Setiap score change mesti disimpan.

Fields:

- Member
- Event
- Reason Code
- Previous Score
- Score Change
- New Score
- Related Plan
- Related Payment
- Related Payout
- Actor
- Timestamp

---

# 36. SCORE REASON CODES

Initial codes:

- INITIAL_SCORE
- ON_TIME_PAYMENT
- EARLY_PAYMENT
- CONSISTENT_PAYMENT
- PLAN_COMPLETED
- LATE_PAYMENT
- MISSED_PAYMENT
- PAYMENT_FAILED
- PLAN_WITHDRAWAL
- ACCOUNT_VIOLATION
- MANUAL_ADJUSTMENT

---

# 37. SCORE LEVEL

Initial display:

- 90–100: Excellent
- 80–89: Good
- 70–79: Fair
- 60–69: Risk
- 0–59: High Risk

Admin boleh configure threshold pada future release.

---

# 38. PLAN ELIGIBILITY

Plan boleh menetapkan minimum score.

Example:

Plan A:
Minimum score 60

Plan B:
Minimum score 80

High value Plan:
Minimum score 90

System melakukan eligibility check semasa join request.

---

# 39. SCORE CALCULATION

Final score perlu mengambil kira:

- Payment performance
- Payment timeliness
- Plan completion
- Consistency
- Negative behaviour
- Plan history

System perlu menyimpan raw event history supaya formula boleh dikemaskini tanpa kehilangan historical data.

---

# 40. WITHDRAWAL

Member boleh request withdrawal jika Plan membenarkannya.

Withdrawal request fields:

- Member
- Plan
- Reason
- Request date
- Current cycle
- Outstanding
- Score impact
- Status
- Approved by
- Decision date
- Notes

Statuses:

- Pending
- Approved
- Rejected
- Cancelled
- Completed

---

# 41. OVERDUE MANAGEMENT

System mesti:

1. Detect overdue contribution.
2. Mark payment as Overdue.
3. Notify member.
4. Update outstanding.
5. Generate score event.
6. Display risk status to Admin.
7. Include overdue in reports.

---

# 42. NOTIFICATION

Events:

- Plan invitation
- Plan joined
- Payment due
- Payment reminder
- Payment submitted
- Payment approved
- Payment rejected
- Payment overdue
- Payout upcoming
- Payout processing
- Payout paid
- Score changed
- Withdrawal decision
- Plan completed

MVP channel:

- In-app
- Email

WhatsApp integration boleh dibuat pada future phase.

---

# 43. FINANCIAL LEDGER

Semua financial movement mesti direkodkan.

Transaction types:

- CONTRIBUTION
- PAYOUT
- ADMIN_FEE
- SHORTFALL
- REFUND
- ADJUSTMENT
- RECOVERY
- PENALTY

Ledger tidak boleh diubah secara destructive.

---

# 44. ACCOUNTING PRINCIPLE

Contribution dan Admin Fee mesti dipisahkan.

Example:

Gross payout = RM1,000
Admin Fee = RM5
Net payout = RM995

Ledger mesti menyimpan:

- Payout = RM1,000
- Admin Fee = RM5
- Net cash paid to member = RM995

---

# 45. AUDIT TRAIL

Audit log untuk:

- Login
- Logout
- Create plan
- Update plan
- Change payout mode
- Change fee
- Verify payment
- Reject payment
- Approve payout
- Upload payout slip
- Change member status
- Score adjustment
- Financial adjustment
- Withdrawal decision

Audit fields:

- User
- Action
- Entity
- Entity ID
- Old value
- New value
- IP
- User agent
- Timestamp

---

# 46. SECURITY

Must implement:

- HTTPS
- Secure sessions
- CSRF token
- Prepared statements
- Password hashing
- Input validation
- Output escaping
- File upload validation
- MIME validation
- File extension validation
- Maximum file size
- Authorization checks
- Rate limiting for login
- Secure headers where possible

Payment slips must not be directly publicly accessible.

---

# 47. FILE UPLOAD SECURITY

Uploaded files:

- Rename using generated UUID/random filename.
- Never use original filename as storage filename.
- Validate MIME type.
- Validate extension.
- Validate size.
- Store outside public web root where possible.
- Serve through authenticated controller.
- Log uploader and timestamp.

Recommended structure:

```text
/storage/uploads/payment-slips/
```

If cPanel document root prevents storage outside public directory, use a protected directory with deny rules and authenticated PHP download endpoint.

---

# 48. DATABASE TRANSACTION RULES

Operations such as:

- Approve bulk payment
- Allocate payment
- Create payout
- Apply admin fee
- Update ledger
- Apply credit score event

must use database transactions.

Example:

```text
BEGIN
  Update payment
  Update contribution schedule
  Insert ledger transaction
  Insert score event
  Insert audit log
COMMIT
```

If any operation fails:

```text
ROLLBACK
```

No partial financial update is allowed.

---

# 49. MEMBER DASHBOARD

Dashboard sections:

- Active Plans
- Upcoming Payment
- Outstanding Payment
- Upcoming Payout
- Current Credit Score
- Recent Transactions
- Notifications

---

# 50. MY PAYMENTS

Member can see:

- Pending payments
- Overdue payments
- Paid payments
- Partial payments
- Rejected payments
- Bulk payment history

Member can select multiple outstanding payments.

Button:

**Pay Selected**

System calculates total.

Then:

**Upload Payment Slip**

---

# 51. ADMIN PAYMENT VERIFICATION QUEUE

Admin sees:

- Batch ID
- Member
- Total amount
- Number of plans
- Slip
- Submission date
- Status

Admin can open batch and see allocation breakdown.

Example:

```text
Batch BP00001
Member: Ahmad
Total: RM600

Plan A  RM100
Plan B  RM200
Plan C  RM300

Slip: transfer.jpg
```

---

# 52. ADMIN DASHBOARD

Widgets:

- Active Plans
- Total Members
- Pending Verification
- Overdue
- Today's Payout
- Upcoming Payout
- Total Collection
- Total Payout
- Admin Fee
- Shortfall
- Low Score Members

---

# 53. REPORTS

Reports:

## Financial

- Contribution
- Payout
- Admin Fee
- Shortfall
- Outstanding
- Refund
- Adjustment

## Plan

- Plan performance
- Member count
- Collection rate
- Payout
- Shortfall
- Completion

## Member

- Payment history
- Plan history
- Credit Score
- Late payments
- Withdrawal

## Payment

- Pending
- Approved
- Rejected
- Overdue
- Partial
- Bulk payment

---

# 54. EXPORT

Admin reports should support:

- CSV
- Excel-compatible CSV
- PDF as future enhancement

MVP priority:

CSV.

---

# 55. SEARCH AND FILTER

Admin must support:

- Member search
- Plan search
- Payment search
- Payout search
- Transaction search
- Date range
- Status
- Credit Score range

---

# 56. DATABASE DESIGN PRINCIPLES

Use:

- Primary keys
- Foreign keys where appropriate
- Indexes on:
  - member_id
  - plan_id
  - status
  - due_date
  - payout_date
  - transaction_date
  - reference
- DECIMAL for monetary values.

Never use FLOAT for money.

Recommended:

`DECIMAL(15,2)`

---

# 57. MONEY HANDLING

All money calculations must use decimal arithmetic.

Never rely on floating point arithmetic.

Store:

- amount
- fee
- payout
- shortfall

as DECIMAL.

Currency default:

MYR.

Currency should be configurable for future expansion.

---

# 58. RECOMMENDED PROJECT STRUCTURE

```text
/public_html/
    index.php

    .htaccess

    /app/
        /config/
        /controllers/
        /models/
        /services/
        /repositories/
        /middleware/
        /helpers/
        /validators/
        /views/
        /routes/

    /database/
        /migrations/
        /seeders/

    /public/
        /assets/
            /css/
            /js/
            /images/

    /storage/
        /logs/
        /uploads/

    /tests/

    /docs/

    /cron/

    .env.example
    README.md
```

For cPanel deployment, public-facing files should be kept in the document root while application/private files should ideally remain outside `public_html`.

---

# 59. ROUTING

Use a simple front controller:

`public/index.php`

All requests route through the application router.

Example:

```text
/login
/logout
/dashboard
/plans
/plans/{id}
/plans/{id}/join
/payments
/payments/bulk
/payments/{id}
/payouts
/payouts/{id}
/calendar
/profile
/credit-score
/admin
/admin/plans
/admin/payments
/admin/payouts
/admin/members
/admin/reports
```

Use clean URLs with Apache rewrite.

---

# 60. API DESIGN

Even if MVP is server-rendered, business logic should be separated into Services.

Example:

```text
PaymentService
PayoutService
CreditScoreService
PlanService
LedgerService
NotificationService
FileUploadService
AuditService
```

Controllers should not contain large business calculations.

---

# 61. REQUIRED SERVICES

Minimum:

- AuthService
- PlanService
- MembershipService
- PaymentService
- BulkPaymentService
- PaymentVerificationService
- PayoutService
- AdminFeeService
- CreditScoreService
- ShortfallService
- LedgerService
- NotificationService
- AuditService
- FileUploadService

---

# 62. CRON JOBS

cPanel Cron may be used for:

- Payment overdue detection
- Reminder notifications
- Upcoming payout notifications
- Score processing if required
- Daily reconciliation checks

Example cron:

```text
php /home/USERNAME/app/cron/daily.php
```

Exact path must be configurable.

---

# 63. BACKUP

Production must have:

- Daily database backup
- Upload file backup
- Configuration backup
- Retention policy

Never store database credentials in Git.

---

# 64. ENVIRONMENT CONFIGURATION

Use:

```text
APP_ENV=production
APP_DEBUG=false

DB_HOST=localhost
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

APP_URL=https://example.com

SESSION_SECURE=true
```

`.env` must not be committed to Git.

---

# 65. DEVELOPMENT PHASES

## Phase 1

Foundation:

- Authentication
- Roles
- Database
- Layout
- Security

## Phase 2

Member & Plan:

- Member
- Plan
- Membership
- Schedule

## Phase 3

Payment:

- Contribution
- Bulk Payment
- Slip
- Verification
- Partial payment

## Phase 4

Payout:

- Calendar
- Payout schedule
- Fixed Payout
- Actual Collection
- Admin Fee
- Shortfall
- Payout slip

## Phase 5

Credit Score:

- Score
- Rules
- Deduction
- Recovery
- History

## Phase 6

Reports:

- Dashboard
- Ledger
- Reports
- CSV export
- Audit

## Phase 7

Testing:

- Unit
- Integration
- Financial
- Security
- UAT

---

# 66. ACCEPTANCE CRITERIA

MVP is acceptable when:

1. Admin can create a Plan.
2. Admin can configure contribution.
3. Admin can configure payout schedule.
4. Fixed Payout is default.
5. Actual Collection can be selected.
6. Admin Fee is deducted only during payout.
7. Member can join multiple Plans.
8. Member can select multiple outstanding payments.
9. Member can make one bulk payment.
10. Member must upload payment slip.
11. One slip can be linked to multiple payment allocations.
12. Admin can verify bulk payment.
13. Partial payment is supported.
14. Member can view contribution calendar.
15. Member can view payout calendar.
16. Admin can upload payout slip.
17. Member can view payout slip after payout confirmation.
18. Shortfall is recorded.
19. Credit Score is calculated.
20. Score decreases for negative behaviour.
21. Score recovers with consistent positive behaviour.
22. Score history is visible.
23. Financial ledger records transactions.
24. Audit trail records critical actions.
25. Application works on cPanel without framework dependency.

---

# 67. OPEN BUSINESS RULES

Before production, confirm:

1. Shortfall settlement method.
2. Whether Admin can advance shortfall.
3. Late payment grace period.
4. Late payment fee.
5. Withdrawal policy.
6. Refund policy.
7. Credit Score exact formula.
8. Score recovery formula.
9. Admin permission matrix.
10. Payment verification SLA.
11. Payout approval requirement.
12. Maximum Plan amount.
13. KYC requirements.
14. Payment gateway/bank transfer process.
15. Legal/compliance requirements.
16. Data retention period.
17. Notification provider.
18. Backup retention.
19. Dispute handling.

---

# 68. LEGAL / COMPLIANCE

This PRD describes software requirements only.

Before commercial production use, the business model must be reviewed for applicable Malaysian legal, financial, consumer protection, privacy, AML/KYC and other regulatory requirements.

The system should be designed so that compliance rules can be added without redesigning the core financial engine.

---

# 69. IMPLEMENTATION PRIORITY

Priority:

### P0 — Critical

- Authentication
- Members
- Plans
- Contribution schedule
- Payment
- Payment slip
- Bulk payment
- Payment verification
- Payout
- Fixed Payout
- Actual Collection
- Admin Fee
- Payout slip
- Ledger
- Audit trail

### P1 — Important

- Credit Score
- Score recovery
- Withdrawal
- Shortfall management
- Notifications
- Reports
- Calendar

### P2 — Future

- WhatsApp
- Payment gateway automation
- Advanced analytics
- Mobile app
- AI risk detection
- KYC automation

---

# 70. FINAL PRODUCT PRINCIPLES

The system must be:

- Transparent
- Traceable
- Configurable
- Secure
- Auditable
- Mobile responsive
- cPanel compatible
- Financially accurate
- Easy to maintain

Every financial transaction must be traceable from:

`Member → Plan → Cycle → Payment/Payout → Ledger → Audit`

Every score change must be traceable from:

`Member → Event → Rule → Score Change → History`

Every payout must show:

`Gross Payout → Admin Fee → Net Payout`

Every bulk payment must show:

`Bulk Payment → Slip → Allocation → Individual Contributions`

---

# END OF PRD
