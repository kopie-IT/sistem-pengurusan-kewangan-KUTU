<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuthService;
use App\Services\NotificationService;

final class NotificationController extends Controller
{
    public function __construct(
        private AuthService $auth,
        private NotificationService $notifications,
    ) {}

    public function index(): void
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $items = $this->notifications->all($userId);

        $this->view('notifications/index', [
            'title' => 'Pemberitahuan',
            'items' => $items,
        ]);
    }

    public function markRead(int $id): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
            set_flash('error', 'Sesi tidak sah. Sila cuba lagi.');
            $this->redirect('/notifications');
        }

        $this->notifications->markRead($id);
        set_flash('success', 'Pemberitahuan ditanda sebagai dibaca.');
        $this->redirect('/notifications');
    }

    public function markAllRead(): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
            set_flash('error', 'Sesi tidak sah. Sila cuba lagi.');
            $this->redirect('/notifications');
        }

        $this->notifications->markAllRead((int) ($_SESSION['user_id'] ?? 0));
        set_flash('success', 'Semua pemberitahuan ditanda sebagai dibaca.');
        $this->redirect('/notifications');
    }
}
