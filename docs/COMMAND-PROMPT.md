# CURSOR COMMAND PROMPT
# Main Kutu System — Vanilla PHP / cPanel

You are the lead software architect and senior PHP developer for this project.

Read `PRD.md` completely before making any code changes.

## PRIMARY OBJECTIVE

Build the Main Kutu Management System according to `PRD.md`.

Technology constraints:

- Vanilla PHP
- PHP 8.2+
- MySQL/MariaDB
- Apache
- cPanel compatible
- HTML5
- CSS3
- Vanilla JavaScript
- PDO
- No Laravel
- No CodeIgniter
- No backend framework
- No Node.js dependency for production

The application must be maintainable by a junior/intermediate PHP developer.

---

# 1. NON-NEGOTIABLE RULES

1. Do not ignore the PRD.
2. Do not invent financial business rules that are not defined.
3. If a business rule is missing, document it in `docs/OPEN-BUSINESS-RULES.md` instead of silently deciding it.
4. Never use FLOAT for money.
5. Use DECIMAL(15,2).
6. Use database transactions for financial operations.
7. Never hard-delete financial transactions.
8. Use adjustment/reversal records instead.
9. Use PDO prepared statements.
10. Escape all HTML output.
11. Validate all user input.
12. Implement CSRF protection.
13. Implement authorization checks.
14. Uploaded payment slips must not be publicly accessible.
15. Store uploaded files using generated safe filenames.
16. Never expose database credentials.
17. Never commit `.env`.
18. Do not place business calculations directly inside views.
19. Controllers must remain thin.
20. Put business logic inside Services.
21. All important financial actions must create audit logs.
22. All score changes must create score history.
23. Do not modify historical calculations when configuration changes.
24. Admin Fee must be versioned.
25. Fixed Payout is the default payout mode.
26. Admin Fee is deducted during payout, not contribution.
27. Member can belong to multiple Plans.
28. Member can make one bulk payment covering multiple Plans.
29. One payment slip can be linked to multiple payment allocations.
30. Partial payment must be supported.
31. Member can view contribution and payout calendars as read-only.
32. Admin can upload payout slip to the specific payout transaction.

---

# 2. DEVELOPMENT WORKFLOW

Before coding:

1. Read PRD.md.
2. Inspect current repository.
3. Identify existing files.
4. Create/update:
   - `docs/IMPLEMENTATION-PLAN.md`
   - `docs/CHANGELOG.md`
5. Break development into small phases.
6. Implement one phase at a time.
7. Test after every significant feature.
8. Never rewrite unrelated working code.

---

# 3. PROJECT STRUCTURE

Use this architecture:

```text
/public_html/
    index.php
    .htaccess

    app/
        config/
        controllers/
        models/
        services/
        repositories/
        middleware/
        helpers/
        validators/
        views/
        routes/

    database/
        migrations/
        seeders/

    public/
        assets/
            css/
            js/
            images/

    storage/
        logs/
        uploads/

    tests/
    docs/
    cron/

    .env.example
    README.md
```

If the actual cPanel document root requires a different structure, keep private application files outside the public web root where possible.

---

# 4. ARCHITECTURE

Use a simple MVC-inspired architecture:

Request
→ Router
→ Middleware
→ Controller
→ Service
→ Repository/Model
→ Database

Response
→ View

Controllers must not contain complex financial calculations.

Recommended services:

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

# 5. DATABASE

Create migrations for all core tables in PRD.

At minimum:

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

Use foreign keys where appropriate.

Add indexes for:

- member_id
- plan_id
- status
- due_date
- payout_date
- transaction_date
- reference

Use UUID/random IDs where useful, but integer BIGINT primary keys are acceptable if architecture remains secure.

---

# 6. FINANCIAL ENGINE

Treat financial operations as high-risk code.

For operations such as:

- Approving bulk payment
- Allocating payment
- Creating payout
- Applying Admin Fee
- Creating ledger entries
- Applying Credit Score event

use:

```sql
BEGIN;
...
COMMIT;
```

and rollback on failure.

Never allow a situation where:

- payment is approved but ledger is missing
- payout is marked paid but no payout record exists
- score changes but history is missing

Use atomic database transactions.

---

# 7. PAYMENT ENGINE

Implement:

## Single Payment

Member pays one contribution.

## Bulk Payment

Member selects multiple outstanding contributions.

Example:

```text
Plan A = RM100
Plan B = RM200
Plan C = RM300

Total = RM600
```

Create:

```text
payment_batches
payment_batch_items
payment_slips
payments
```

The payment batch is the parent transaction.

One slip may belong to the batch.

---

# 8. PAYMENT SLIP

Required for every member contribution payment.

Validate:

- extension
- MIME
- file size
- upload error
- generated storage filename

Never trust:

- original filename
- client MIME
- client extension

Store securely.

Create authenticated file viewing/downloading route.

Do not expose:

```text
/storage/uploads/payment-slips/file.pdf
```

directly.

---

# 9. PAYMENT VERIFICATION

Admin verification:

```text
Submitted
→ Pending Verification
→ Approved
```

or:

```text
Pending Verification
→ Rejected
→ Resubmission
```

On approval:

1. Begin DB transaction.
2. Validate batch total.
3. Validate allocations.
4. Update payments.
5. Update contribution schedules.
6. Create ledger entries.
7. Generate relevant Credit Score event.
8. Create audit log.
9. Commit.
10. Notify member.

If any step fails, rollback.

---

# 10. PARTIAL PAYMENT

Support:

```text
Outstanding:
Plan A = 100
Plan B = 200
Plan C = 300

Payment = 400
```

Allocation can become:

```text
Plan A = Paid 100
Plan B = Paid 200
Plan C = Partial 100
```

Remaining:

Plan C = 200

Do not mark a payment as fully paid unless the allocated amount covers the required amount.

---

# 11. PAYOUT ENGINE

Support exactly:

```text
FIXED_PAYOUT
ACTUAL_COLLECTION
```

Default:

```text
FIXED_PAYOUT
```

## Fixed Payout

```text
Gross Payout = Fixed Payout
Net Payout = Gross Payout - Admin Fee
Shortfall = Gross Payout - Actual Collection
```

## Actual Collection

```text
Gross Payout = Actual Collection
Net Payout = Gross Payout - Admin Fee
```

Never silently change payout mode.

Store payout mode on the payout transaction so historical records remain accurate.

---

# 12. ADMIN FEE ENGINE

Admin Fee applies only during payout.

Never add Admin Fee to contribution.

Support:

```text
FIXED
PERCENTAGE
```

Example:

```text
Gross Payout = 1000
Admin Fee = 5
Net Payout = 995
```

or:

```text
Gross Payout = 5000
Admin Fee = 2%
Fee = 100
Net = 4900
```

Fee configuration must be versioned.

Historical payouts must retain the fee configuration used at the time.

---

# 13. PAYOUT SLIP

Admin must be able to upload payout slip to a specific payout.

Workflow:

```text
Scheduled
→ Due
→ Processing
→ Upload Slip
→ Confirm Paid
→ Paid
```

Do not mark payout as Paid without required confirmation according to the configured workflow.

Store:

- payout_id
- file
- uploader
- uploaded_at
- payment reference

Member can view the slip after the payout becomes available to them.

---

# 14. PAYOUT CALENDAR

Create read-only member calendar.

Show:

- cycle
- payout date
- recipient
- gross payout
- admin fee
- net payout
- status
- slip availability

Member must not be able to modify the schedule.

Admin calendar can contain operational controls.

---

# 15. CREDIT SCORE ENGINE

Initial:

```text
100
```

Range:

```text
0-100
```

Implement configurable rules.

Positive events:

- ON_TIME_PAYMENT
- EARLY_PAYMENT
- CONSISTENT_PAYMENT
- PLAN_COMPLETED

Negative events:

- LATE_PAYMENT
- MISSED_PAYMENT
- PAYMENT_FAILED
- PLAN_WITHDRAWAL
- ACCOUNT_VIOLATION

Every score change must:

1. Read current score.
2. Apply rule.
3. Clamp 0–100.
4. Update current score.
5. Insert score history.
6. Insert audit record if manually triggered.

All steps must be atomic.

---

# 16. SCORE RECOVERY

Score must recover gradually through positive behaviour.

Do not immediately erase historical negative events.

Example:

```text
65
→ +2
67
→ +2
69
```

Make recovery amount configurable.

Maximum score:

100.

---

# 17. CALENDAR AND PAYMENT VISIBILITY

Member can see:

### Contribution Calendar

- contribution date
- cycle
- amount
- status

### Payout Calendar

- payout date
- cycle
- recipient
- payout amount
- status
- slip

Both are read-only.

---

# 18. SECURITY REQUIREMENTS

Implement:

- CSRF
- Session regeneration after login
- Secure session cookies
- HttpOnly
- SameSite
- Password hashing
- Login rate limiting
- Authorization middleware
- Input validation
- Output escaping
- Secure file upload
- Secure download
- Audit logging
- HTTPS enforcement in production

---

# 19. CODING STYLE

Use readable PHP.

Prefer:

```php
declare(strict_types=1);
```

Use typed properties/parameters where practical.

Use classes for services.

Avoid giant files.

Avoid copy/paste business logic.

Use reusable helpers.

Document complicated financial formulas.

---

# 20. UI PRINCIPLES

Use a clean responsive admin dashboard.

Member UI should prioritize:

- Upcoming payment
- Outstanding payment
- Bulk payment
- Upcoming payout
- Credit Score

Admin UI should prioritize:

- Payment verification
- Upcoming payout
- Overdue
- Shortfall
- Low Credit Score
- Plan status

Do not overdesign the MVP.

---

# 21. TESTING

Create tests for:

## Payment

- single payment
- bulk payment
- same slip
- partial payment
- rejected payment
- resubmission

## Payout

- fixed payout
- actual collection
- admin fee fixed
- admin fee percentage
- shortfall

## Credit Score

- late payment
- missed payment
- on-time recovery
- plan completion
- score lower bound
- score upper bound

## Security

- unauthorized access
- CSRF
- invalid upload
- malicious filename
- invalid MIME
- direct file access

## Financial Integrity

- rollback when ledger fails
- rollback when score update fails
- duplicate payment prevention
- duplicate payout prevention

---

# 22. IDEMPOTENCY

Financial actions must not be duplicated if user refreshes or submits twice.

Implement unique references/idempotency checks for:

- payment submission
- payment approval
- payout creation
- payout confirmation
- ledger transaction

---

# 23. CHANGE CONTROL

Before changing business rules:

1. Update PRD.
2. Update implementation plan.
3. Document reason.
4. Implement change.
5. Add/update tests.
6. Update changelog.

Do not silently alter business rules inside code.

---

# 24. CHANGELOG

Maintain:

```text
docs/CHANGELOG.md
```

Format:

```text
## [Version] - YYYY-MM-DD

### Added
-

### Changed
-

### Fixed
-

### Security
-
```

---

# 25. IMPLEMENTATION PLAN

Maintain:

```text
docs/IMPLEMENTATION-PLAN.md
```

Each phase must contain:

- Objective
- Files affected
- Database changes
- Business rules
- Implementation steps
- Tests
- Acceptance criteria
- Status

---

# 26. CURRENT DEVELOPMENT ORDER

Implement in this exact broad order unless there is a documented reason to change:

### Phase 1
Project foundation

### Phase 2
Authentication and roles

### Phase 3
Member management

### Phase 4
Plan management

### Phase 5
Contribution schedule

### Phase 6
Single payment

### Phase 7
Bulk payment and payment slip

### Phase 8
Payment verification

### Phase 9
Payout schedule/calendar

### Phase 10
Fixed Payout

### Phase 11
Actual Collection

### Phase 12
Admin Fee

### Phase 13
Payout slip

### Phase 14
Shortfall

### Phase 15
Credit Score

### Phase 16
Score recovery

### Phase 17
Withdrawal

### Phase 18
Notifications

### Phase 19
Reports

### Phase 20
Security hardening

### Phase 21
Testing/UAT

---

# 27. DEFINITION OF DONE

A feature is not complete until:

- Code implemented
- Validation implemented
- Authorization implemented
- Error handling implemented
- Database transaction considered
- Audit trail implemented where required
- Tests created
- UI completed
- Mobile layout checked
- Documentation updated
- Changelog updated
- No obvious PHP errors
- No SQL injection vulnerability
- No direct sensitive file exposure

---

# 28. FIRST TASK

Do NOT immediately build the whole application.

First:

1. Read `PRD.md`.
2. Inspect repository.
3. Create project structure.
4. Create database migration plan.
5. Create `docs/IMPLEMENTATION-PLAN.md`.
6. Create `docs/CHANGELOG.md`.
7. Create `.env.example`.
8. Create database connection layer using PDO.
9. Create base Router.
10. Create base Controller.
11. Create base View/layout.
12. Create authentication foundation.
13. Run a basic smoke test.
14. Report what was implemented.
15. Wait for the next development instruction.

Do not implement later phases until requested or until the current implementation plan explicitly authorizes the next phase.

# END COMMAND PROMPT
