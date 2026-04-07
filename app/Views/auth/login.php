<?php $page_title = 'Login'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PM Box</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <h1 class="auth-title">PM Box</h1>
            <p class="auth-subtitle">Sign in to your account</p>
            
            <?php if (isset($flash_error)): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($flash_error) ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($flash_success)): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($flash_success) ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="/login" class="auth-form">
                <?= CSRF::inputField() ?>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           placeholder="you@example.com">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="••••••••">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Sign In</button>
            </form>
            
            <p class="auth-footer">
                Don't have an account? <a href="/register">Register</a>
            </p>
        </div>
    </div>
</body>
</html>
