<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\MemberRepository;
use App\Repositories\PlanRepository;
use App\Services\AuthService;
use App\Services\MembershipService;
use App\Services\PlanService;

final class PlanController extends Controller
{
    public function __construct(
        private AuthService $auth,
        private PlanService $plans,
        private MembershipService $membership,
        private PlanRepository $planRepo,
        private MemberRepository $members,
    ) {}

    /** Member-facing: open / active plans the member could join. */
    public function index(): void
    {
        $member = $this->currentMember();
        $plans = $this->plans->list(['status' => 'open']);

        $memberships = [];
        if ($member !== null) {
            foreach ($this->membership->listForMember($member->id) as $pm) {
                $memberships[$pm->planId] = $pm->status;
            }
        }

        $this->view('plans/index', [
            'title'      => 'Pelan Tersedia',
            'plans'      => $plans,
            'memberships' => $memberships,
        ]);
    }

    /** Member-facing: plan detail + the member's membership status. */
    public function show(int $id): void
    {
        $plan = $this->plans->find($id);
        if ($plan === null) {
            set_flash('error', 'Pelan tidak dijumpai.');
            $this->redirect('/plans');
        }

        $member = $this->currentMember();
        $membership = null;
        if ($member !== null) {
            foreach ($this->membership->listForMember($member->id) as $pm) {
                if ($pm->planId === $id) {
                    $membership = $pm;
                    break;
                }
            }
        }

        $this->view('plans/show', [
            'title'         => e($plan->name),
            'plan'          => $plan,
            'alreadyMember' => $membership !== null,
            'memberStatus'  => $membership?->status ?? null,
        ]);
    }

    /** Member-facing: request to join a plan. */
    public function join(int $id): void
    {
        if (!$this->auth->isAuthenticated() || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), (string) ($_POST['csrf_token'] ?? ''))) {
            set_flash('error', 'Sesi tidak sah. Sila cuba lagi.');
            $this->redirect('/plans/' . $id);
        }

        $member = $this->currentMember();
        if ($member === null) {
            set_flash('error', 'Akaun ahli tidak dijumpai.');
            $this->redirect('/plans/' . $id);
        }

        $result = $this->membership->requestJoin($member->id, $id);
        if (!$result['ok']) {
            set_flash('error', $result['error'] ?? 'Gagal menyertai pelan.');
            $this->redirect('/plans/' . $id);
        }

        if (($result['status'] ?? '') === 'pending') {
            set_flash('info', 'Permintaan menyertai pelan telah dihantar untuk kelulusan.');
        } else {
            set_flash('success', 'Anda telah berjaya menyertai pelan.');
        }
        $this->redirect('/plans/' . $id);
    }

    // ----------------------------------------------------------------------
    // Admin section
    // ----------------------------------------------------------------------

    public function adminIndex(): void
    {
        $search = trim((string) ($_GET['search'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        $plans = $this->plans->list([
            'search' => $search !== '' ? $search : null,
            'status' => $status !== '' ? $status : null,
        ]);

        $this->view('plans/admin_index', [
            'title' => 'Urus Pelan',
            'plans' => $plans,
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function create(): void
    {
        $this->view('plans/form', [
            'title' => 'Cipta Pelan',
            'plan'  => null,
        ]);
    }

    public function store(): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
            set_flash('error', 'Sesi tidak sah. Sila cuba lagi.');
            $this->redirect('/admin/plans/create');
        }

        $data = $this->collectPlanInput();
        $errors = $this->validatePlanInput($data);
        if ($errors !== []) {
            $_SESSION['old'] = $data;
            set_flash('error', implode(' ', $errors));
            $this->redirect('/admin/plans/create');
        }

        $result = $this->plans->createPlan($data, (int) $_SESSION['user_id']);
        if (!$result['ok']) {
            set_flash('error', $result['error'] ?? 'Gagal mencipta pelan.');
            $this->redirect('/admin/plans/create');
        }

        set_flash('success', 'Pelan berjaya dicipta.');
        $this->redirect('/admin/plans');
    }

    public function edit(int $id): void
    {
        $plan = $this->plans->find($id);
        if ($plan === null) {
            set_flash('error', 'Pelan tidak dijumpai.');
            $this->redirect('/admin/plans');
        }

        $this->view('plans/form', [
            'title' => 'Kemaskini Pelan',
            'plan'  => $plan,
        ]);
    }

    public function update(int $id): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
            set_flash('error', 'Sesi tidak sah. Sila cuba lagi.');
            $this->redirect('/admin/plans/' . $id . '/edit');
        }

        $plan = $this->plans->find($id);
        if ($plan === null) {
            set_flash('error', 'Pelan tidak dijumpai.');
            $this->redirect('/admin/plans');
        }

        $data = $this->collectPlanInput();
        $errors = $this->validatePlanInput($data, true);
        if ($errors !== []) {
            $_SESSION['old'] = $data;
            set_flash('error', implode(' ', $errors));
            $this->redirect('/admin/plans/' . $id . '/edit');
        }

        $result = $this->plans->updatePlan($id, $data);
        if (!$result['ok']) {
            set_flash('error', $result['error'] ?? 'Gagal mengemaskini pelan.');
            $this->redirect('/admin/plans/' . $id . '/edit');
        }

        set_flash('success', 'Pelan berjaya dikemaskini.');
        $this->redirect('/admin/plans');
    }

    /**
     * Upload/replace/remove the QR code for a plan.
     *
     * Validates the same image constraints as the brand logo (PNG/JPG/SVG/WEBP,
     * ≤2 MB) and stores the file under `storage/uploads/brand/`. Persists the
     * stored filename on `plans.payment_qr_path`. When the plan has no QR the
     * public viewer falls back to the system-wide payment QR.
     */
    public function updateQr(int $id): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
            set_flash('error', 'Sesi tidak sah. Sila cuba lagi.');
            $this->redirect('/admin/plans/' . $id . '/edit');
        }

        $plan = $this->plans->find($id);
        if ($plan === null) {
            set_flash('error', 'Pelan tidak dijumpai.');
            $this->redirect('/admin/plans');
        }

        $pdo = \App\Core\Database::connection();
        $currentQr = (string) ($plan->paymentQrPath ?? '');

        if (isset($_POST['remove_qr']) && $currentQr !== '') {
            $this->deletePlanQrFile($currentQr);
            $pdo->prepare('UPDATE plans SET payment_qr_path = NULL, updated_at = NOW() WHERE id = :id')
                ->execute([':id' => $id]);
            set_flash('success', 'QR pelan telah dibuang.');
            $this->redirect('/admin/plans/' . $id . '/edit');
        }

        $file = $_FILES['payment_qr'] ?? null;
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            set_flash('warning', 'Tiada fail QR dipilih.');
            $this->redirect('/admin/plans/' . $id . '/edit');
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            set_flash('error', 'Fail QR tidak sah.');
            $this->redirect('/admin/plans/' . $id . '/edit');
        }
        if ((int) $file['size'] > 2 * 1024 * 1024) {
            set_flash('error', 'Saiz QR melebihi had 2 MB.');
            $this->redirect('/admin/plans/' . $id . '/edit');
        }

        $ext  = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        $mime = mime_content_type($file['tmp_name']) ?: '';
        $allowedExt  = ['png', 'jpg', 'jpeg', 'svg', 'webp'];
        $allowedMime = ['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp'];
        if (!in_array($ext, $allowedExt, true) || !in_array($mime, $allowedMime, true)) {
            set_flash('error', 'Jenis fail QR tidak dibenarkan (png, jpg, svg, webp).');
            $this->redirect('/admin/plans/' . $id . '/edit');
        }

        $dir = APP_ROOT . '/storage/uploads/brand/';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $stored = 'qr_plan' . $id . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $dir . $stored;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            set_flash('error', 'Gagal menyimpan QR.');
            $this->redirect('/admin/plans/' . $id . '/edit');
        }

        if ($currentQr !== '' && $currentQr !== $stored) {
            $this->deletePlanQrFile($currentQr);
        }

        $pdo->prepare('UPDATE plans SET payment_qr_path = :qr, updated_at = NOW() WHERE id = :id')
            ->execute([':qr' => $stored, ':id' => $id]);

        \App\Services\AuditService::log('plan.qr.updated', (int) ($_SESSION['user_id'] ?? 0), 'plan', $id, [
            'qr_changed' => true,
        ]);

        set_flash('success', 'QR pelan berjaya dikemaskini.');
        $this->redirect('/admin/plans/' . $id . '/edit');
    }

    private function deletePlanQrFile(string $storedName): void
    {
        if (!preg_match('/^[A-Za-z0-9._-]+$/', $storedName)) {
            return;
        }
        if (!str_starts_with($storedName, 'qr_')) {
            return;
        }
        $path = APP_ROOT . '/storage/uploads/brand/' . $storedName;
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public function generateSchedules(int $id): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
            set_flash('error', 'Sesi tidak sah. Sila cuba lagi.');
            $this->redirect('/admin/plans');
        }

        $result = $this->plans->generateSchedules($id);
        if (!$result['ok']) {
            set_flash('error', $result['error'] ?? 'Gagal menjana jadual.');
            $this->redirect('/admin/plans');
        }

        set_flash('success', 'Jadual caruman berjaya dijana (' . ($result['count'] ?? 0) . ' rekod).');
        $this->redirect('/admin/plans');
    }

    public function changeStatus(int $id): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
            set_flash('error', 'Sesi tidak sah. Sila cuba lagi.');
            $this->redirect('/admin/plans');
        }

        $status = trim((string) ($_POST['status'] ?? ''));
        $allowed = ['draft', 'open', 'active', 'paused', 'closed'];
        if (!in_array($status, $allowed, true)) {
            set_flash('error', 'Status tidak sah.');
            $this->redirect('/admin/plans');
        }

        $result = $this->plans->changeStatus($id, $status);
        if (!$result['ok']) {
            set_flash('error', $result['error'] ?? 'Gagal menukar status.');
            $this->redirect('/admin/plans');
        }

        set_flash('success', 'Status pelan dikemaskini kepada ' . e($status) . '.');
        $this->redirect('/admin/plans');
    }

    // ----------------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------------

    private function currentMember(): ?\App\Models\Member
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId === 0) {
            return null;
        }
        return $this->members->findByUserId($userId);
    }

    /**
     * @return array<string, mixed>
     */
    private function collectPlanInput(): array
    {
        $boolFields = ['approval_required', 'allow_multiple', 'withdrawal_allowed'];
        $data = [
            'name'                => trim((string) ($_POST['name'] ?? '')),
            'plan_code'           => trim((string) ($_POST['plan_code'] ?? '')),
            'description'         => trim((string) ($_POST['description'] ?? '')),
            'contribution_amount' => trim((string) ($_POST['contribution_amount'] ?? '')),
            'number_of_members'   => (int) ($_POST['number_of_members'] ?? 0),
            'payment_frequency'   => trim((string) ($_POST['payment_frequency'] ?? 'monthly')),
            'number_of_cycles'    => (int) ($_POST['number_of_cycles'] ?? 0),
            'start_date'          => trim((string) ($_POST['start_date'] ?? '')),
            'end_date'            => trim((string) ($_POST['end_date'] ?? '')) ?: null,
            'payout_mode'         => trim((string) ($_POST['payout_mode'] ?? 'fixed')),
            'fixed_payout_amount' => trim((string) ($_POST['fixed_payout_amount'] ?? '')),
            'payout_frequency'    => trim((string) ($_POST['payout_frequency'] ?? 'monthly')),
            'max_members'         => (int) ($_POST['max_members'] ?? 0),
            'min_credit_score'    => (int) ($_POST['min_credit_score'] ?? 0),
            'status'              => trim((string) ($_POST['status'] ?? 'draft')),
        ];
        foreach ($boolFields as $f) {
            $data[$f] = isset($_POST[$f]) && (string) $_POST[$f] === '1';
        }
        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return string[]
     */
    private function validatePlanInput(array $data, bool $isUpdate = false): array
    {
        $errors = [];
        if ($data['name'] === '') {
            $errors[] = 'Nama pelan diperlukan.';
        }
        if ($data['contribution_amount'] === '' || !is_numeric($data['contribution_amount']) || (float) $data['contribution_amount'] < 0) {
            $errors[] = 'Jumlah caruman mestilah nombor yang sah.';
        }
        if ($data['number_of_cycles'] <= 0) {
            $errors[] = 'Bilangan kitaran mestilah sekurang-kurangnya 1.';
        }
        if (!$isUpdate && $data['start_date'] === '') {
            $errors[] = 'Tarikh mula diperlukan.';
        }
        $freq = ['weekly', 'biweekly', 'monthly', 'quarterly'];
        if (!in_array($data['payment_frequency'], $freq, true)) {
            $errors[] = 'Kekerapan pembayaran tidak sah.';
        }
        if (!in_array($data['payout_mode'], ['fixed', 'collection'], true)) {
            $errors[] = 'Mod pembayaran tidak sah.';
        }
        if ($data['payout_mode'] === 'fixed' && ($data['fixed_payout_amount'] === '' || !is_numeric($data['fixed_payout_amount']))) {
            $errors[] = 'Jumlah pembayaran tetap diperlukan untuk mod tetap.';
        }
        return $errors;
    }
}
