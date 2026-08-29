<?php
/** @var array $dbTables */
$totalTables = count($dbTables ?? []);
?>
<section class="section" style="padding-top: 2.5rem;">
    <div class="container">
        <?= flash_messages() ?>

        <div class="page-header">
            <div>
                <span class="page-eyebrow">Sistem &amp; Pangkalan Data</span>
                <h1>Pangkalan Data &amp; Sandaran</h1>
                <p class="muted">Eksport sandaran pangkalan data dalam format SQL, atau pulihkan data dari fail sandaran yang telah sedia ada.</p>
            </div>
            <div class="actions">
                <a href="<?= url('/admin/settings') ?>" class="btn btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.25rem;"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                    <span>Kembali ke Tetapan</span>
                </a>
            </div>
        </div>

        <!-- Stat Card: Inventory -->
        <div class="grid grid-3" style="margin-bottom: 1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
            <div class="card" style="padding: 1.25rem; border-left: 4px solid var(--primary, #3b82f6); background: var(--bg-surface, #fff);">
                <span class="muted small" style="font-weight: 500;">Bilangan Jadual Sistem</span>
                <strong style="display: block; font-size: 1.5rem; color: #1e293b; margin-top: 0.25rem;"><?= e((string) $totalTables) ?> Jadual</strong>
                <span class="muted small" style="margin-top: 0.25rem; display: block;">Inventori terkini dari pangkalan data</span>
            </div>
            <div class="card" style="padding: 1.25rem; border-left: 4px solid #10b981; background: var(--bg-surface, #fff);">
                <span class="muted small" style="font-weight: 500;">Format Eksport</span>
                <strong style="display: block; font-size: 1.15rem; color: #16a34a; margin-top: 0.25rem;">SQL Dump Lengkap</strong>
                <span class="muted small" style="margin-top: 0.25rem; display: block;">Skema + data semua jadual</span>
            </div>
            <div class="card" style="padding: 1.25rem; border-left: 4px solid #f59e0b; background: var(--bg-surface, #fff);">
                <span class="muted small" style="font-weight: 500;">Had Saiz Import</span>
                <strong style="display: block; font-size: 1.15rem; color: #d97706; margin-top: 0.25rem;">Maksimum 25 MB</strong>
                <span class="muted small" style="margin-top: 0.25rem; display: block;">Format fail: <code>.sql</code></span>
            </div>
        </div>

        <!-- Export & Import Action Cards -->
        <div class="grid grid-2" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; align-items: stretch;">
            <!-- Export Card -->
            <div class="card" style="padding: 1.75rem; border: 1px solid #e2e8f0; border-radius: 12px; background: #ffffff; display: flex; flex-direction: column; box-shadow: var(--shadow-sm, 0 1px 2px rgba(0,0,0,0.05));">
                <div style="display: flex; align-items: center; gap: 0.85rem; margin-bottom: 1rem;">
                    <div style="width: 48px; height: 48px; border-radius: 10px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="7 10 12 15 17 10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                    </div>
                    <div>
                        <h2 style="margin: 0; font-size: 1.15rem; font-weight: 600; color: #0f172a;">Eksport Pangkalan Data (SQL)</h2>
                        <p class="muted small" style="margin: 0.15rem 0 0 0;">Muat turun salinan sandaran penuh.</p>
                    </div>
                </div>

                <p class="muted" style="margin-bottom: 1.5rem; line-height: 1.6;">
                    Menghasilkan fail <code>.sql</code> yang mengandungi skema penuh dan rekod data terkini. Fail ini boleh disimpan sebagai sandaran atau digunakan untuk migrasi ke pelayan lain.
                </p>

                <ul class="muted small" style="margin: 0 0 1.5rem 0; padding-left: 1.25rem; line-height: 1.7;">
                    <li>Struktur setiap jadual akan disertakan (<code>CREATE TABLE</code>).</li>
                    <li>Semua rekod akan dimasukkan semula menggunakan <code>INSERT</code>.</li>
                    <li>FOREIGN_KEY_CHECKS akan dinyah-aktifkan sementara untuk integriti.</li>
                </ul>

                <div style="margin-top: auto;">
                    <a href="<?= url('/admin/settings/database/export') ?>" class="btn btn-primary" style="display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; width: 100%; padding: 0.75rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="7 10 12 15 17 10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        <span>Muat Turun Eksport SQL</span>
                    </a>
                </div>
            </div>

            <!-- Import Card -->
            <div class="card" style="padding: 1.75rem; border: 1px solid #fed7aa; border-radius: 12px; background: #fffaf5; display: flex; flex-direction: column; box-shadow: var(--shadow-sm, 0 1px 2px rgba(0,0,0,0.05));">
                <div style="display: flex; align-items: center; gap: 0.85rem; margin-bottom: 1rem;">
                    <div style="width: 48px; height: 48px; border-radius: 10px; background: #fff7ed; color: #ea580c; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="17 8 12 3 7 8"/>
                            <line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                    </div>
                    <div>
                        <h2 style="margin: 0; font-size: 1.15rem; font-weight: 600; color: #9a3412;">Import Pangkalan Data (SQL)</h2>
                        <p class="muted small" style="margin: 0.15rem 0 0 0; color: #c2410c;">Pulihkan data dari fail sandaran <code>.sql</code></p>
                    </div>
                </div>

                <div class="small" style="background: #fef3c7; border-left: 3px solid #f59e0b; padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1.25rem; color: #78350f;">
                    <strong>Amaran Keselamatan:</strong> Memasukkan fail SQL akan mengubah atau menggantikan rekod pangkalan data semasa. Pastikan sandaran telah dibuat terlebih dahulu sebelum meneruskan.
                </div>

                <form action="<?= url('/admin/settings/database/import') ?>" method="POST" enctype="multipart/form-data" onsubmit="return confirm('AMARAN: Adakah anda pasti mahu mengimport fail SQL ini ke dalam pangkalan data? Rekod sedia ada mungkin terjejas.');" style="display: flex; flex-direction: column; flex: 1;">
                    <?= csrf_field() ?>

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label for="sql_file" class="form-label" style="font-weight: 600; color: #7c2d12;">Pilih Fail SQL (.sql)</label>
                        <input type="file" id="sql_file" name="sql_file" accept=".sql,text/plain" required class="form-control" style="padding: 0.5rem; background: #fff;">
                        <p class="form-help small" style="color: #9a3412; margin-top: 0.4rem;">Saiz maksimum: 25 MB. Hanya fail berformat <code>.sql</code> dibenarkan.</p>
                    </div>

                    <ul class="muted small" style="margin: 0 0 1.25rem 0; padding-left: 1.25rem; line-height: 1.7; color: #7c2d12;">
                        <li>Import dijalankan dalam transaksi atomik — sama ada semua berjaya, atau tiada perubahan.</li>
                        <li>FOREIGN_KEY_CHECKS dinyah-aktifkan buat sementara waktu untuk kelancaran import.</li>
                        <li>Aktiviti import akan direkodkan di log audit keselamatan.</li>
                    </ul>

                    <div style="margin-top: auto;">
                        <button type="submit" class="btn btn-danger" style="background: #dc2626; color: #fff; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; width: 100%; padding: 0.75rem; border: 0;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="17 8 12 3 7 8"/>
                                <line x1="12" y1="3" x2="12" y2="15"/>
                            </svg>
                            <span>Mulakan Import SQL</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Database Inventory -->
        <?php if (!empty($dbTables)): ?>
            <div class="card" style="padding: 1.5rem; background: var(--bg-surface, #fff);">
                <h2 class="card-title" style="margin-top: 0; font-size: 1.15rem;">Inventori Jadual Pangkalan Data</h2>
                <p class="muted small" style="margin-top: -0.5rem; margin-bottom: 1rem;">Senarai jadual yang akan terlibat dalam eksport & import.</p>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">#</th>
                                <th>Nama Jadual</th>
                                <th>Kategori</th>
                                <th style="text-align: right;">Rekod</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dbTables as $i => $t): ?>
                                <tr>
                                    <td class="muted small"><?= e((string) ($i + 1)) ?></td>
                                    <td><code style="background: var(--color-slate-50, #f8fafc); padding: 0.15rem 0.45rem; border-radius: 4px; font-size: 0.85rem;"><?= e($t['name'] ?? '-') ?></code></td>
                                    <td style="text-align: right;" class="muted small"><?= e(ucfirst($t['category'] ?? 'lain-lain')) ?></td>
                                    <td style="text-align: right;" class="muted small"><?= isset($t['rows']) ? number_format((int) $t['rows']) : '-' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
