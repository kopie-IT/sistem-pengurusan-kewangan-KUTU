<?php
/** @var array $plans */
?>
<section class="section" style="padding-top: 2.5rem;">
    <div class="container">
        <?= flash_messages() ?>

        <div class="page-header">
            <div>
                <span class="page-eyebrow">Pengeluaran</span>
                <h1>Permintaan Pengeluaran</h1>
                <p class="muted">Mohon pengeluaran daripada pelan aktif anda.</p>
            </div>
            <div class="actions">
                <a href="<?= url('/withdrawals/me') ?>" class="btn btn-secondary">Sejarah Saya</a>
            </div>
        </div>

        <?php if (empty($plans)): ?>
            <div class="card">
                <div class="empty-state">Anda tiada pelan aktif yang membenarkan pengeluaran.</div>
            </div>
        <?php else: ?>
            <div class="card">
                <form method="POST" action="<?= url('/withdrawals/request') ?>" novalidate>
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label for="plan_id" class="form-label">Pelan</label>
                        <select id="plan_id" name="plan_id" class="form-control" required>
                            <option value="">Pilih pelan</option>
                            <?php foreach ($plans as $p): ?>
                                <option value="<?= e((string) $p->id) ?>">
                                    <?= e($p->name) ?><?= isset($p->outstanding) ? ' (Tertunggak: ' . format_money($p->outstanding) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="reason" class="form-label">Sebab</label>
                        <textarea id="reason" name="reason" class="form-control" rows="4" placeholder="Nyatakan sebab permintaan pengeluaran" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg">Hantar Permintaan</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</section>
