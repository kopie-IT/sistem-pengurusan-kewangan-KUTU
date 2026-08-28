<div class="error-page">
    <div class="error-card">
        <div class="error-code">404</div>
        <h1>Halaman tidak dijumpai</h1>
        <p>Maaf, halaman yang anda cari tidak wujud atau telah dipindahkan.</p>
        <div style="display: inline-flex; gap: 0.5rem; flex-wrap: wrap; justify-content: center;">
            <a href="<?= url('/') ?>" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Kembali ke Utama
            </a>
            <a href="<?= url('/login') ?>" class="btn btn-secondary">Log Masuk</a>
        </div>
    </div>
</div>
