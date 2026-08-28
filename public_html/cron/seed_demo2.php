<?php

declare(strict_types=1);

/**
 * Extended demo data seeder.
 *
 * Adds (idempotently):
 *   - 6 additional members (various email addresses & credit score levels).
 *   - 1 additional Plan (`PLN-KUTU3`) with members enrolled.
 *   - Contribution schedules for the new plan.
 *   - Payout schedules for every plan/member, with at least one payout_date
 *     set to today and additional dates in the next 7 days so the admin
 *     "giliran dapat kutu" calendar shows realistic data immediately.
 *
 * Usage: php cron/seed_demo2.php
 *
 * Safe to re-run — existing rows are detected and skipped.
 */

define('APP_ROOT', dirname(__DIR__));

require APP_ROOT . '/app/helpers/functions.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = APP_ROOT . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) {
        require $file;
        return;
    }
    $lower = APP_ROOT . '/app/' . strtolower(preg_replace('/\\\\[^\\\\]+$/', '', $relative)) . '/' . substr(strrchr($relative, '\\'), 1) . '.php';
    if (file_exists($lower)) {
        require $lower;
    }
});

\App\Config\Config::load();

try {
    $pdo = \App\Core\Database::connection();
} catch (\Throwable $e) {
    fwrite(STDERR, '[ERROR] DB: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

$members  = new \App\Repositories\MemberRepository();
$plans    = new \App\Repositories\PlanRepository();
$pms      = new \App\Repositories\PlanMemberRepository();
$sched    = new \App\Repositories\ContributionScheduleRepository();
$credit   = new \App\Repositories\CreditScoreRepository();
$payouts  = new \App\Repositories\PayoutScheduleRepository();

// Seed default credit score rules (idempotent).
$credit->seedDefaultRules();

// ---------------------------------------------------------------------------
// 1. Additional members (6 entries).
// ---------------------------------------------------------------------------
$extraPeople = [
    [
        'email'    => 'faisal@mainkutu.local',
        'password' => 'Faisal@12345',
        'name'     => 'Faisal bin Karim',
        'phone'    => '0172233445',
        'ic'       => '870612-14-2233',
        'address'  => 'No. 8, Jalan Bukit Bintang, 55100 Kuala Lumpur',
        'score'    => 78,
    ],
    [
        'email'    => 'lina@mainkutu.local',
        'password' => 'Lina@12345',
        'name'     => 'Lina binti Osman',
        'phone'    => '0193344556',
        'ic'       => '930215-08-9911',
        'address'  => 'No. 14, Jalan Tun Razak, 50400 Kuala Lumpur',
        'score'    => 92,
    ],
    [
        'email'    => 'arif@mainkutu.local',
        'password' => 'Arif@12345',
        'name'     => 'Arif bin Hassan',
        'phone'    => '0114567890',
        'ic'       => '850923-10-3344',
        'address'  => 'No. 22, Jalan Gombak, 53000 Kuala Lumpur',
        'score'    => 60,
    ],
    [
        'email'    => 'nadia@mainkutu.local',
        'password' => 'Nadia@12345',
        'name'     => 'Nadia binti Khalid',
        'phone'    => '0115566778',
        'ic'       => '960405-14-7788',
        'address'  => 'No. 9, Jalan Kuchai Lama, 58200 Kuala Lumpur',
        'score'    => 86,
    ],
    [
        'email'    => 'khairul@mainkutu.local',
        'password' => 'Khairul@12345',
        'name'     => 'Khairul Anwar',
        'phone'    => '0183344556',
        'ic'       => '820701-08-1122',
        'address'  => 'No. 5, Jalan Sultan Ismail, 50250 Kuala Lumpur',
        'score'    => 45,
    ],
    [
        'email'    => 'yusof@mainkutu.local',
        'password' => 'Yusof@12345',
        'name'     => 'Yusof bin Ismail',
        'phone'    => '0167788990',
        'ic'       => '890111-10-4455',
        'address'  => 'No. 17, Jalan Wangsa Maju, 53300 Kuala Lumpur',
        'score'    => 70,
    ],
];

$roleId = (int) $pdo->query("SELECT id FROM roles WHERE slug = 'member' LIMIT 1")->fetchColumn();
if ($roleId === 0) {
    $roleId = 2;
}

$memberIds = []; // member_id => user_id
foreach ($extraPeople as $p) {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :e LIMIT 1');
    $stmt->execute([':e' => $p['email']]);
    $userId = (int) $stmt->fetchColumn();

    if ($userId === 0) {
        $hash = password_hash($p['password'], PASSWORD_BCRYPT);
        $pdo->prepare(
            'INSERT INTO users (name, email, password, role_id, status, must_reset_password, created_at, updated_at)
             VALUES (:n, :e, :p, :r, :s, 0, NOW(), NOW())'
        )->execute([
            ':n' => $p['name'],
            ':e' => $p['email'],
            ':p' => $hash,
            ':r' => $roleId,
            ':s' => 'active',
        ]);
        $userId = (int) $pdo->lastInsertId();
        echo "[ins] user {$p['email']} (id={$userId})" . PHP_EOL;
    } else {
        echo "[skip] user {$p['email']} (id={$userId})" . PHP_EOL;
    }

    $member = $members->findByUserId($userId);
    if ($member === null) {
        $memberId = $members->create([
            'user_id'      => $userId,
            'member_code'  => 'M' . str_pad((string) $userId, 5, '0', STR_PAD_LEFT),
            'phone'        => $p['phone'],
            'ic_number'    => $p['ic'],
            'address'      => $p['address'],
            'credit_score' => $p['score'],
            'status'       => 'active',
        ]);
        $credit->upsert($memberId, $p['score'], \App\Services\CreditScoreService::levelFor($p['score']));
        echo "[ins] member profile (id={$memberId})" . PHP_EOL;
    } else {
        $memberId = $member->id;
    }

    $memberIds[$memberId] = $userId;
}

// ---------------------------------------------------------------------------
// 2. Pull ALL existing members (we want new plans to include all of them).
// ---------------------------------------------------------------------------
$allMemberIds = [];
$rows = $pdo->query(
    'SELECT m.id FROM members m INNER JOIN users u ON u.id = m.user_id WHERE m.status = :s ORDER BY m.id ASC'
)->fetchAll(\PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    $allMemberIds[] = (int) $r['id'];
}
if ($allMemberIds === []) {
    fwrite(STDERR, '[ERROR] No members found in DB. Run cron/seed_demo.php first.' . PHP_EOL);
    exit(1);
}

// ---------------------------------------------------------------------------
// 3. A third Plan.
// ---------------------------------------------------------------------------
$extraPlan = [
    'plan_code'           => 'PLN-KUTU3',
    'name'                => 'Pelan Kutu Premium Tahunan',
    'description'         => 'Pelan premium RM500 sebulan selama 12 kitaran — untuk komuniti lebih besar.',
    'number_of_members'   => count($allMemberIds),
    'contribution_amount' => '500.00',
    'payment_frequency'   => 'monthly',
    'number_of_cycles'    => 12,
    'start_date'          => date('Y-m-01', strtotime('-1 month')),
    'end_date'            => date('Y-m-d', strtotime('+11 months')),
    'status'              => 'active',
    'max_members'         => 20,
    'min_credit_score'    => 60,
    'approval_required'   => 0,
    'allow_multiple'      => 1,
    'withdrawal_allowed'  => 1,
    'payout_mode'         => 'fixed',
    'fixed_payout_amount' => (string) (500 * count($allMemberIds)),
    'payout_frequency'    => 'monthly',
    'payout_day'          => (int) date('j'), // day-of-month, so we get a payout today
    'min_score'           => 0,
    'created_by'          => 1,
];

$plan = null;
foreach ($plans->all(null, null) as $p) {
    if ($p->planCode === $extraPlan['plan_code']) {
        $plan = $p;
        break;
    }
}
if ($plan === null) {
    $extraPlanId = $plans->create($extraPlan);
    echo "[ins] plan {$extraPlan['plan_code']} (id={$extraPlanId})" . PHP_EOL;
} else {
    $extraPlanId = $plan->id;
    echo "[skip] plan {$extraPlan['plan_code']} (id={$extraPlanId})" . PHP_EOL;
}

$extraPlanRow = $plans->findById($extraPlanId);

// ---------------------------------------------------------------------------
// 4. Add members to the new plan and create contribution schedules.
// ---------------------------------------------------------------------------
foreach ($allMemberIds as $mid) {
    $existing = $pms->findByPlanAndMember($extraPlanId, $mid);
    if ($existing === null) {
        $pms->create([
            'plan_id'   => $extraPlanId,
            'member_id' => $mid,
            'status'    => 'active',
            'joined_at' => date('Y-m-d H:i:s'),
            'approved_by' => 1,
        ]);
    }
}

if ($extraPlanRow !== null) {
    $intervalSpec = 'P1M';
    $interval = new DateInterval($intervalSpec);
    foreach ($allMemberIds as $mid) {
        $existing = $sched->allForMember($mid);
        $hasForPlan = false;
        foreach ($existing as $cs) {
            if ($cs->planId === $extraPlanId) {
                $hasForPlan = true;
                break;
            }
        }
        if ($hasForPlan) {
            continue;
        }

        for ($cycle = 1; $cycle <= $extraPlanRow->numberOfCycles; $cycle++) {
            $dueDate = new DateTimeImmutable($extraPlanRow->startDate);
            for ($i = 0; $i < $cycle - 1; $i++) {
                $dueDate = $dueDate->add($interval);
            }
            $due = $dueDate->format('Y-m-d');
            $status = 'pending';
            if ($cycle === 1 && $due <= date('Y-m-d')) {
                $status = 'paid';
                $sched->create([
                    'plan_id'        => $extraPlanId,
                    'plan_cycle_id'  => null,
                    'member_id'      => $mid,
                    'due_date'       => $due,
                    'amount'         => $extraPlanRow->contributionAmount,
                    'amount_paid'    => $extraPlanRow->contributionAmount,
                    'status'         => $status,
                ]);
            } else {
                $sched->create([
                    'plan_id'        => $extraPlanId,
                    'plan_cycle_id'  => null,
                    'member_id'      => $mid,
                    'due_date'       => $due,
                    'amount'         => $extraPlanRow->contributionAmount,
                    'amount_paid'    => '0.00',
                    'status'         => $status,
                ]);
            }
        }
    }
    echo "[ins] contribution schedules for plan {$extraPlanId}" . PHP_EOL;
}

// ---------------------------------------------------------------------------
// 5. Payout schedules — for every plan/member, with payout_date spread
//    across today + next 7 days so the admin dashboard has something to
//    display.
// ---------------------------------------------------------------------------
$todayTs = strtotime(date('Y-m-d'));
$planIds = [(int) ($extraPlanId)];
foreach ($plans->all(null, null) as $p) {
    if (!in_array($p->id, $planIds, true)) {
        $planIds[] = $p->id;
    }
}

$inserted = 0;
foreach ($planIds as $planId) {
    $planRow = $plans->findById($planId);
    if ($planRow === null) {
        continue;
    }
    $membersOfPlan = $pdo->prepare(
        'SELECT member_id FROM plan_members WHERE plan_id = :p AND status = :s ORDER BY member_id ASC'
    );
    $membersOfPlan->execute([':p' => $planId, ':s' => 'active']);
    $memberList = array_map(static fn ($r) => (int) $r['member_id'], $membersOfPlan->fetchAll(\PDO::FETCH_ASSOC));

    // Skip if payouts already exist for this plan (idempotent).
    $existingPayout = (int) $pdo->query("SELECT COUNT(*) FROM payout_schedules WHERE plan_id = {$planId}")->fetchColumn();
    if ($existingPayout > 0) {
        continue;
    }

    $cycleLength = max(1, $planRow->numberOfCycles);
    foreach ($memberList as $i => $recipientId) {
        // Spread recipient payout dates: first member today, second tomorrow, ...
        $offsetDays = $i; // 0 = today, 1 = tomorrow...
        $payoutDate = date('Y-m-d', strtotime("+{$offsetDays} days", $todayTs));

        // expected_amount = contribution_amount * (number_of_members/cycles...)
        // For simplicity here we use fixed_payout_amount from the plan row.
        $expectedAmount = (string) ($planRow->fixedPayoutAmount ?? '0');

        $payouts->create([
            'plan_id'              => $planId,
            'plan_cycle_id'        => null,
            'recipient_member_id'  => $recipientId,
            'payout_date'          => $payoutDate,
            'expected_amount'      => $expectedAmount,
            'status'               => $offsetDays === 0 ? 'due' : 'scheduled',
        ]);
        $inserted++;
    }
}
echo "[ins] payout schedules: {$inserted}" . PHP_EOL;

// ---------------------------------------------------------------------------
// 6. Notifications — give each new member a welcome + first due reminder.
// ---------------------------------------------------------------------------
$firstNew = $memberIds ? array_key_first($memberIds) : null;
if ($firstNew !== null) {
    $firstUserId = $memberIds[$firstNew];
    $exists = (int) $pdo->prepare(
        'SELECT COUNT(*) FROM notifications WHERE recipient_id = :r AND type = :t'
    );
    $check = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE recipient_id = :r AND type = :t');
    $check->execute([':r' => $firstUserId, ':t' => 'plan.joined']);
    if ((int) $check->fetchColumn() === 0) {
        $pdo->prepare(
            'INSERT INTO notifications (recipient_id, type, title, message, channel, is_read, created_at)
             VALUES (:r, :t, :ti, :m, "in_app", 0, NOW())'
        )->execute([
            ':r'  => $firstUserId,
            ':t'  => 'plan.joined',
            ':ti' => 'Selamat Datang ke Pelan Premium',
            ':m'  => 'Anda telah didaftarkan ke Pelan Kutu Premium Tahunan. Sila buat caruman pertama sebelum 7 hari.',
        ]);
    }
    echo "[ins] welcome notifications" . PHP_EOL;
}

echo PHP_EOL . "Extended demo seed complete." . PHP_EOL;
echo "Additional member login (password = Name@12345):" . PHP_EOL;
foreach ($extraPeople as $p) {
    echo "  - {$p['email']} / {$p['password']}" . PHP_EOL;
}
