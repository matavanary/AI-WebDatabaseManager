<div class="login-container">
    <div class="login-brand"><i class="fas fa-database" aria-hidden="true"></i><span>DB Manager</span></div>
    <div class="login-header">
        <h1 class="mb-0">Welcome back.</h1>
        <p>Sign in to your database workspace.</p>
    </div>

    <?php if (isset($error) && $error): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-circle me-2" aria-hidden="true"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= \App\Core\Application::asset('login') ?>">
        <div class="mb-3">
            <label for="username" class="form-label text-secondary">Username</label>
            <div class="input-group">
                <span class="input-group-text bg-transparent border-end-0 border-secondary"><i class="fas fa-user text-secondary" aria-hidden="true"></i></span>
                <input type="text" class="form-control border-start-0" id="username" name="username" required autocomplete="username" placeholder="Enter your username" autocapitalize="none" spellcheck="false">
            </div>
        </div>

        <div class="mb-4">
            <label for="password" class="form-label text-secondary">Password</label>
            <div class="input-group">
                <span class="input-group-text bg-transparent border-end-0 border-secondary"><i class="fas fa-lock text-secondary" aria-hidden="true"></i></span>
                <input type="password" class="form-control border-start-0" id="password" name="password" required autocomplete="current-password" placeholder="Enter your password">
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
            Sign In <i class="fas fa-arrow-right ms-2" aria-hidden="true"></i>
        </button>
    </form>
    <p class="login-footer">MySQL · MariaDB · SQL Server</p>
</div>
