<?php
/** This legacy dashboard view is retained only for direct template compatibility. */
?>
<div class="card">
    <h1 class="card-title">Ringkasan Laporan</h1>
    <p class="muted">Gunakan laporan kewangan, pelan, atau ahli daripada menu Laporan.</p>
    <div class="actions mt-4">
        <a href="<?= url('/admin/reports/financial') ?>" class="btn btn-primary">Laporan Kewangan</a>
        <a href="<?= url('/admin/reports/plans') ?>" class="btn btn-secondary">Laporan Pelan</a>
        <a href="<?= url('/admin/reports/members') ?>" class="btn btn-secondary">Laporan Ahli</a>
    </div>
</div>
