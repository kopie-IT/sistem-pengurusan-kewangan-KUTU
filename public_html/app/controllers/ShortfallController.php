<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuthService;
use App\Services\ShortfallService;

final class ShortfallController extends Controller
{
    public function __construct(
        private AuthService $auth,
        private ShortfallService $shortfalls,
    ) {}

    public function index(): void
    {
        $status = trim((string) ($_GET['status'] ?? ''));
        $items = $this->shortfalls->list($status !== '' ? $status : null);

        $this->view('shortfalls/index', [
            'title'  => 'Kekurangan Pembayaran',
            'items'  => $items,
            'status' => $status,
        ]);
    }

    public function resolve(int $id): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
            set_flash('error', 'Sesi tidak sah. Sila cuba lagi.');
            $this->redirect('/admin/shortfalls');
        }

        $resolution = trim((string) ($_POST['resolution'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));
        if ($resolution === '') {
            set_flash('error', 'Sila pilih resolusi.');
            $this->redirect('/admin/shortfalls');
        }

        $result = $this->shortfalls->resolve($id, $resolution, $notes !== '' ? $notes : null, (int) $_SESSION['user_id']);
        if (!$result['ok']) {
            set_flash('error', $result['error'] ?? 'Gagal menyelesaikan kekurangan.');
            $this->redirect('/admin/shortfalls');
        }

        set_flash('success', 'Kekurangan pembayaran diselesaikan.');
        $this->redirect('/admin/shortfalls');
    }
}
