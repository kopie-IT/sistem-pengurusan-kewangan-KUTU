<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Repositories\CreditScoreRepository;
use App\Repositories\LedgerRepository;
use App\Repositories\MemberRepository;
use App\Repositories\PlanRepository;
use App\Repositories\ShortfallRepository;
use App\Services\AuthService;
use App\Services\PlanService;
use PDO;

final class ReportController extends Controller
{
    public function __construct(
        private AuthService $auth,
        private PlanService $plans,
        private LedgerRepository $ledger,
        private MemberRepository $members,
        private PlanRepository $planRepo,
        private CreditScoreRepository $creditRepo,
        private ShortfallRepository $shortfalls,
    ) {}

    /** Admin dashboard with rich widgets. */
    public function dashboard(): void
    {
        $stats = $this->plans->getStats();

        $pendingVerification = (int) Database::connection()
            ->query("SELECT COUNT(*) FROM payment_batches WHERE status IN ('submitted','pending_verification')")
            ->fetchColumn();

        $stats['pending_verification'] = $pendingVerification;
        $stats['shortfall_open'] = (int) Database::connection()
            ->query("SELECT COUNT(*) FROM shortfalls WHERE status = 'open'")
            ->fetchColumn();

        // Low-score members (<= 60).
        $lowScores = [];
        $pdo = Database::connection();
        $stmt = $pdo->query('SELECT cs.member_id, cs.score, cs.level, u.name AS name
                            FROM credit_scores cs
                            LEFT JOIN members m ON m.id = cs.member_id
                            LEFT JOIN users u ON u.id = m.user_id
                            WHERE cs.score <= 60
                            ORDER BY cs.score ASC LIMIT 20');
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $lowScores[] = $row;
        }

        $this->view('reports/dashboard', [
            'title'     => 'Papan Pemuka Pentadbir',
            'stats'     => $stats,
            'lowScores' => $lowScores,
        ]);
    }

    /** Financial ledger summary. */
    public function financial(): void
    {
        $summary = $this->ledger->balanceSummary();
        $recent = $this->ledger->recent(50);

        $this->view('reports/financial', [
            'title'   => 'Laporan Kewangan',
            'summary' => $summary,
            'recent'  => $recent,
        ]);
    }

    /** Plan performance report. */
    public function plans(): void
    {
        $plans = $this->planRepo->all();

        $pdo = Database::connection();
        $planStats = [];
        foreach ($plans as $plan) {
            $members = (int) $pdo->query(
                "SELECT COUNT(*) FROM plan_members WHERE plan_id = {$plan->id}"
            )->fetchColumn();
            $collected = (string) ($pdo->query(
                "SELECT COALESCE(SUM(amount),0) FROM ledger_transactions
                 WHERE plan_id = {$plan->id} AND transaction_type = 'contribution'"
            )->fetchColumn() ?? '0.00');
            $paid = (string) ($pdo->query(
                "SELECT COALESCE(SUM(net_payout),0) FROM payouts WHERE plan_id = {$plan->id}"
            )->fetchColumn() ?? '0.00');
            $planStats[$plan->id] = [
                'members'   => $members,
                'collected' => $collected,
                'paid'      => $paid,
            ];
        }

        $this->view('reports/plans', [
            'title'     => 'Laporan Pelan',
            'plans'     => $plans,
            'planStats' => $planStats,
        ]);
    }

    /** Member report with scores. */
    public function members(): void
    {
        $search = trim((string) ($_GET['search'] ?? ''));
        $memberList = $this->members->all(100, 0, $search !== '' ? $search : null);

        $rows = [];
        foreach ($memberList as $member) {
            $score = $this->creditRepo->findByMember($member->id);
            $rows[] = [
                'member' => $member,
                'score'  => $score !== null ? $score->score : 100,
                'level'  => $score !== null ? $score->level : 'excellent',
            ];
        }

        $this->view('reports/members', [
            'title'  => 'Laporan Ahli',
            'rows'   => $rows,
            'search' => $search,
        ]);
    }

    /** Export a report as CSV. */
    public function exportCsv(): void
    {
        $type = trim((string) ($_GET['type'] ?? 'financial'));

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="laporan-' . e($type) . '-' . date('Ymd') . '.csv"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        $out = fopen('php://output', 'w');
        if ($out === false) {
            exit;
        }

        fprintf($out, "\xEF\xBB\xBF");

        if ($type === 'members') {
            fputcsv($out, ['ID', 'Nama', 'Emel', 'Kod Ahli', 'Skor Kredit', 'Tahap', 'Status']);
            $memberList = $this->members->all(1000, 0);
            foreach ($memberList as $member) {
                $score = $this->creditRepo->findByMember($member->id);
                fputcsv($out, [
                    $member->id,
                    $member->name,
                    $member->email,
                    $member->memberCode,
                    $score !== null ? $score->score : 100,
                    $score !== null ? $score->level : 'excellent',
                    $member->status,
                ]);
            }
        } elseif ($type === 'plans') {
            fputcsv($out, ['ID', 'Kod Pelan', 'Nama', 'Status', 'Jumlah Caruman', 'Kitaran', 'Ahli']);
            $plans = $this->planRepo->all();
            $pdo = Database::connection();
            foreach ($plans as $plan) {
                $members = (int) $pdo->query("SELECT COUNT(*) FROM plan_members WHERE plan_id = {$plan->id}")->fetchColumn();
                fputcsv($out, [
                    $plan->id,
                    $plan->planCode,
                    $plan->name,
                    $plan->status,
                    $plan->contributionAmount,
                    $plan->numberOfCycles,
                    $members,
                ]);
            }
        } else {
            fputcsv($out, ['ID', 'Jenis', 'Ahli ID', 'Pelan ID', 'Rujukan', 'Jumlah', 'Mata Wang', 'Keterangan', 'Dicipta']);
            $recent = $this->ledger->recent(1000);
            foreach ($recent as $row) {
                fputcsv($out, [
                    $row['id'],
                    $row['transaction_type'],
                    $row['member_id'],
                    $row['plan_id'],
                    $row['reference_id'],
                    $row['amount'],
                    $row['currency'],
                    $row['description'],
                    $row['created_at'],
                ]);
            }
        }

        fclose($out);
        exit;
    }
}
