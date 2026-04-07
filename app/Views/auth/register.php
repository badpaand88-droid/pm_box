<?php $page_title = 'Register'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - PM Box</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <h1 class="auth-title">Create Account</h1>
            <p class="auth-subtitle">Join PM Box today</p>
            
            <?php if (isset($flash_error)): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($flash_error) ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="/register" class="auth-form">
                <?= CSRF::inputField() ?>
                
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" required 
                           value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                           placeholder="John Doe">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           placeholder="you@example.com">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required 
                           minlength="6" placeholder="••••••••">
                </div>
                
                <div class="form-group">
                    <label for="password_confirm">Confirm Password</label>
                    <input type="password" id="password_confirm" name="password_confirm" required 
                           minlength="6" placeholder="••••••••">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Create Account</button>
            </form>
            
            <p class="auth-footer">
                Already have an account? <a href="/login">Sign in</a>
            </p>
        </div>
    </div>
</body>
</html>
