<?php
/**
 * Reusable brand mark.
 *
 * Renders either the configured brand logo (if one has been uploaded by an
 * admin via /admin/settings) or a fallback text/initials mark.
 *
 * @param string $fallbackInitials Two-letter initials used when no logo exists.
 */
$fallbackInitials = $fallbackInitials ?? 'MK';
$logoUrl = $logoUrl ?? null;
?>
<span class="brand-mark">
    <?php if ($logoUrl !== null && $logoUrl !== ''): ?>
        <img src="<?= e($logoUrl) ?>" alt="" class="brand-mark-img" loading="lazy">
    <?php else: ?>
        <span class="brand-mark-text" aria-hidden="true"><?= e($fallbackInitials) ?></span>
    <?php endif; ?>
</span>
