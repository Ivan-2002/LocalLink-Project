<?php
// Login + Register page, 
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
  if (isAdmin())  redirect(BASE_URL . '../admin/dashboard.php');
  redirect(BASE_URL . 'index.php');
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LocalLink — Login</title>

  <!-- 1. Preconnect (speed) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">

  <!-- 2. Google Fonts (your custom fonts) -->
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Serif+Display&display=swap" rel="stylesheet">

  <!-- 3. Bootstrap (framework) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

  <!-- 4. YOUR style.css LAST (so your styles override Bootstrap's defaults) -->
  <link rel="stylesheet" href="assets/css/style.css">

</head>

<body class="auth-page">

  <div class="auth-card">

    <span class="brand-icon">🛍️</span>
    <h1 class="auth-heading" id="cardHeading">Welcome Back</h1>
    <p class="auth-sub" id="cardSub">
      Don't have an account yet? <a href="#" class="switch-tab" data-tab="register">Sign up</a>
    </p>

    <!-- Tab toggle -->
    <div class="tab-row">
      <button class="tab-btn active" data-tab="login">Login</button>
      <button class="tab-btn" data-tab="register">Register</button>
    </div>

    <!-- Alert -->
    <div id="authAlert" class="alert auth-alert d-none" role="alert"></div>

    <!-- LOGIN FORM -->
    <form id="loginForm" class="auth-form">
      <div class="input-wrap">
        <span class="input-icon">✉️</span>
        <input type="email" name="email" class="form-control" placeholder="email address" required>
      </div>
      <div class="input-wrap">
        <span class="input-icon">🔒</span>
        <input type="password" name="password" class="form-control" placeholder="password" required>
      </div>
      <div class="extras-row">
        <label><input type="checkbox" checked> Remember me</label>
        <a href="#">Forgot password?</a>
      </div>
      <button type="submit" class="btn-primary-dark">Login</button>

      <div class="divider">or</div>
      <div class="social-row">
        <a href="#" class="social-btn" title="Continue with Google">
          <svg viewBox="0 0 24 24">
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4" />
            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853" />
            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05" />
            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335" />
          </svg>
        </a>
        <a href="#" class="social-btn" title="Continue with Facebook">
          <svg viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="12" fill="#1877F2" />
            <path d="M16.5 8H14c-.3 0-.5.2-.5.5V10h3l-.4 2.5H13.5V19h-3v-6.5H9V10h1.5V8.5C10.5 6.6 11.8 5 14 5h2.5v3z" fill="#fff" />
          </svg>
        </a>
        <a href="#" class="social-btn" title="Continue with Apple">
          <svg viewBox="0 0 24 24">
            <path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.7 9.05 7.4c1.32.07 2.24.75 3.04.8 1.14-.23 2.24-.93 3.45-.84 1.46.12 2.56.69 3.28 1.77-3 1.8-2.29 5.77.22 6.88-.57 1.53-1.33 3.04-2 4.27zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z" fill="#000" />
          </svg>
        </a>
      </div>
    </form>

    <!-- REGISTER FORM -->
    <form id="registerForm" class="auth-form d-none">
      <div class="input-wrap">
        <span class="input-icon">👤</span>
        <input type="text" name="name" class="form-control" placeholder="full name" required>
      </div>
      <div class="input-wrap">
        <span class="input-icon">✉️</span>
        <input type="email" name="email" class="form-control" placeholder="email address" required>
      </div>
      <div class="input-wrap">
        <span class="input-icon">🔒</span>
        <input type="password" name="password" class="form-control" placeholder="password (min. 6 chars)" required minlength="6">
      </div>
      <div class="input-wrap">
        <span class="input-icon">📍</span>
        <input type="text" name="location" class="form-control" placeholder="location (e.g. Soweto, GP)">
      </div>
      <button type="submit" class="btn-primary-dark">Create Account</button>
    </form>

  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="<?= BASE_URL ?>assets/js/auth.js"></script>
</body>

</html>