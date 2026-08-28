<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Config;
use App\Repositories\PaymentSlipRepository;

/**
 * Validates and stores uploaded payment / payout slips (PRD section 47).
 *
 * Files are validated for upload integrity, size, extension and MIME type
 * before being moved to a purpose-specific storage directory. All generated
 * file names are random and unguessable; original names are sanitised before
 * being stored as metadata only (never used for the on-disk path).
 */
final class FileUploadService
{
    private const ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'pdf'];
    private const ALLOWED_MIME = [
        'image/jpeg',
        'image/png',
        'application/pdf',
    ];

    public function __construct(
        private PaymentSlipRepository $slips,
    ) {}

    /**
     * Upload a slip from a $_FILES entry.
     *
     * @param array<string, mixed> $file
     * @return array{ok: bool, slip_id?: int, stored_name?: string, error?: string}
     */
    public function upload(array $file, string $purpose, ?int $memberId, ?int $uploadedBy): array
    {
        if (!isset($file['tmp_name'], $file['size'], $file['name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['ok' => false, 'error' => 'Fail tidak sah.'];
        }

        $maxBytes = (Config::getInstance()->getInt('MAX_UPLOAD_SIZE_MB', 5)) * 1024 * 1024;
        if ($file['size'] > $maxBytes) {
            return ['ok' => false, 'error' => 'Saiz fail melebihi had.'];
        }

        $originalName = is_string($file['name']) ? basename($file['name']) : '';
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $mime = is_string($file['type'] ?? null) ? $file['type'] : (function (string $tmp): string {
            $detected = mime_content_type($tmp);
            return $detected === false ? '' : $detected;
        })($file['tmp_name']);

        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            return ['ok' => false, 'error' => 'Jenis fail tidak dibenarkan.'];
        }

        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            return ['ok' => false, 'error' => 'Jenis fail tidak dibenarkan.'];
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
        $dir = APP_ROOT . '/storage/uploads/' . ($purpose === 'payout' ? 'payout-slips' : 'payment-slips') . '/';

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $dest = $dir . $storedName;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['ok' => false, 'error' => 'Gagal menyimpan fail.'];
        }

        $slipId = $this->slips->create([
            'member_id'     => $memberId,
            'stored_name'   => $storedName,
            'original_name' => $originalName,
            'mime_type'     => $mime,
            'size_bytes'    => (int) $file['size'],
            'purpose'       => $purpose === 'payout' ? 'payout' : 'contribution',
            'uploaded_by'   => $uploadedBy,
        ]);

        return ['ok' => true, 'slip_id' => $slipId, 'stored_name' => $storedName];
    }

    /**
     * Resolve the on-disk path for an uploaded slip. Returns null if the slip
     * or its file does not exist (used by the authenticated download endpoint).
     */
    public function getDownloadPath(int $slipId): ?string
    {
        $slip = $this->slips->findById($slipId);
        if ($slip === null) {
            return null;
        }

        $purpose = $slip->purpose === 'payout' ? 'payout-slips' : 'payment-slips';
        $path = APP_ROOT . '/storage/uploads/' . $purpose . '/' . $slip->storedName;

        if (!is_file($path)) {
            return null;
        }

        return $path;
    }
}
