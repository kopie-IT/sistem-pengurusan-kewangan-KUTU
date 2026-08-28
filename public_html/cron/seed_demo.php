<?php

declare(strict_types=1);

/**
 * Demo data seeder.
 *
 * Creates a realistic set of sample records so the application can be
 * explored: several member users with profiles, two Plans, memberships,
 * contribution schedules, credit-score history and in-app notifications.
 *
 * Idempotent — safe to re-run.
 *
 * Usage: php cron/seed_demo.php
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

    // Fallback: case-insensitive directory (e.g. app/models vs app/Models)
    $lower = APP_ROOT . '/app/' . strtolower(preg_replace('/\\\\[^\\\\]+$/', '', $relative)) . '/' . substr(strrchr($relative, '\\'), 1) . '.php';
    if (file_exists($lower)) {
        require $lower;
    }
});

\App\Config\Config::load();

try {
    $pdo = \App\Core\Database::connection();
} catch (\Throwable $e) {
    fwrite(STDERR, "[ERROR] DB: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

$members = new \App\Repositories\MemberRepository();
$plans   = new \App\Repositories\PlanRepository();
$pms     = new \App\Repositories\PlanMemberRepository();
$sched   = new \App\Repositories\ContributionScheduleRepository();
$credit  = new \App\Repositories\CreditScoreRepository();
$notif   = new \App\Repositories\NotificationRepository();

// Seed default credit score rules (idempotent).
$credit->seedDefaultRules();

// ---------------------------------------------------------------------------
// 1. Demo member users (4 total).
//    Each entry: email, password, name, phone, ic, address, starting score.
// ---------------------------------------------------------------------------
$people = [
    [
        'email'    => 'ahmad@mainkutu.local',
        'password' => 'Ahmad@12345',
        'name'     => 'Ahmad bin Ali',
        'phone'    => '0123456789',
        'ic'       => '901234-56-7890',
        'address'  => 'No. 12, Jalan Merdeka, 50000 Kuala Lumpur',
        'score'    => 95,
    ],
    [
        'email'    => 'siti@mainkutu.local',
        'password' => 'Siti@12345',
        'name'     => 'Siti binti Rahman',
        'phone'    => '0134567890',
        'ic'       => '920101-14-1234',
        'address'  => 'No. 3, Jalan Damai, 52000 Kuala Lumpur',
        'score'    => 88,
    ],
    [
        'email'    => 'roslan@mainkutu.local',
        'password' => 'Roslan@12345',
        'name'     => 'Roslan bin Kamal',
        'phone'    => '0145678901',
        'ic'       => '880304-10-5678',
        'address'  => 'No. 7, Jalan Setia, 47000 Petaling Jaya',
        'score'    => 72,
    ],
    [
        'email'    => 'maya@mainkutu.local',
        'password' => 'Maya@12345',
        'name'     => 'Maya binti Ishak',
        'phone'    => '0167890123',
        'ic'       => '950512-08-2468',
        'address'  => 'No. 21, Jalan Harmoni, 50400 Kuala Lumpur',
        'score'    => 98,
    ],
];

$roleId = (int) $pdo->query("SELECT id FROM roles WHERE slug = 'member' LIMIT 1")->fetchColumn();
if ($roleId === 0) {
    $roleId = 2;
}

$memberIds = []; // member_id => user_id

foreach ($people as $i => $p) {
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
        echo "[ins] member user {$p['email']} / {$p['password']} (id={$userId})" . PHP_EOL;
    } else {
        echo "[skip] member user {$p['email']} (id={$userId})" . PHP_EOL;
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
// 2. Two demo Plans.
// ---------------------------------------------------------------------------
$plansDef = [
    [
        'plan_code'           => 'PLN-DEMO01',
        'name'                => 'Pelan Caruman Bulanan Setia',
        'description'         => 'Pelan caruman bulanan RM200 selama 5 kitaran untuk komuniti setempat.',
        'number_of_members'   => 5,
        'contribution_amount' => '200.00',
        'payment_frequency'   => 'monthly',
        'number_of_cycles'    => 5,
        'start_date'          => date('Y-m-01', strtotime('-2 months')),
        'end_date'            => date('Y-m-d', strtotime('+3 months')),
        'status'              => 'active',
        'max_members'         => 10,
        'min_credit_score'    => 60,
        'approval_required'   => 0,
        'allow_multiple'      => 1,
        'withdrawal_allowed'  => 1,
        'payout_mode'         => 'fixed',
        'fixed_payout_amount' => '1000.00',
        'payout_frequency'    => 'monthly',
        'payout_day'          => 1,
        'min_score'           => 0,
        'created_by'          => 1,
    ],
    [
        'plan_code'           => 'PLN-DEMO02',
        'name'                => 'Pelan Kutu Mingguan Runcit',
        'description'         => 'Pelan mingguan RM50 selama 12 kitaran — sesuai untuk simpanan jangka pendek.',
        'number_of_members'   => 4,
        'contribution_amount' => '50.00',
        'payment_frequency'   => 'weekly',
        'number_of_cycles'    => 12,
        'start_date'          => date('Y-m-d', strtotime('-1 week')),
        'end_date'            => date('Y-m-d', strtotime('+11 weeks')),
        'status'              => 'open',
        'max_members'         => 8,
        'min_credit_score'    => 0,
        'approval_required'   => 0,
        'allow_multiple'      => 1,
        'withdrawal_allowed'  => 1,
        'payout_mode'         => 'fixed',
        'fixed_payout_amount' => '600.00',
        'payout_frequency'    => 'weekly',
        'payout_day'          => 7,
        'min_score'           => 0,
        'created_by'          => 1,
    ],
];

$planIds = [];
foreach ($plansDef as $def) {
    $plan = null;
    foreach ($plans->all(null, null) as $p) {
        if ($p->planCode === $def['plan_code']) {
            $plan = $p;
            break;
        }
    }
    if ($plan === null) {
        $planId = $plans->create($def);
        echo "[ins] plan {$def['plan_code']} (id={$planId})" . PHP_EOL;
    } else {
        $planId = $plan->id;
        echo "[skip] plan {$def['plan_code']} (id={$planId})" . PHP_EOL;
    }
    $planIds[] = $planId;
}

// ---------------------------------------------------------------------------
// 3. Memberships (every demo member active in every plan).
// ---------------------------------------------------------------------------
foreach ($planIds as $planId) {
    foreach (array_keys($memberIds) as $memberId) {
        $existing = $pms->findByPlanAndMember($planId, $memberId);
        if ($existing === null) {
            $pms->create([
                'plan_id'     => $planId,
                'member_id'   => $memberId,
                'status'      => 'active',
                'joined_at'   => date('Y-m-d H:i:s'),
                'approved_by' => 1,
            ]);
            echo "[ins] membership (plan {$planId}, member {$memberId})" . PHP_EOL;
        }
    }
}

// ---------------------------------------------------------------------------
// 4. Contribution schedules per plan/member.
//    For each plan, derive due dates from its start date + frequency.
// ---------------------------------------------------------------------------
$freqToInterval = [
    'monthly' => 'P1M',
    'weekly'  => 'P7D',
    'biweekly' => 'P14D',
    'quarterly' => 'P3M',
];

foreach ($planIds as $planId) {
    $plan = $plans->findById($planId);
    if ($plan === null) {
        continue;
    }
    $interval = new DateInterval($freqToInterval[$plan->paymentFrequency] ?? 'P1M');

    foreach (array_keys($memberIds) as $memberId) {
        $existing = $sched->allForMember($memberId);
        $hasForPlan = false;
        foreach ($existing as $cs) {
            if ($cs->planId === $planId) {
                $hasForPlan = true;
                break;
            }
        }
        if ($hasForPlan) {
            continue;
        }

        for ($cycle = 1; $cycle <= $plan->numberOfCycles; $cycle++) {
            // Add the frequency interval (cycle - 1) times from the plan start.
            $dueDate = new DateTimeImmutable($plan->startDate);
            for ($i = 0; $i < $cycle - 1; $i++) {
                $dueDate = $dueDate->add($interval);
            }
            $due = $dueDate->format('Y-m-d');
            $status = 'pending';
            // First cycle is already due (started in the past) — mark some paid
            // for realism depending on the member index.
            if ($cycle === 1 && $due <= date('Y-m-d')) {
                $status = 'paid';
                $sched->create([
                    'plan_id'        => $planId,
                    'plan_cycle_id'  => null,
                    'member_id'      => $memberId,
                    'due_date'       => $due,
                    'amount'          => $plan->contributionAmount,
                    'amount_paid'     => $plan->contributionAmount,
                    'status'          => $status,
                ]);
            } else {
                $sched->create([
                    'plan_id'        => $planId,
                    'plan_cycle_id'  => null,
                    'member_id'      => $memberId,
                    'due_date'       => $due,
                    'amount'          => $plan->contributionAmount,
                    'amount_paid'     => '0.00',
                    'status'          => $status,
                ]);
            }
        }
        echo "[ins] schedules for plan {$planId}, member {$memberId}" . PHP_EOL;
    }
}

// ---------------------------------------------------------------------------
// 5. Credit-score history events for realism.
// ---------------------------------------------------------------------------
$scoreEvents = [
    ['member' => 'ahmad@mainkutu.local', 'reason' => 'PAYMENT_ON_TIME', 'desc' => 'Paid on time', 'chg' => 2],
    ['member' => 'siti@mainkutu.local',   'reason' => 'PAYMENT_LATE',    'desc' => 'Paid late',    'chg' => -5],
    ['member' => 'roslan@mainkutu.local', 'reason' => 'PAYMENT_MISSED',  'desc' => 'Missed payment','chg' => -10],
    ['member' => 'maya@mainkutu.local',   'reason' => 'PLAN_COMPLETED',  'desc' => 'Completed a plan', 'chg' => 5],
];
foreach ($scoreEvents as $ev) {
    // find member id by email
    $stmt = $pdo->prepare('SELECT m.id FROM members m JOIN users u ON u.id = m.user_id WHERE u.email = :e LIMIT 1');
    $stmt->execute([':e' => $ev['member']]);
    $mid = (int) $stmt->fetchColumn();
    if ($mid === 0) {
        continue;
    }
    $rule = $credit->ruleByCode($ev['reason']);
    if ($rule === null) {
        continue;
    }
    $current = $credit->findByMember($mid);
    $prev = $current !== null ? $current->score : 100;
    $newScore = max(0, min(100, $prev + (int) $rule['score_change']));
    $credit->addHistory([
        'member_id'      => $mid,
        'event'          => $ev['desc'],
        'reason_code'    => $ev['reason'],
        'previous_score' => $prev,
        'score_change'   => (int) $rule['score_change'],
        'new_score'      => $newScore,
        'actor_id'       => 1,
    ]);
    $credit->upsert($mid, $newScore, \App\Services\CreditScoreService::levelFor($newScore));
    echo "[ins] credit event {$ev['reason']} -> member {$mid}" . PHP_EOL;
}

// ---------------------------------------------------------------------------
// 6. A few notifications so the badge/feed is populated.
// ---------------------------------------------------------------------------
$firstMemberId = array_key_first($memberIds);
$firstUserId = $memberIds[$firstMemberId] ?? null;

if ($firstUserId !== null && $notif->countUnread($firstUserId) === 0) {
    $notif->create([
        'recipient_id'   => $firstUserId,
        'type'           => 'plan.joined',
        'title'          => 'Selamat Datang ke Pelan Caruman Bulanan Setia',
        'message'        => 'Anda telah berjaya menyertai pelan ini. Sila bayar caruman pertama sebelum tarikh matang.',
        'reference_type' => 'plan',
        'reference_id'   => $planIds[0] ?? null,
        'channel'        => 'in_app',
        'is_read'        => false,
    ]);
    $notif->create([
        'recipient_id'   => $firstUserId,
        'type'           => 'payment.reminder',
        'title'          => 'Peringatan Pembayaran',
        'message'        => 'Caruman anda yang akan datang tidak lama lagi. Pastikan baki mencukupi.',
        'reference_type' => 'plan',
        'reference_id'   => $planIds[0] ?? null,
        'channel'        => 'in_app',
        'is_read'        => false,
    ]);
    echo "[ins] sample notifications" . PHP_EOL;
}

echo PHP_EOL . "Demo seed complete." . PHP_EOL;
echo "Demo members (password = e.g. Ahmad@12345 / Siti@12345 / Roslan@12345 / Maya@12345):" . PHP_EOL;
foreach ($people as $p) {
    echo "  - {$p['email']} / {$p['password']}" . PHP_EOL;
}
