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

        <!-- Reset Data Card (Danger Zone) -->
        <div class="card" style="padding: 1.5rem 1.75rem; border: 2px solid #fecaca; border-radius: 12px; background: #fef2f2; margin-bottom: 2rem;">
            <div style="display: flex; align-items: flex-start; gap: 1rem;">
                <div style="width: 52px; height: 52px; border-radius: 10px; background: #fee2e2; color: #b91c1c; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                </div>
                <div style="flex: 1;">
                    <h2 style="margin: 0; font-size: 1.2rem; font-weight: 700; color: #991b1b;">Zon Bahaya — Reset Data Sistem</h2>
                    <p class="muted" style="margin-top: 0.35rem; line-height: 1.6; color: #7f1d1d;">
                        Tindakan ini akan <strong>mengosongkan semua rekod</strong> dalam pangkalan data termasuk ahli, pelan, jadual, caruman, payout, transaksi, dan lain-lain.
                        <br>
                        <strong>Dipelihara:</strong> Akaun pentadbir &amp; staf (jadual <code>users</code>, <code>roles</code>, <code>user_roles</code>, <code>sessions</code>, <code>password_resets</code>) supaya anda boleh log masuk semula selepas reset.
                    </p>
                    <button type="button" class="btn btn-danger" onclick="document.getElementById('resetDataModal').style.display = 'flex';" style="background: #dc2626; color: #fff; border: 0; display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.7rem 1.2rem; font-weight: 600;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                            <path d="M10 11v6"/>
                            <path d="M14 11v6"/>
                            <path d="M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/>
                        </svg>
                        <span>Reset Semua Data Sekarang</span>
                    </button>
                </div>
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

<!-- Reset Data Confirmation Modal -->
<div id="resetDataModal" role="dialog" aria-modal="true" aria-labelledby="resetDataModalTitle" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 1000; align-items: center; justify-content: center; padding: 1rem; backdrop-filter: blur(4px);">
    <div style="background: #ffffff; max-width: 520px; width: 100%; border-radius: 14px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4); overflow: hidden; animation: modalPop 200ms ease-out;">
        <div style="padding: 1.5rem 1.75rem; background: linear-gradient(135deg, #dc2626, #991b1b); color: #fff; display: flex; align-items: center; gap: 0.85rem;">
            <div style="width: 44px; height: 44px; border-radius: 50%; background: rgba(255,255,255,0.18); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
            <div>
                <h3 id="resetDataModalTitle" style="margin: 0; font-size: 1.2rem; font-weight: 700;">AMARAN: Tindakan Tidak Boleh Dibatalkan</h3>
                <p style="margin: 0.15rem 0 0; font-size: 0.85rem; opacity: 0.9;">Sila baca dengan teliti sebelum meneruskan.</p>
            </div>
        </div>

        <div style="padding: 1.5rem 1.75rem;">
            <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 0.85rem 1rem; border-radius: 6px; margin-bottom: 1.25rem; color: #78350f;">
                <strong>Adakah anda pasti?</strong> Semua rekod (ahli, pelan, jadual, caruman, payout, transaksi) akan <strong>dihapuskan secara kekal</strong> dan tidak boleh dipulihkan.
            </div>

            <ul style="margin: 0 0 1.25rem 0; padding-left: 1.25rem; line-height: 1.7; color: #475569;">
                <li>Hanya akaun pentadbir &amp; staf akan dipelihara (<code>users</code>, <code>roles</code>, <code>sessions</code>).</li>
                <li>Tindakan ini akan direkodkan dalam log audit keselamatan.</li>
                <li>Disyorkan supaya <strong>membuat eksport SQL</strong> terlebih dahulu sebagai sandaran.</li>
            </ul>

            <form method="POST" action="<?= url('/admin/settings/database/reset') ?>" id="resetDataForm" onsubmit="return confirm('PENGESAHAN AKHIR: Adakah anda benar-benar pasti mahu menghapuskan semua data sistem? Tindakan ini TIDAK BOLEH DIPULIHKAN.');">
                <?= csrf_field() ?>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label for="confirm_password" class="form-label" style="font-weight: 600; color: #991b1b;">Masukkan Kata Laluan Pentadbir Untuk Pengesahan</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required autocomplete="current-password" placeholder="Kata laluan anda" style="padding: 0.65rem 0.85rem; font-size: 1rem;">
                    <p class="form-help" style="color: #7f1d1d; margin-top: 0.4rem; font-size: 0.82rem;">Hanya pentadbir yang sedang log masuk boleh melakukan reset.</p>
                </div>

                <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('resetDataModal').style.display = 'none'; document.getElementById('confirm_password').value = '';">Batal</button>
                    <button type="submit" class="btn btn-danger" style="background: #dc2626; color: #fff; border: 0; display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.7rem 1.2rem; font-weight: 600;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                            <path d="M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/>
                        </svg>
                        <span>Ya, Reset Semua Data</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
@keyframes modalPop {
    from { transform: scale(0.92); opacity: 0; }
    to   { transform: scale(1); opacity: 1; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('resetDataModal');
    if (!modal) return;

    // Close on backdrop click
    modal.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
            var input = document.getElementById('confirm_password');
            if (input) input.value = '';
        }
    });

    // Close on ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && modal.style.display === 'flex') {
            modal.style.display = 'none';
            var input = document.getElementById('confirm_password');
            if (input) input.value = '';
        }
    });
});
</script>
