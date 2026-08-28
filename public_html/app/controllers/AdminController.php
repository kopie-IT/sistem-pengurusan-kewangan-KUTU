<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuthService;

final class AdminController extends Controller
{
    public function __construct(private AuthService $auth) {}

    public function index(): void
    {
        $links = [
            ['label' => 'Papan Pemuka', 'desc' => 'Statistik & petunjuk penting.', 'url' => '/admin/reports/dashboard', 'icon' => 'grid'],
            ['label' => 'Pelan', 'desc' => 'Cipta & urus pelan simpanan.', 'url' => '/admin/plans', 'icon' => 'layers'],
            ['label' => 'Ahli', 'desc' => 'Senarai & profil ahli.', 'url' => '/admin/members', 'icon' => 'users'],
            ['label' => 'Pembayaran', 'desc' => 'Sahkan bayaran caruman.', 'url' => '/admin/payments', 'icon' => 'check'],
            ['label' => 'Pembayaran (Payout)', 'desc' => 'Jana & urus payout.', 'url' => '/admin/payouts', 'icon' => 'send'],
            ['label' => 'Kekurangan', 'desc' => 'Selesaikan kekurangan kutipan.', 'url' => '/admin/shortfalls', 'icon' => 'alert'],
            ['label' => 'Pengeluaran', 'desc' => 'Kelulusan pengeluaran ahli.', 'url' => '/admin/withdrawals', 'icon' => 'exit'],
            ['label' => 'Laporan', 'desc' => 'Laporan kewangan & prestasi.', 'url' => '/admin/reports/financial', 'icon' => 'chart'],
        ];

        $this->view('admin/index', [
            'title' => 'Pentadbir',
            'links' => $links,
        ]);
    }
}
