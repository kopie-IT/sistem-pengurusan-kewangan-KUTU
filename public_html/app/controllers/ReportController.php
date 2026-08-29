<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Repositories\CreditScoreRepository;
use App\Repositories\LedgerRepository;
use App\Repositories\MemberRepository;
use App\Repositories\PlanRepository;
final class ReportController extends Controller
{
    public function __construct(
        private LedgerRepository $ledger,
        private MemberRepository $members,
        private PlanRepository $planRepo,
        private CreditScoreRepository $creditRepo,
    ) {}

    /** Legacy report root redirects to the primary financial report. */
    public function dashboard(): void
    {
        $this->redirect(url('/admin/reports/financial'));
    }

    /** Financial ledger summary. */
    public function financial(): void
    {
        $typeFilter = trim((string) ($_GET['type'] ?? ''));
        $dateFilter = trim((string) ($_GET['date'] ?? ''));
        $planFilter = (int) ($_GET['plan'] ?? 0);

        $pdo = Database::connection();
        $sql = 'SELECT lt.id, lt.transaction_type, lt.member_id, lt.plan_id, lt.reference_id,
                       lt.amount, lt.currency, lt.description, lt.created_at,
                       u.name AS member_name, m.member_code,
                       p.name AS plan_name, p.plan_code
                FROM ledger_transactions lt
                LEFT JOIN members m ON m.id = lt.member_id
                LEFT JOIN users u ON u.id = m.user_id
                LEFT JOIN plans p ON p.id = lt.plan_id
                WHERE 1=1';
        $params = [];
        if ($typeFilter !== '') {
            $sql .= ' AND lt.transaction_type = :type';
            $params[':type'] = $typeFilter;
        }
        if ($dateFilter !== '') {
            $sql .= ' AND DATE(lt.created_at) = :date';
            $params[':date'] = $dateFilter;
        }
        if ($planFilter > 0) {
            $sql .= ' AND lt.plan_id = :plan';
            $params[':plan'] = $planFilter;
        }
        $sql .= ' ORDER BY lt.id DESC LIMIT 500';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $ledger = $stmt->fetchAll(PDO::FETCH_OBJ);

        $summarySql = 'SELECT
                COALESCE(SUM(CASE WHEN transaction_type IN ("contribution","payment") THEN amount ELSE 0 END),0) AS total_in,
                COALESCE(SUM(CASE WHEN transaction_type = "payout" THEN amount ELSE 0 END),0) AS total_out,
                COALESCE(SUM(CASE WHEN transaction_type = "admin_fee" THEN amount ELSE 0 END),0) AS total_fee,
                COALESCE(SUM(CASE WHEN transaction_type = "shortfall" THEN amount ELSE 0 END),0) AS total_shortfall,
                COUNT(*) AS total_count
            FROM ledger_transactions';
        $summaryRow = $pdo->query($summarySql)->fetch(PDO::FETCH_ASSOC) ?: [];

        $byTypeSql = 'SELECT transaction_type, COUNT(*) AS cnt, COALESCE(SUM(amount),0) AS total
            FROM ledger_transactions GROUP BY transaction_type ORDER BY total DESC';
        $byType = $pdo->query($byTypeSql)->fetchAll(PDO::FETCH_ASSOC);

        $plans = $this->planRepo->all();

        $this->view('reports/financial', [
            'title'      => 'Laporan Kewangan',
            'summary'    => $summaryRow,
            'byType'     => $byType,
            'ledger'     => $ledger,
            'plans'      => $plans,
            'typeFilter' => $typeFilter !== '' ? $typeFilter : null,
            'dateFilter' => $dateFilter !== '' ? $dateFilter : null,
            'planFilter' => $planFilter,
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
