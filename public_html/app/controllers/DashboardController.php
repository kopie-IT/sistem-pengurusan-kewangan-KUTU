<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuthService;

final class DashboardController extends Controller
{
    public function __construct(private AuthService $auth) {}

    public function index(): void
    {
        $user = $this->auth->currentUser();
        $this->view('dashboard/index', [
            'title' => 'Dashboard',
            'user'  => $user,
        ]);
    }
}
