<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$pageTitle = 'Login';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/header.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-sm-10 col-md-7 col-lg-5">

      <?php if (isset($_SESSION['login_error'])): ?>
        <div class="alert alert-danger alert-auto-dismiss"><?php echo $_SESSION['login_error']; unset($_SESSION['login_error']); ?></div>
      <?php endif; ?>
      <?php if (isset($_SESSION['register_success'])): ?>
        <div class="alert alert-success alert-auto-dismiss"><?php echo $_SESSION['register_success']; unset($_SESSION['register_success']); ?></div>
      <?php endif; ?>

      <div class="auth-card">
        <div class="text-center mb-3">
          <img src="<?php echo BASE_URL; ?>/assets/images/logo.svg" alt="Epsilon" width="56" height="56">
        </div>
        <h4 class="fw-bold text-center mb-1">Welcome Back</h4>
        <p class="text-muted text-center mb-4">Login to your Epsilon account</p>

        <form method="POST" action="<?php echo BASE_URL; ?>/auth/login_process.php" novalidate>
          <div class="mb-3">
            <label for="login_id" class="form-label">Email or Student ID</label>
            <input type="text" class="form-control" id="login_id" name="login_id"
                   placeholder="e.g. 242-15-782" required autofocus>
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
              <input type="password" class="form-control" id="password" name="password"
                     placeholder="Enter your password" required>
              <button type="button" class="btn btn-outline-secondary toggle-password"
                      data-target="password" title="Show password">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>
          <button type="submit" class="btn btn-brand w-100">Login</button>
        </form>

        <p class="text-center small text-muted mt-4 mb-0">
          Don't have an account? <a href="<?php echo BASE_URL; ?>/auth/register.php">Register here</a>
        </p>
      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
