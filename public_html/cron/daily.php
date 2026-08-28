<?php

declare(strict_types=1);

/**
 * Daily cron: overdue detection, notifications, and reconciliation.
 *
 * PRD §41 (Overdue Management) and §62 (Cron Jobs).
 *
 * 1. Marks contribution_schedules past due date as 'overdue'.
 * 2. Notifies affected members (payment.overdue).
 * 3. Flags upcoming payout schedules as 'due' when within 7 days.
 * 4. Records LATE_PAYMENT / MISSED_PAYMENT credit-score events.
 *
 * Usage (cPanel cron): php /home/USERNAME/app/cron/daily.php
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
    // Case-insensitive directory fallback (app/models vs app/Models).
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

$schedules  = new \App\Repositories\ContributionScheduleRepository();
$members    = new \App\Repositories\MemberRepository();
$payouts    = new \App\Repositories\PayoutScheduleRepository();
$notifications = new \App\Repositories\NotificationRepository();
$credit     = new \App\Repositories\CreditScoreRepository();

$today = date('Y-m-d');
$graceDays = (int) \App\Config\Config::getInstance()->get('OVERDUE_GRACE_DAYS', '0');

echo "[cron] daily run {$today}" . PHP_EOL;

// ---------------------------------------------------------------------------
// 1. Mark overdue contributions (past due date, still pending/partial).
// ---------------------------------------------------------------------------
$stmt = $pdo->prepare(
    "SELECT cs.id, cs.member_id, cs.plan_id, cs.due_date, cs.amount, cs.status,
            u.id AS user_id
     FROM contribution_schedules cs
     JOIN members m  ON m.id = cs.member_id
     JOIN users u    ON u.id = m.user_id
     WHERE cs.due_date < :today
       AND cs.status IN ('pending','partial')"
);
$stmt->execute([':today' => $today]);
$overdue = $stmt->fetchAll();

$count = 0;
foreach ($overdue as $row) {
    $daysLate = (int) floor((strtotime($today) - strtotime($row['due_date'])) / 86400);
    if ($daysLate <= $graceDays) {
        continue;
    }

    $schedules->updateStatus((int) $row['id'], 'overdue');

    // Notify the member once per overdue schedule.
    $notifications->create([
        'recipient_id'   => (int) $row['user_id'],
        'type'           => 'payment.overdue',
        'title'          => 'Caruman Tertunggak',
        'message'        => "Caruman anda telah melebihi tarikh matang ({$row['due_date']}). Sila bayar segera untuk mengelakkan penalti skor.",
        'reference_type' => 'contribution_schedule',
        'reference_id'   => (int) $row['id'],
        'channel'        => 'in_app',
        'is_read'        => false,
    ]);

    // Credit-score event: LATE_PAYMENT (-5) or MISSED_PAYMENT (-20) if > 30 days.
    $reasonCode = $daysLate > 30 ? 'MISSED_PAYMENT' : 'LATE_PAYMENT';
    $rule = $credit->ruleByCode($reasonCode);
    $change = $rule !== null ? (int) $rule['score_change'] : ($daysLate > 30 ? -20 : -5);

    $member = $members->findById((int) $row['member_id']);
    if ($member !== null) {
        $prev = (int) $member->creditScore;
        $new = max(0, min(100, $prev + $change));
        $credit->upsert((int) $row['member_id'], $new, \App\Services\CreditScoreService::levelFor($new));
        $credit->addHistory([
            'member_id'           => (int) $row['member_id'],
            'event'               => 'Late payment detected',
            'reason_code'         => $reasonCode,
            'previous_score'      => $prev,
            'score_change'        => $change,
            'new_score'           => $new,
            'related_plan_id'     => (int) $row['plan_id'],
            'related_payment_id'  => null,
            'related_payout_id'   => null,
            'actor_id'            => null,
        ]);
    }

    $count++;
}
echo "[cron] marked {$count} overdue schedule(s)" . PHP_EOL;

// ---------------------------------------------------------------------------
// 2. Flag upcoming payout schedules as 'due' within 7 days.
// ---------------------------------------------------------------------------
$stmt = $pdo->prepare(
    "SELECT id, plan_id, recipient_member_id, payout_date
     FROM payout_schedules
     WHERE status = 'scheduled'
       AND payout_date IS NOT NULL
       AND payout_date BETWEEN :today AND :week"
);
$stmt->execute([':today' => $today, ':week' => date('Y-m-d', strtotime('+7 days'))]);
foreach ($stmt->fetchAll() as $row) {
    $payouts->updateStatus((int) $row['id'], 'due');
}
echo "[cron] flagged upcoming payouts as due" . PHP_EOL;

// ---------------------------------------------------------------------------
// 3. Payment reminders: schedules due within 3 days, still pending.
// ---------------------------------------------------------------------------
$stmt = $pdo->prepare(
    "SELECT cs.id, cs.due_date, cs.amount, u.id AS user_id
     FROM contribution_schedules cs
     JOIN members m ON m.id = cs.member_id
     JOIN users u   ON u.id = m.user_id
     WHERE cs.status = 'pending'
       AND cs.due_date BETWEEN :today AND :soon"
);
$stmt->execute([':today' => $today, ':soon' => date('Y-m-d', strtotime('+3 days'))]);
$reminded = 0;
foreach ($stmt->fetchAll() as $row) {
    $notifications->create([
        'recipient_id'   => (int) $row['user_id'],
        'type'           => 'payment.reminder',
        'title'          => 'Peringatan Caruman',
        'message'        => "Caruman anda akan matang pada {$row['due_date']} (RM {$row['amount']}). Sila bayar tepat pada masanya.",
        'reference_type' => 'contribution_schedule',
        'reference_id'   => (int) $row['id'],
        'channel'        => 'in_app',
        'is_read'        => false,
    ]);
    $reminded++;
}
echo "[cron] sent {$reminded} payment reminder(s)" . PHP_EOL;

echo "[cron] done" . PHP_EOL;
