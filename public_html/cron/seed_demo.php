<?php

declare(strict_types=1);

/**
 * Demo data seeder.
 *
 * Creates: a member user with profile, an open Plan, membership, and a few
 * contribution schedules. Idempotent — safe to re-run.
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

// Member repository / plan service wiring
$members = new \App\Repositories\MemberRepository();
$plans   = new \App\Repositories\PlanRepository();
$pms     = new \App\Repositories\PlanMemberRepository();
$sched   = new \App\Repositories\ContributionScheduleRepository();
$credit  = new \App\Repositories\CreditScoreRepository();

// Seed default credit score rules (idempotent).
$credit->seedDefaultRules();

// ---------------------------------------------------------------------------
// 1. Ensure a demo member user exists (different from seeded member@mainkutu).
// ---------------------------------------------------------------------------
$email = 'ahmad@mainkutu.local';
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = :e LIMIT 1');
$stmt->execute([':e' => $email]);
$userId = (int) $stmt->fetchColumn();

if ($userId === 0) {
    $roleId = (int) $pdo->query("SELECT id FROM roles WHERE slug = 'member' LIMIT 1")->fetchColumn();
    if ($roleId === 0) {
        $roleId = 2;
    }
    $hash = password_hash('Ahmad@12345', PASSWORD_BCRYPT);
    $pdo->prepare(
        'INSERT INTO users (name, email, password, role_id, status, must_reset_password, created_at, updated_at)
         VALUES (:n, :e, :p, :r, :s, 0, NOW(), NOW())'
    )->execute([
        ':n' => 'Ahmad bin Ali',
        ':e' => $email,
        ':p' => $hash,
        ':r' => $roleId,
        ':s' => 'active',
    ]);
    $userId = (int) $pdo->lastInsertId();
    echo "[ins] demo member user ahmad@mainkutu.local / Ahmad@12345 (id={$userId})" . PHP_EOL;
} else {
    echo "[skip] demo member user already exists (id={$userId})" . PHP_EOL;
}

// Member profile row.
$member = $members->findByUserId($userId);
if ($member === null) {
    $memberId = $members->create([
        'user_id'      => $userId,
        'member_code'  => 'M' . str_pad((string) $userId, 5, '0', STR_PAD_LEFT),
        'phone'        => '0123456789',
        'ic_number'    => '901234-56-7890',
        'address'      => 'No. 12, Jalan Merdeka, 50000 Kuala Lumpur',
        'credit_score' => 95,
        'status'       => 'active',
    ]);
    $credit->upsert($memberId, 95, 'excellent');
    echo "[ins] member profile (id={$memberId})" . PHP_EOL;
} else {
    $memberId = $member->id;
    echo "[skip] member profile already exists (id={$memberId})" . PHP_EOL;
}

// ---------------------------------------------------------------------------
// 2. Ensure a demo Plan exists.
// ---------------------------------------------------------------------------
$planCode = 'PLN-DEMO01';
$plan = null;
foreach ($plans->all(null, null) as $p) {
    if ($p->planCode === $planCode) {
        $plan = $p;
        break;
    }
}

if ($plan === null) {
    $planId = $plans->create([
        'plan_code'           => $planCode,
        'name'                => 'Pelan Caruman Bulanan Setia',
        'description'         => 'Pelan caruman bulanan RM200 selama 5 kitaran untuk komuniti setempat.',
        'number_of_members'   => 5,
        'contribution_amount' => '200.00',
        'payment_frequency'   => 'monthly',
        'number_of_cycles'    => 5,
        'start_date'          => date('Y-m-01'),
        'end_date'            => date('Y-m-d', strtotime('+5 months')),
        'status'              => 'open',
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
        'created_by'          => 1, // admin
    ]);
    echo "[ins] demo plan {$planCode} (id={$planId})" . PHP_EOL;
} else {
    $planId = $plan->id;
    echo "[skip] demo plan already exists (id={$planId})" . PHP_EOL;
}

// ---------------------------------------------------------------------------
// 3. Membership: enrol demo member into the plan as active.
// ---------------------------------------------------------------------------
$existing = $pms->findByPlanAndMember($planId, $memberId);
if ($existing === null) {
    $pms->create([
        'plan_id'    => $planId,
        'member_id'  => $memberId,
        'status'     => 'active',
        'joined_at'  => date('Y-m-d H:i:s'),
        'approved_by'=> 1,
    ]);
    echo "[ins] membership (plan {$planId}, member {$memberId})" . PHP_EOL;
} else {
    echo "[skip] membership already exists" . PHP_EOL;
}

// ---------------------------------------------------------------------------
// 4. Contribution schedules: 5 monthly dues starting start-of-month.
// ---------------------------------------------------------------------------
$existingSched = $sched->allForMember($memberId);
if (count($existingSched) === 0) {
    $base = new \DateTimeImmutable(date('Y-m-01'));
    for ($cycle = 1; $cycle <= 5; $cycle++) {
        $due = $base->modify('+' . ($cycle - 1) . ' months')->format('Y-m-d');
        $sched->create([
            'plan_id'        => $planId,
            'plan_cycle_id'  => null,
            'member_id'      => $memberId,
            'due_date'       => $due,
            'amount'          => '200.00',
            'amount_paid'     => '0.00',
            'status'          => $cycle === 1 ? 'pending' : 'pending',
        ]);
    }
    echo "[ins] 5 contribution schedules" . PHP_EOL;
} else {
    echo "[skip] contribution schedules already exist (" . count($existingSched) . ")" . PHP_EOL;
}

// ---------------------------------------------------------------------------
// 5. A sample in-app notification for the member.
// ---------------------------------------------------------------------------
$notifRepo = new \App\Repositories\NotificationRepository();
if ($notifRepo->countUnread($userId) === 0) {
    $notifRepo->create([
        'recipient_id'   => $userId,
        'type'           => 'plan.joined',
        'title'          => 'Selamat Datang ke Pelan Caruman Bulanan Setia',
        'message'        => 'Anda telah berjaya menyertai pelan ini. Sila bayar caruman pertama sebelum tarikh matang.',
        'reference_type' => 'plan',
        'reference_id'   => $planId,
        'channel'        => 'in_app',
        'is_read'        => false,
    ]);
    echo "[ins] welcome notification" . PHP_EOL;
} else {
    echo "[skip] notification already exists" . PHP_EOL;
}

echo PHP_EOL . "Demo seed complete." . PHP_EOL;
echo "Demo member: ahmad@mainkutu.local / Ahmad@12345" . PHP_EOL;
