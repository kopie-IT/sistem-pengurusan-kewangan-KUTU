<section class="section" style="padding-top: 2.5rem;">
    <div class="container container-narrow">
        <?= flash_messages() ?>

        <div class="page-header">
            <div>
                <span class="page-eyebrow">Akaun</span>
                <h1>Tukar Kata Laluan</h1>
                <p class="muted">Pastikan kata laluan baru sukar diteka dan tidak digunakan di tempat lain.</p>
            </div>
            <div class="actions">
                <a href="<?= url('/profile') ?>" class="btn btn-secondary">Kembali ke Profil</a>
            </div>
        </div>

        <div class="card">
            <form method="POST" action="<?= url('/profile/change-password') ?>" novalidate>
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="current_password" class="form-label">Kata Laluan Semasa</label>
                    <div class="input-affix">
                        <input type="password" id="current_password" name="current_password"
                               class="form-control" required autocomplete="current-password">
                        <button type="button" class="input-affix-btn" data-password-toggle="current_password"
                                aria-label="Papar kata laluan" aria-pressed="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-eye" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-eye-off" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="new_password" class="form-label">Kata Laluan Baru</label>
                    <div class="input-affix">
                        <input type="password" id="new_password" name="new_password"
                               class="form-control" required minlength="8" autocomplete="new-password">
                        <button type="button" class="input-affix-btn" data-password-toggle="new_password"
                                aria-label="Papar kata laluan" aria-pressed="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-eye" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-eye-off" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>
                    <p class="form-help">Sekurang-kurangnya 8 aksara.</p>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">Sahkan Kata Laluan Baru</label>
                    <div class="input-affix">
                        <input type="password" id="confirm_password" name="confirm_password"
                               class="form-control" required minlength="8" autocomplete="new-password">
                        <button type="button" class="input-affix-btn" data-password-toggle="confirm_password"
                                aria-label="Papar kata laluan" aria-pressed="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-eye" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-eye-off" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">Tukar Kata Laluan</button>
            </form>
        </div>
    </div>
</section>
