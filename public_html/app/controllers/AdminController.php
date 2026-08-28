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

        $this->view('admin/index', [
            'title' => 'Dashboard Pentadbir',
            'stats' => $stats,
        ]);
    }
}
