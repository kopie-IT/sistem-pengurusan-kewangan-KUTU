<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\EmailBlastRepository;

/**
 * Sends email blasts (admin → users) using PHP's native mail() (configurable
 * upstream by the SMTP/Mail config layer). The same path can later be swapped
 * for Symfony Mailer / PHPMailer without changing controllers.
 *
 * Messages are also mirrored into the `notifications` table so the in-app
 * inbox always has a copy.
 */
final class EmailBlastService
{
    public function __construct(
        private EmailBlastRepository $blasts,
        private NotificationService $notifications,
        private SystemSettingService $settings,
    ) {}

    /**
     * Send a broadcast and persist it.
     *
     * @return array{ok: bool, id?: int, count?: int, error?: string}
     */
    public function send(
        string $subject,
        string $message,
        string $targetRole,
        int $createdBy
    ): array {
        if (trim($subject) === '' || trim($message) === '') {
            return ['ok' => false, 'error' => 'Subjek dan mesej diperlukan.'];
        }

        $allowedRoles = ['all', 'admin', 'super_admin', 'staff', 'member'];
        if (!in_array($targetRole, $allowedRoles, true)) {
            $targetRole = 'all';
        }

        $blastId = $this->blasts->create([
            'subject'         => $subject,
            'message'         => $message,
            'target_role'     => $targetRole,
            'recipient_count' => 0,
            'status'          => 'queued',
            'created_by'      => $createdBy,
        ]);

        $recipients = $this->resolveRecipients($targetRole);
        if ($recipients === []) {
            $this->blasts->updateStatus($blastId, 'failed', 0);
            return ['ok' => false, 'error' => 'Tiada penerima ditemui.', 'id' => $blastId];
        }

        $fromName  = $this->settings->get('email_blast_from_name', brand_name()) ?: brand_name();
        $fromEmail = $this->settings->get('email_blast_from_email', '') ?: ('noreply@' . preg_replace('#^https?://#', '', url('/')));
        $replyTo   = $this->settings->get('email_blast_reply_to', '') ?: '';
        $footer    = $this->settings->get('email_blast_footer', '') ?: '';

        $body = $message . ($footer !== '' ? "\n\n--\n" . $footer : '');

        $headers   = [];
        $headers[] = 'From: ' . sprintf('%s <%s>', $fromName, $fromEmail);
        $headers[] = 'X-Mailer: PHP/' . PHP_VERSION;
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        if ($replyTo !== '') {
            $headers[] = 'Reply-To: ' . $replyTo;
        }
        $headerString = implode("\r\n", $headers);

        $sent = 0;
        foreach ($recipients as $row) {
            $to = (string) $row['email'];
            $ok = @mail($to, $subject, $body, $headerString);
            if ($ok) {
                $sent++;
            }
            // Mirror into the in-app notifications inbox.
            $this->notifications->notify(
                (int) $row['id'],
                'system.email_blast',
                $subject,
                mb_substr($body, 0, 280),
                ['type' => 'email_blast', 'id' => $blastId]
            );
        }

        $status = $sent === count($recipients) ? 'sent' : ($sent === 0 ? 'failed' : 'partial');
        $this->blasts->updateStatus($blastId, $status, $sent);

        AuditService::log('email.blast.sent', $createdBy, 'email_blasts', $blastId, [
            'target'     => $targetRole,
            'recipients' => $sent,
            'status'     => $status,
        ]);

        return ['ok' => true, 'id' => $blastId, 'count' => $sent];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resolveRecipients(string $targetRole): array
    {
        $pdo = Database::connection();

        if ($targetRole === 'all') {
            $sql = 'SELECT id, email FROM users WHERE status = :active AND email <> "" ORDER BY id ASC';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':active' => 'active']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        $sql = 'SELECT u.id, u.email
                FROM users u
                INNER JOIN roles r ON r.id = u.role_id
                WHERE u.status = :active AND r.slug = :slug AND u.email <> ""
                ORDER BY u.id ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':active' => 'active', ':slug' => $targetRole]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
