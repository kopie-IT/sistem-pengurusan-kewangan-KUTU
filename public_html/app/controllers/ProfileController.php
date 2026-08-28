<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Repositories\MemberRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\AuditService;
use App\Services\CreditScoreService;
use PDO;

final class ProfileController extends Controller
{
    private const AVATAR_ALLOWED_EXT  = ['png', 'jpg', 'jpeg', 'webp', 'gif'];
    private const AVATAR_ALLOWED_MIME = [
        'image/png',
        'image/jpeg',
        'image/gif',
        'image/webp',
    ];
    private const AVATAR_MAX_BYTES = 2 * 1024 * 1024; // 2 MB

    public function __construct(
        private AuthService $auth,
        private MemberRepository $members,
        private UserRepository $users,
        private CreditScoreService $credit,
    ) {}

    private function currentMember(): ?\App\Models\Member
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        return $userId === 0 ? null : $this->members->findByUserId($userId);
    }

    public function index(): void
    {
        $user = $this->auth->currentUser();
        $member = $this->currentMember();
        $score = $member !== null ? $this->credit->getScore($member->id) : ['score' => 100, 'level' => 'excellent'];

        $avatarUrl = user_avatar_url((int) ($user->id ?? 0));

        $this->view('profile/index', [
            'title'      => 'Profil Saya',
            'user'       => $user,
            'member'     => $member,
            'score'      => $score,
            'avatarUrl'  => $avatarUrl,
            'avatarInitials' => user_initials($user->name ?? ''),
        ]);
    }

    public function update(): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
            set_flash('error', 'Sesi tidak sah. Sila cuba lagi.');
            $this->redirect('/profile');
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId === 0) {
            $this->redirect('/login');
        }

        $name    = trim((string) ($_POST['name'] ?? ''));
        $phone   = trim((string) ($_POST['phone'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));
        $ic      = trim((string) ($_POST['ic_number'] ?? ''));

        if ($name === '') {
            set_flash('error', 'Nama tidak boleh kosong.');
            $this->redirect('/profile');
        }
        if (mb_strlen($name) > 100) {
            set_flash('error', 'Nama melebihi 100 aksara.');
            $this->redirect('/profile');
        }
        if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{6,20}$/', $phone)) {
            set_flash('error', 'Nombor telefon tidak sah.');
            $this->redirect('/profile');
        }
        if ($ic !== '' && !preg_match('/^[0-9A-Za-z\-]{6,20}$/', $ic)) {
            set_flash('error', 'No. Kad Pengenalan tidak sah.');
            $this->redirect('/profile');
        }

        // Persist the new name on the user record + mirror to session so the
        // header avatar initials and greeting refresh immediately.
        $this->users->updateProfile($userId, $name);
        $_SESSION['user_name'] = $name;

        // Optional avatar upload (png/jpg/jpeg/webp/gif, ≤ 2 MB).
        $avatarError = null;
        if (!empty($_FILES['avatar']['tmp_name'] ?? '') && (int) ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $avatarError = $this->handleAvatarUpload($userId);
        } elseif (!empty($_FILES['avatar']['name'] ?? '') && (int) ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $avatarError = 'Gagal memuat naik avatar (kod ralat ' . (int) $_FILES['avatar']['error'] . ').';
        }

        $member = $this->members->findByUserId($userId);
        if ($member !== null) {
            $this->members->update($member->id, [
                'phone'      => $phone !== '' ? $phone : null,
                'address'    => $address !== '' ? $address : null,
                'ic_number'  => $ic !== '' ? $ic : null,
            ]);
        }

        AuditService::log('profile.updated', $userId, 'users', $userId, [
            'avatar_changed' => $avatarError === null && !empty($_FILES['avatar']['tmp_name'] ?? ''),
        ]);

        if ($avatarError !== null) {
            set_flash('warning', 'Profil disimpan, tetapi avatar: ' . $avatarError);
        } else {
            set_flash('success', 'Profil berjaya dikemaskini.');
        }
        $this->redirect('/profile');
    }

    public function changePassword(): void
    {
        $this->view('profile/change-password', [
            'title' => 'Tukar Kata Laluan',
        ]);
    }

    public function updatePassword(): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
            set_flash('error', 'Sesi tidak sah. Sila cuba lagi.');
            $this->redirect('/profile/change-password');
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $current = (string) ($_POST['current_password'] ?? '');
        $next    = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        if ($userId === 0) {
            $this->redirect('/login');
        }
        if ($current === '' || $next === '' || $confirm === '') {
            set_flash('error', 'Sila isi semua medan kata laluan.');
            $this->redirect('/profile/change-password');
        }
        if ($next !== $confirm) {
            set_flash('error', 'Kata laluan baru dan pengesahan tidak sepadan.');
            $this->redirect('/profile/change-password');
        }
        if (strlen($next) < 8) {
            set_flash('error', 'Kata laluan baru mestilah sekurang-kurangnya 8 aksara.');
            $this->redirect('/profile/change-password');
        }
        if ($current === $next) {
            set_flash('error', 'Kata laluan baru mestilah berbeza daripada yang semasa.');
            $this->redirect('/profile/change-password');
        }

        $user = $this->users->findById($userId);
        if ($user === null || !password_verify($current, $user->passwordHash)) {
            set_flash('error', 'Kata laluan semasa tidak betul.');
            $this->redirect('/profile/change-password');
        }

        $hash = password_hash($next, PASSWORD_BCRYPT);
        $this->users->updatePassword($userId, $hash, true);

        AuditService::log('profile.password.changed', $userId, 'users', $userId);

        set_flash('success', 'Kata laluan berjaya ditukar.');
        $this->redirect('/profile');
    }

    private function handleAvatarUpload(int $userId): ?string
    {
        $file = $_FILES['avatar'];
        if (!is_uploaded_file($file['tmp_name'])) {
            return 'Fail tidak sah.';
        }
        if ((int) $file['size'] > self::AVATAR_MAX_BYTES) {
            return 'Saiz avatar melebihi had 2 MB.';
        }

        $ext  = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        $mime = mime_content_type($file['tmp_name']) ?: '';
        if (!in_array($ext, self::AVATAR_ALLOWED_EXT, true) || !in_array($mime, self::AVATAR_ALLOWED_MIME, true)) {
            return 'Jenis fail tidak dibenarkan (png, jpg, jpeg, webp, gif).';
        }

        $dir = APP_ROOT . '/storage/uploads/avatars/';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $storedName = 'avatar_' . $userId . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $dest = $dir . $storedName;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return 'Gagal menyimpan avatar.';
        }

        $previous = $this->users->getAvatarPath($userId);
        if ($previous !== null && $previous !== $storedName) {
            $this->deleteAvatarFile($previous);
        }
        $this->users->updateAvatar($userId, $storedName);
        return null;
    }

    private function deleteAvatarFile(string $storedName): void
    {
        if (!preg_match('/^avatar_[A-Za-z0-9._-]+$/', $storedName)) {
            return;
        }
        $path = APP_ROOT . '/storage/uploads/avatars/' . $storedName;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
