<?php

declare(strict_types=1);

/**
 * Realistic transaction seeder.
 *
 * After cron/seed_demo.php + cron/seed_demo2.php have created the user
 * roster, this script backfills the rest of the application with realistic
 * transactional data so every page has something to show:
 *
 *  - plan_cycles                (one per month per plan)
 *  - admin_fee_configs + versions
 *  - payment_slips, payment_batches, payments
 *  - ledger_transactions        (one row per payment, plus admin fees)
 *  - payouts                    (mark earlier scheduled payouts as 'paid',
 *                                 create the financial transaction row,
 *                                 leave the near-future ones 'scheduled' /
 *                                 'due' so the dashboard calendar still shows
 *                                 them)
 *  - shortfalls                 (one open + one resolved)
 *  - withdrawal_requests        (1-2 pending + 1 completed past example)
 *  - notifications              (payment confirmations, payout reminders,
 *                                 shortfall alerts, welcome messages that
 *                                 reference existing members)
 *
 * Idempotent: every "create" is preceded by a uniqueness probe (batch_no,
 * payout reference, slip stored_name) so re-running the script is safe.
 *
 * Usage: php cron/seed_demo3.php
 */

define('APP_ROOT', dirname(__DIR__));

require APP_ROOT . '/app/helpers/functions.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $relative = substr($class, strlen($prefix));
    $file = APP_ROOT . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) { require $file; return; }
    $lower = APP_ROOT . '/app/' . strtolower(preg_replace('/\\\\[^\\\\]+$/', '', $relative)) . '/' . substr(strrchr($relative, '\\'), 1) . '.php';
    if (file_exists($lower)) { require $lower; }
});

\App\Config\Config::load();

try {
    $pdo = \App\Core\Database::connection();
} catch (\Throwable $e) {
    fwrite(STDERR, "[ERROR] DB: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

// ---------------------------------------------------------------------------
// Dependencies
// ---------------------------------------------------------------------------
$members       = new \App\Repositories\MemberRepository();
$plans         = new \App\Repositories\PlanRepository();
$pms           = new \App\Repositories\PlanMemberRepository();
$schedules     = new \App\Repositories\ContributionScheduleRepository();
$slips         = new \App\Repositories\PaymentSlipRepository();
$batches       = new \App\Repositories\PaymentBatchRepository();
$payments      = new \App\Repositories\PaymentRepository();
$payouts       = new \App\Repositories\PayoutRepository();
$payoutSched   = new \App\Repositories\PayoutScheduleRepository();
$shortfalls    = new \App\Repositories\ShortfallRepository();
$ledger        = new \App\Repositories\LedgerRepository();
$afConfig      = new \App\Repositories\AdminFeeConfigRepository();
$afVersion     = new \App\Repositories\AdminFeeVersionRepository();
$withdrawals   = new \App\Repositories\WithdrawalRepository();
$notif         = new \App\Repositories\NotificationRepository();
$credit        = new \App\Repositories\CreditScoreRepository();

// ---------------------------------------------------------------------------
// Discovery: pull all plans, members, schedules, payout_schedules.
// ---------------------------------------------------------------------------
$allPlans = $plans->all(null, null);
if ($allPlans === []) {
    fwrite(STDERR, "[ERROR] No plans in DB. Run cron/seed_demo.php + seed_demo2.php first." . PHP_EOL);
    exit(1);
}

$allMembers = [];
foreach ($pdo->query('SELECT m.id, m.member_code, m.user_id, u.name, u.email FROM members m
                     INNER JOIN users u ON u.id = m.user_id
                     WHERE m.status = "active"') as $r) {
    $allMembers[(int) $r['id']] = [
        'member_id'   => (int) $r['id'],
        'member_code' => $r['member_code'],
        'user_id'     => (int) $r['user_id'],
        'name'        => $r['name'],
        'email'       => $r['email'],
    ];
}
if ($allMembers === []) {
    fwrite(STDERR, "[ERROR] No active members. Run the earlier seed scripts." . PHP_EOL);
    exit(1);
}

echo "[info] " . count($allPlans) . " plan, " . count($allMembers) . " member" . PHP_EOL;

// ---------------------------------------------------------------------------
// Helper: count rows by table for idem checks + counting.
// ---------------------------------------------------------------------------
function countRows(\PDO $pdo, string $table): int
{
    return (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
}

function idemCheck(\PDO $pdo, string $table, array $where): bool
{
    $sql = 'SELECT id FROM ' . $table . ' WHERE ';
    $parts = [];
    $params = [];
    foreach ($where as $col => $val) {
        $parts[]           = "`{$col}` = :{$col}";
        $params[':' . $col] = $val;
    }
    $sql .= implode(' AND ', $parts) . ' LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (bool) $stmt->fetchColumn();
}

// ---------------------------------------------------------------------------
// 1) plan_cycles — one cycle per calendar month per plan, covering the last
//    4 months and the next 12.
// ---------------------------------------------------------------------------
echo PHP_EOL . "--- 1) plan_cycles ---" . PHP_EOL;
$insertedCycles = 0;
foreach ($allPlans as $plan) {
    $months = [];
    for ($i = -4; $i <= 12; $i++) {
        $months[] = (new DateTimeImmutable('first day of this month'))->modify("{$i} months");
    }
    foreach ($months as $monthStart) {
        $cycleNo = (int) $monthStart->format('n');
        $endDate = $monthStart->modify('last day of this month');
        $exists = $pdo->prepare('SELECT id FROM plan_cycles WHERE plan_id = :p AND cycle_no = :c LIMIT 1');
        $exists->execute([':p' => $plan->id, ':c' => $cycleNo]);
        if ((int) $exists->fetchColumn() > 0) {
            continue;
        }
        $now = new DateTimeImmutable('today');
        $status = 'completed';
        if ($monthStart > $now) {
            $status = 'upcoming';
        } elseif ($monthStart->format('Y-m') === $now->format('Y-m')) {
            $status = 'active';
        }
        $pdo->prepare(
            'INSERT INTO plan_cycles (plan_id, cycle_no, start_date, end_date, status, created_at)
             VALUES (:p, :c, :s, :e, :st, NOW())'
        )->execute([
            ':p'  => $plan->id,
            ':c'  => $cycleNo,
            ':s'  => $monthStart->format('Y-m-d'),
            ':e'  => $endDate->format('Y-m-d'),
            ':st' => $status,
        ]);
        $insertedCycles++;
    }
    // Pull cycles back (we need them as cycles[*] -> plan_cycles.id).
    $rows = $pdo->prepare('SELECT id, cycle_no, start_date, end_date FROM plan_cycles WHERE plan_id = :p ORDER BY cycle_no ASC');
    $rows->execute([':p' => $plan->id]);
    $plan->cycles = $rows->fetchAll(\PDO::FETCH_ASSOC);
}
echo "[ins] {$insertedCycles} plan_cycles" . PHP_EOL;

// ---------------------------------------------------------------------------
// 2) admin fee config per plan — fixed RM25 (5% of RM500, or a flat RM25 for
//    smaller plans). Stored in admin_fee_configs + one active version row.
// ---------------------------------------------------------------------------
echo PHP_EOL . "--- 2) admin fee configs ---" . PHP_EOL;
$feeByPlan = [];
foreach ($allPlans as $plan) {
    $existing = $afConfig->findByPlan($plan->id);
    $feeValue = ((float) $plan->contributionAmount >= 500) ? 25.00 : 12.00;
    if ($existing === null) {
        $afId = $afConfig->create([
            'plan_id'   => $plan->id,
            'enabled'   => 1,
            'fee_type'  => 'fixed',
            'fee_value' => $feeValue,
            'status'    => 'active',
        ]);
        $afVersion->create([
            'admin_fee_config_id' => $afId,
            'fee_type'            => 'fixed',
            'fee_value'           => $feeValue,
            'effective_date'      => date('Y-m-01', strtotime('-4 months')),
            'status'              => 'active',
        ]);
        echo "[ins] admin_fee_config plan={$plan->id} (RM{$feeValue})" . PHP_EOL;
    } else {
        $afId = $existing->id;
    }
    $feeByPlan[$plan->id] = $feeValue;
}

// ---------------------------------------------------------------------------
// 3) For each plan: ensure every member has a plan_members row + every cycle
//    has matching contribution_schedules (only if missing).
// ---------------------------------------------------------------------------
echo PHP_EOL . "--- 3) contribution schedules ---" . PHP_EOL;
$schedulesByPlan = [];
$createdSchedules = 0;
foreach ($allPlans as $plan) {
    foreach ($plan->cycles as $cycle) {
        $cycId = (int) $cycle['id'];
        foreach ($allMembers as $m) {
            $exist = $pdo->prepare('SELECT id FROM contribution_schedules WHERE plan_id = :p AND plan_cycle_id = :c AND member_id = :m LIMIT 1');
            $exist->execute([':p' => $plan->id, ':c' => $cycId, ':m' => $m['member_id']]);
            if ((int) $exist->fetchColumn() > 0) {
                $cid = (int) $exist->fetchColumn();
            } else {
                $due = (new DateTimeImmutable($cycle['end_date']))->modify('-3 days')->format('Y-m-d');
                $cid = $schedules->create([
                    'plan_id'        => $plan->id,
                    'plan_cycle_id'  => $cycId,
                    'member_id'      => $m['member_id'],
                    'due_date'       => $due,
                    'amount'         => (string) $plan->contributionAmount,
                    'amount_paid'    => '0.00',
                    'status'         => 'pending',
                ]);
                $createdSchedules++;
            }
            $schedulesByPlan[$plan->id][$cycId][$m['member_id']] = [
                'id'        => $cid,
                'due_date'  => $cycle['end_date'],
                'amount'    => (string) $plan->contributionAmount,
                'plan_id'   => $plan->id,
            ];
        }
    }
}
echo "[ins] {$createdSchedules} contribution_schedules" . PHP_EOL;

// ---------------------------------------------------------------------------
// 4) Backfill historical payments for past cycles — one batch per member per
//    past month, status approved, with a payment_slip + payment_batch_items +
//    payments + ledger rows.
// ---------------------------------------------------------------------------
echo PHP_EOL . "--- 4) historical payments (batches + slips + payments + ledger) ---" . PHP_EOL;
$today = new DateTimeImmutable('today');
$pastCycleStart = $today->modify('-4 months')->format('Y-m-01');
$batchesInserted = 0;
$paymentsInserted = 0;
$ledgerInserted = 0;
$slipsInserted = 0;

foreach ($allMembers as $m) {
    foreach ($allPlans as $plan) {
        $fee = (float) ($feeByPlan[$plan->id] ?? 0);
        foreach ($plan->cycles as $cycle) {
            $cycleStart = $cycle['start_date'];
            if ($cycleStart >= $today->format('Y-m-01')) {
                continue; // only back-fill past cycles
            }
            $batchNo = sprintf('BATCH-%s-%04d-%02d',
                str_replace('-', '', $cycleStart),
                $m['member_id'],
                (int) $plan->id
            );
            if (idemCheck($pdo, 'payment_batches', ['batch_no' => $batchNo])) {
                continue;
            }

            // Fake slip row (the file itself is not generated — we only
            // populate the DB row). The original_name varies so it looks
            // realistic.
            $storedName = sprintf('slip_%s_m%04d_p%d_%s.png',
                str_replace('-', '', $cycleStart),
                $m['member_id'],
                (int) $plan->id,
                bin2hex(random_bytes(4))
            );
            $slipId = $pdo->prepare(
                'INSERT INTO payment_slips (member_id, stored_name, original_name, mime_type, size_bytes, purpose, uploaded_by, created_at)
                 VALUES (:m, :s, :o, :t, :z, :p, :u, NOW())'
            )->execute([
                ':m' => $m['member_id'],
                ':s' => $storedName,
                ':o' => 'bayaran-' . substr($cycleStart, 0, 7) . '.png',
                ':t' => 'image/png',
                ':z' => random_int(80_000, 320_000),
                ':p' => 'contribution',
                ':u' => $m['user_id'],
            ]) ? (int) $pdo->lastInsertId() : null;
            if ($slipId) { $slipsInserted++; }

            $contrib = (float) $plan->contributionAmount;
            $totalAmount = $contrib + $fee;

            $batchId = $batches->create([
                'batch_no'        => $batchNo,
                'member_id'       => $m['member_id'],
                'total_amount'    => (string) $totalAmount,
                'payment_slip_id' => $slipId,
                'status'          => 'approved',
                'note'            => 'Caruman ' . substr($cycleStart, 0, 7) . ' untuk Pelan ' . $plan->planCode,
            ]);
            // Stamp verified_by / verified_at because status = approved.
            $pdo->prepare('UPDATE payment_batches SET verified_by = :v, verified_at = :a WHERE id = :i')
                ->execute([':v' => 1, ':a' => date('Y-m-d H:i:s', strtotime($cycleStart . ' +5 days')), ':i' => $batchId]);
            $batchesInserted++;

            $schedForMember = $schedulesByPlan[$plan->id][(int) $cycle['id']][$m['member_id']] ?? null;
            if ($schedForMember === null) continue;

            // Batch item — one row per plan (here it's one plan/cycle).
            $pdo->prepare(
                'INSERT INTO payment_batch_items (batch_id, plan_id, contribution_schedule_id, amount, created_at)
                 VALUES (:b, :p, :cs, :a, NOW())'
            )->execute([
                ':b'  => $batchId,
                ':p'  => $plan->id,
                ':cs' => $schedForMember['id'],
                ':a'  => $totalAmount,
            ]);

            // The actual payment row (one payment per contribution cycle).
            $paymentDate = date('Y-m-d H:i:s', strtotime($cycleStart . ' +7 days'));
            $paymentId = $payments->create([
                'member_id'              => $m['member_id'],
                'plan_id'                => $plan->id,
                'contribution_schedule_id' => $schedForMember['id'],
                'batch_id'               => $batchId,
                'amount'                 => (string) $totalAmount,
                'status'                 => 'approved',
                'payment_slip_id'        => $slipId,
                'note'                   => 'Bayaran auto-debit untuk kitar ' . substr($cycleStart, 0, 7),
            ]);
            $pdo->prepare('UPDATE payments SET verified_by = :v, verified_at = :a WHERE id = :i')
                ->execute([':v' => 1, ':a' => $paymentDate, ':i' => $paymentId]);
            $schedules->markPaid($schedForMember['id'], (string) $totalAmount, 'paid');
            $paymentsInserted++;

            // Ledger — contribution + admin fee.
            $ledger->create([
                'transaction_type' => 'contribution',
                'member_id'        => $m['member_id'],
                'plan_id'          => $plan->id,
                'reference_id'     => $paymentId,
                'amount'           => (string) $contrib,
                'description'      => 'Caruman ' . substr($cycleStart, 0, 7) . ' – Pelan ' . $plan->planCode,
            ]);
            $ledgerInserted++;

            if ($fee > 0) {
                $ledger->create([
                    'transaction_type' => 'admin_fee',
                    'member_id'        => $m['member_id'],
                    'plan_id'          => $plan->id,
                    'reference_id'     => $paymentId,
                    'amount'           => (string) $fee,
                    'description'      => 'Yuran pentadbiran ' . substr($cycleStart, 0, 7),
                ]);
                $ledgerInserted++;
            }
        }
    }
}
echo "[ins] payment_slips:{$slipsInserted} payment_batches:{$batchesInserted} payments:{$paymentsInserted} ledger:{$ledgerInserted}" . PHP_EOL;

// ---------------------------------------------------------------------------
// 5) Current month — a single "pending_verification" batch for each member so
//    the admin /verification queue has work. Generates 1 slip + 1 batch + 1
//    payment row.
// ---------------------------------------------------------------------------
echo PHP_EOL . "--- 5) current month pending_verification batches ---" . PHP_EOL;
$currentCycle = (int) date('n');
$cycleRow = null;
foreach ($allPlans as $plan) {
    foreach ($plan->cycles as $cycle) {
        if ((int) $cycle['cycle_no'] === $currentCycle) {
            $cycleRow = ['plan' => $plan, 'cycle' => $cycle];
            break 2;
        }
    }
}
if ($cycleRow !== null) {
    $plan = $cycleRow['plan'];
    $cyc  = $cycleRow['cycle'];
    $cycId = (int) $cyc['id'];
    $curPending = 0;
    foreach ($allMembers as $i => $m) {
        // ~40% of members submit a slip this month, the rest don't yet.
        if (($i % 5) === 0 || ($i % 7) === 0) {
            $batchNo = sprintf('BATCH-CURR-%04d-%02d', $m['member_id'], (int) $plan->id);
            if (idemCheck($pdo, 'payment_batches', ['batch_no' => $batchNo])) continue;

            $storedName = sprintf('slip_curr_m%04d_p%d.png', $m['member_id'], (int) $plan->id);
            $pdo->prepare(
                'INSERT INTO payment_slips (member_id, stored_name, original_name, mime_type, size_bytes, purpose, uploaded_by, created_at)
                 VALUES (:m, :s, :o, :t, :z, :p, :u, NOW())'
            )->execute([
                ':m' => $m['member_id'],
                ':s' => $storedName,
                ':o' => 'bayaran-bulan-ini.png',
                ':t' => 'image/png',
                ':z' => random_int(80_000, 320_000),
                ':p' => 'contribution',
                ':u' => $m['user_id'],
            ]);
            $slipId = (int) $pdo->lastInsertId();

            $batchId = $batches->create([
                'batch_no'        => $batchNo,
                'member_id'       => $m['member_id'],
                'total_amount'    => (string) ($plan->contributionAmount + $feeByPlan[$plan->id]),
                'payment_slip_id' => $slipId,
                'status'          => 'pending_verification',
                'note'            => 'Caruman bulan ' . date('M Y') . ' – menunggu semakan pentadbir',
            ]);

            $schedForMember = $schedulesByPlan[$plan->id][$cycId][$m['member_id']] ?? null;
            if ($schedForMember !== null) {
                $pdo->prepare(
                    'INSERT INTO payment_batch_items (batch_id, plan_id, contribution_schedule_id, amount, created_at)
                     VALUES (:b, :p, :cs, :a, NOW())'
                )->execute([
                    ':b' => $batchId, ':p' => $plan->id, ':cs' => $schedForMember['id'],
                    ':a' => (float) $plan->contributionAmount + (float) $feeByPlan[$plan->id],
                ]);
                $payments->create([
                    'member_id'              => $m['member_id'],
                    'plan_id'                => $plan->id,
                    'contribution_schedule_id' => $schedForMember['id'],
                    'batch_id'               => $batchId,
                    'amount'                 => (string) ($plan->contributionAmount + $feeByPlan[$plan->id]),
                    'status'                 => 'pending_verification',
                    'payment_slip_id'        => $slipId,
                ]);
                $curPending++;
            }
        }
    }
    echo "[ins] {$curPending} pending_verification batches for current cycle" . PHP_EOL;
}

// ---------------------------------------------------------------------------
// 6) Realise scheduled payouts — payouts whose payout_date is in the past
//    become 'paid'. The most recent past payout is paid via simulated bank
//    transfer; older ones too. The near-future scheduled ones stay put so
//    the dashboard calendar continues to display them.
// ---------------------------------------------------------------------------
echo PHP_EOL . "--- 6) realise scheduled payouts ---" . PHP_EOL;
$realised = 0;
foreach ($allPlans as $plan) {
    $rows = $pdo->prepare(
        'SELECT ps.* FROM payout_schedules ps WHERE ps.plan_id = :p AND ps.payout_date <= :today'
    );
    $rows->execute([':p' => $plan->id, ':today' => $today->format('Y-m-d')]);
    $past = $rows->fetchAll(\PDO::FETCH_ASSOC);

    // Also include older payout dates that go back to the past 5 weeks for
    // richer history.
    $rows2 = $pdo->prepare(
        'SELECT ps.* FROM payout_schedules ps WHERE ps.plan_id = :p
         AND ps.payout_date > :today AND ps.payout_date <= :future
         ORDER BY ps.payout_date ASC LIMIT 4'
    );
    $rows2->execute([
        ':p' => $plan->id,
        ':today' => $today->format('Y-m-d'),
        ':future' => $today->modify('+4 days')->format('Y-m-d'),
    ]);
    $soonDue = $rows2->fetchAll(\PDO::FETCH_ASSOC);

    // If a plan doesn't have any schedules yet, skip.
    if ($past === [] && $soonDue === []) continue;

    $counter = 0;
    foreach ($past as $row) {
        if (idemCheck($pdo, 'payouts', ['payout_schedule_id' => $row['id']])) continue;
        $gross    = (float) ($row['expected_amount'] ?? 0);
        $fee      = (float) ($feeByPlan[$plan->id] ?? 0);
        $net      = $gross - $fee;
        if ($net < 0) $net = 0;
        $payoutId = $payouts->create([
            'plan_id'             => $plan->id,
            'plan_cycle_id'       => null,
            'payout_schedule_id'  => (int) $row['id'],
            'recipient_member_id' => (int) $row['recipient_member_id'],
            'gross_payout'        => (string) $gross,
            'actual_collection'   => (string) $gross,
            'admin_fee'           => (string) $fee,
            'net_payout'          => (string) $net,
            'payout_mode'         => $plan->payoutMode ?? 'fixed',
            'shortfall_amount'    => '0.00',
            'status'              => 'paid',
            'payment_reference'   => sprintf('TRF-%s-%05d', str_replace('-', '', $row['payout_date']), $counter),
        ]);
        $paidDate = $row['payout_date'] . ' 10:30:00';
        $payouts->updateStatus($payoutId, 'paid', 1, $paidDate);
        // Flip schedule status too so the dashboard calendar no longer shows
        // past entries redundantly.
        $payoutSched->updateStatus((int) $row['id'], 'paid');

        $ledger->create([
            'transaction_type' => 'payout',
            'member_id'        => (int) $row['recipient_member_id'],
            'plan_id'          => $plan->id,
            'reference_id'     => $payoutId,
            'amount'           => '-' . number_format($net, 2, '.', ''),
            'description'      => 'Pembayaran payout ' . $row['payout_date'],
        ]);
        if ($fee > 0) {
            $ledger->create([
                'transaction_type' => 'admin_fee',
                'member_id'        => null,
                'plan_id'          => $plan->id,
                'reference_id'     => $payoutId,
                'amount'           => (string) $fee,
                'description'      => 'Yuran pentadbiran payout ' . $row['payout_date'],
            ]);
        }
        $realised++;
        $counter++;
    }

    // For soon-due payouts, leave them as 'scheduled' but bump them to
    // 'due' so the admin dashboard "Giliran dapat kutu" card surfaces
    // them as actionable.
    foreach ($soonDue as $row) {
        if ((int) $row['status'] !== 4 /* paid */) {
            $payoutSched->updateStatus((int) $row['id'], 'due');
        }
    }
}
echo "[ins] payouts realised: {$realised}" . PHP_EOL;

// ---------------------------------------------------------------------------
// 7) Shortfalls — one open, one resolved (so the admin /shortfalls list
//    has something for both filters).
// ---------------------------------------------------------------------------
echo PHP_EOL . "--- 7) shortfalls ---" . PHP_EOL;
$shortCreated = 0;
foreach ($allPlans as $plan) {
    // Make sure we have at least one "open" shortfall.
    if (!idemCheck($pdo, 'shortfalls', ['plan_id' => $plan->id, 'status' => 'open'])) {
        $expected  = (float) $plan->fixedPayoutAmount;
        $collected = round($expected * 0.85, 2); // 85% collected
        $shortfallId = $shortfalls->create([
            'plan_id'           => $plan->id,
            'plan_cycle_id'     => null,
            'expected_amount'   => (string) $expected,
            'actual_collection' => (string) $collected,
            'shortfall_amount'  => (string) ($expected - $collected),
            'status'            => 'open',
        ]);
        $shortCreated++;
    }
    if (!idemCheck($pdo, 'shortfalls', ['plan_id' => $plan->id, 'status' => 'resolved'])) {
        $expected  = (float) $plan->fixedPayoutAmount;
        $collected = $expected;
        $sid = $shortfalls->create([
            'plan_id'           => $plan->id,
            'plan_cycle_id'     => null,
            'expected_amount'   => (string) $expected,
            'actual_collection' => (string) $collected,
            'shortfall_amount'  => (string) ($expected - $collected),
            'status'            => 'resolved',
        ]);
        $pdo->prepare('UPDATE shortfalls SET resolution = :r, resolved_at = NOW(), approved_by = 1 WHERE id = :id')
            ->execute([':r' => 'Tertutup selepas kutipan penuh', ':id' => $sid]);
        $shortCreated++;
    }
}
echo "[ins] shortfalls: {$shortCreated}" . PHP_EOL;

// ---------------------------------------------------------------------------
// 8) Withdrawal requests — 1 completed, 2 pending for variety.
// ---------------------------------------------------------------------------
echo PHP_EOL . "--- 8) withdrawal requests ---" . PHP_EOL;
$withCreated = 0;
$memberKeys = array_keys($allMembers);
$planKeys   = array_keys($allPlans);
if ($memberKeys !== [] && $planKeys !== []) {
    $samples = [
        ['status' => 'completed', 'reason' => 'Telah tamat kitar; mohon pengeluaran penuh'],
        ['status' => 'pending',   'reason' => 'Perubahan pekerjaan; tidak dapat teruskan'],
        ['status' => 'pending',   'reason' => 'Kesihatan – perlukan wang tunai segera'],
    ];
    foreach ($samples as $i => $s) {
        if (!idemCheck($pdo, 'withdrawal_requests', ['reason' => $s['reason']])) {
            $withdrawals->create([
                'member_id'     => $allMembers[$memberKeys[$i % count($memberKeys)]]['member_id'],
                'plan_id'       => $allPlans[$planKeys[$i % count($planKeys)]]->id,
                'reason'        => $s['reason'],
                'request_date'  => date('Y-m-d H:i:s', strtotime('-' . (15 - $i * 3) . ' days')),
                'current_cycle' => 3,
                'outstanding'   => '0.00',
                'score_impact'  => -10,
                'status'        => $s['status'],
                'approved_by'   => $s['status'] === 'completed' ? 1 : null,
                'decision_date' => $s['status'] === 'completed' ? date('Y-m-d H:i:s', strtotime('-10 days')) : null,
                'notes'         => $s['status'] === 'completed' ? 'Diluluskan selepas semakan dokumentasi.' : null,
            ]);
            $withCreated++;
        }
    }
}
echo "[ins] withdrawal_requests: {$withCreated}" . PHP_EOL;

// ---------------------------------------------------------------------------
// 9) Notifications — payment confirmations for past batches, payout
//    confirmations, system reminders, shortfall alerts.
// ---------------------------------------------------------------------------
echo PHP_EOL . "--- 9) notifications ---" . PHP_EOL;
$notifInserted = 0;
foreach ($allMembers as $m) {
    // Payment confirmation for latest approved payment
    $latestPay = $pdo->prepare(
        'SELECT p.id, p.amount, p.verified_at FROM payments p WHERE p.member_id = :m AND p.status = "approved" ORDER BY p.id DESC LIMIT 1'
    );
    $latestPay->execute([':m' => $m['member_id']]);
    $payRow = $latestPay->fetch(\PDO::FETCH_ASSOC);
    if ($payRow && !idemCheck($pdo, 'notifications', ['recipient_id' => $m['user_id'], 'reference_type' => 'payment', 'reference_id' => $payRow['id']])) {
        $notif->create([
            'recipient_id'  => $m['user_id'],
            'type'          => 'payment.approved',
            'title'         => 'Pembayaran Diluluskan',
            'message'       => 'Bayaran RM ' . number_format((float) $payRow['amount'], 2) . ' telah diluluskan. Slip dan resit boleh dimuat turun.',
            'reference_type' => 'payment',
            'reference_id'  => (int) $payRow['id'],
            'channel'       => 'in_app',
            'is_read'       => (int) (random_int(0, 3) > 0),
        ]);
        $notifInserted++;
    }

    // Payout confirmation for the most recent paid payout for this member
    $po = $pdo->prepare(
        'SELECT p.id, p.net_payout, p.paid_date FROM payouts p WHERE p.recipient_member_id = :m AND p.status = "paid" ORDER BY p.paid_date DESC LIMIT 1'
    );
    $po->execute([':m' => $m['member_id']]);
    $poRow = $po->fetch(\PDO::FETCH_ASSOC);
    if ($poRow && !idemCheck($pdo, 'notifications', ['recipient_id' => $m['user_id'], 'reference_type' => 'payout', 'reference_id' => $poRow['id']])) {
        $notif->create([
            'recipient_id'  => $m['user_id'],
            'type'          => 'payout.paid',
            'title'         => 'Payout Telah Dibayar',
            'message'       => 'Payout RM ' . number_format((float) $poRow['net_payout'], 2) . ' telah dipindahkan ke akaun bank berdaftar.',
            'reference_type' => 'payout',
            'reference_id'  => (int) $poRow['id'],
            'channel'       => 'in_app',
            'is_read'       => 0,
        ]);
        $notifInserted++;
    }

    // Pending verification reminder (current cycle) — only for members
    // who actually have a pending_verification batch.
    $hasPending = (int) $pdo->query(
        'SELECT COUNT(*) FROM payment_batches WHERE member_id = ' . (int) $m['member_id'] . ' AND status = "pending_verification"'
    )->fetchColumn();
    if ($hasPending > 0 && !idemCheck($pdo, 'notifications', ['recipient_id' => $m['user_id'], 'type' => 'payment.pending_reminder'])) {
        $notif->create([
            'recipient_id'  => $m['user_id'],
            'type'          => 'payment.pending_reminder',
            'title'         => 'Bayaran Dalam Semakan',
            'message'       => 'Bayaran caruman bulan ini sedang disemak oleh pentadbir. Sila bersabar — biasanya 1-3 hari bekerja.',
            'channel'       => 'in_app',
            'is_read'       => (int) (random_int(0, 3) > 1),
        ]);
        $notifInserted++;
    }

    // Shortfall alert — only for the first member if a shortfall exists
    if ($m['member_id'] === reset($memberKeys) && !idemCheck($pdo, 'notifications', ['type' => 'shortfall.alert', 'recipient_id' => $m['user_id']])) {
        $notif->create([
            'recipient_id'  => $m['user_id'],
            'type'          => 'shortfall.alert',
            'title'         => 'Shortfall Aktif',
            'message'       => 'Terdapat shortfall dalam kitar semasa. Pentadbir sedang mengurus kutipan tambahan.',
            'channel'       => 'in_app',
            'is_read'       => 0,
        ]);
        $notifInserted++;
    }
}
echo "[ins] notifications: {$notifInserted}" . PHP_EOL;

// ---------------------------------------------------------------------------
// 10) Plan admin notification — email-blast log entry so the email_blasts
//     table is populated (and the admin settings page works even if they
//     hadn't sent any yet).
// ---------------------------------------------------------------------------
echo PHP_EOL . "--- 10) sample email_blasts ---" . PHP_EOL;
$blastCreated = 0;
try {
    $exists = $pdo->query("SHOW TABLES LIKE 'email_blasts'")->fetch();
    if ($exists) {
        $blRepo = new \App\Repositories\EmailBlastRepository();
        $total  = count($allMembers);
        $samples = [
            [
                'subject'     => 'Peringatan Tarikh Akhir Caruman',
                'message'     => "Salam sejahtera,\n\nIni adalah peringatan bahawa tarikh akhir caruman bulan ini adalah pada hujung bulan. Sila buat bayaran tepat pada masnya.\n\nTerima kasih.",
                'target'      => 'member',
                'recipients'  => $total,
                'status'      => 'sent',
                'sent_at'     => date('Y-m-d H:i:s', strtotime('-7 days')),
            ],
            [
                'subject'     => 'Notis Mesyuarat Agung Tahunan',
                'message'     => "Salam,\n\nSila hadir ke Mesyuarat Agung Tahunan pada tarikh yang akan dimaklumkan kemudian. Kehadiran amat dialu-alukan.\n\nPentadbir.",
                'target'      => 'all',
                'recipients'  => $total,
                'status'      => 'sent',
                'sent_at'     => date('Y-m-d H:i:s', strtotime('-14 days')),
            ],
        ];
        foreach ($samples as $s) {
            if ($blRepo->isTableReady() && !idemCheck($pdo, 'email_blasts', ['subject' => $s['subject']])) {
                $pdo->prepare(
                    'INSERT INTO email_blasts (subject, message, target_role, recipient_count, status, created_by, sent_at, created_at)
                     VALUES (:s, :m, :tr, :rc, :st, 1, :sa, NOW())'
                )->execute([
                    ':s'  => $s['subject'],
                    ':m'  => $s['message'],
                    ':tr' => $s['target'],
                    ':rc' => $s['recipients'],
                    ':st' => $s['status'],
                    ':sa' => $s['sent_at'],
                ]);
                $blastCreated++;
            }
        }
    }
} catch (\Throwable $e) {
    // email_blasts table may not exist; skip silently.
}
echo "[ins] email_blasts: {$blastCreated}" . PHP_EOL;

// ---------------------------------------------------------------------------
// Summary
// ---------------------------------------------------------------------------
echo PHP_EOL . "=== seed_demo3 complete ===" . PHP_EOL;
echo "Tables now contain:" . PHP_EOL;
foreach (['plan_cycles','admin_fee_configs','admin_fee_versions','contribution_schedules','payment_slips','payment_batches','payment_batch_items','payments','payouts','shortfalls','withdrawal_requests','notifications','ledger_transactions','email_blasts'] as $t) {
    try {
        $n = countRows($pdo, $t);
        echo sprintf("  %-26s  %6d rows\n", $t, $n);
    } catch (\Throwable $e) {
        echo sprintf("  %-26s  (missing)\n", $t);
    }
}
