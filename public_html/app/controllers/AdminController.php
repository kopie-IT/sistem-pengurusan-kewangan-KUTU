<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\PlanService;

final class AdminController extends Controller
{
    public function __construct(
        private PlanService $plans,
    ) {}

    public function index(): void
    {
        $stats = $this->plans->getStats();
        $pdo = Database::connection();

        $stats['pending_verification'] = (int) $pdo->query(
            "SELECT COUNT(*) FROM payment_batches WHERE status IN ('submitted', 'pending_verification')"
        )->fetchColumn();
        $stats['pending_withdrawals'] = (int) $pdo->query(
            "SELECT COUNT(*) FROM withdrawal_requests WHERE status = 'pending'"
        )->fetchColumn();
        $stats['shortfall_count'] = (int) $pdo->query(
            "SELECT COUNT(*) FROM shortfalls WHERE status = 'open'"
        )->fetchColumn();
        $stats['low_score_members'] = (int) $pdo->query(
            'SELECT COUNT(*) FROM credit_scores WHERE score <= 60'
        )->fetchColumn();

        // ----- Today's payout calendar (giliran dapat kutu) ---------------
        // Pull every payout_schedules row whose payout_date is today OR due
        // in the next 7 days, joined with member + plan info so the admin
        // can see who is being paid and how much. Used to populate the
        // "Kalendar Payout" card on the admin dashboard.
        $today = date('Y-m-d');
        $week  = date('Y-m-d', strtotime('+7 days'));
        $stmt = $pdo->prepare(
            "SELECT ps.id, ps.plan_id, ps.recipient_member_id, ps.payout_date,
                    ps.expected_amount, ps.status,
                    p.name AS plan_name, p.plan_code,
                    m.member_code,
                    u.name AS recipient_name, u.email AS recipient_email
             FROM payout_schedules ps
             INNER JOIN plans p ON p.id = ps.plan_id
             INNER JOIN members m ON m.id = ps.recipient_member_id
             INNER JOIN users u ON u.id = m.user_id
             WHERE ps.payout_date BETWEEN :from AND :to
               AND ps.status IN ('scheduled', 'due', 'processing')
             ORDER BY ps.payout_date ASC, ps.expected_amount DESC"
        );
        $stmt->execute([':from' => $today, ':to' => $week]);
        $upcomingPayouts = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $todayDue   = [];
        $comingWeek = [];
        foreach ($upcomingPayouts as $row) {
            if (($row['payout_date'] ?? '') === $today) {
                $todayDue[] = $row;
            } else {
                $comingWeek[] = $row;
            }
        }

        $this->view('admin/index', [
            'title'        => 'Dashboard Pentadbir',
            'stats'        => $stats,
            'todayDue'     => $todayDue,
            'comingWeek'   => $comingWeek,
            'todayLabel'   => date('d M Y'),
        ]);
    }
}
