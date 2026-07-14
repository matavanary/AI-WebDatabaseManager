<div class="login-container">
    <div class="login-header">
        <i class="fas fa-database"></i>
        <h3 class="mb-0">DB Manager</h3>
        <p class="text-secondary mt-2">Sign in to manage your databases</p>
    </div>
    
    <?php if (isset($error) && $error): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= \App\Core\Application::asset('login') ?>">
        <div class="mb-3">
            <label for="username" class="form-label text-secondary">Username</label>
            <div class="input-group">
                <span class="input-group-text bg-transparent border-end-0 border-secondary"><i class="fas fa-user text-secondary"></i></span>
                <input type="text" class="form-control border-start-0" id="username" name="username" required autofocus autocomplete="username">
            </div>
        </div>
        
        <div class="mb-4">
            <label for="password" class="form-label text-secondary">Password</label>
            <div class="input-group">
                <span class="input-group-text bg-transparent border-end-0 border-secondary"><i class="fas fa-lock text-secondary"></i></span>
                <input type="password" class="form-control border-start-0" id="password" name="password" required autocomplete="current-password">
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
            Sign In <i class="fas fa-arrow-right ms-2"></i>
        </button>
    </form>
</div>
