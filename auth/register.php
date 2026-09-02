<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$pageTitle = 'Register';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/header.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-sm-11 col-md-9 col-lg-7">

      <?php if (isset($_SESSION['register_error'])): ?>
        <div class="alert alert-danger alert-auto-dismiss"><?php echo $_SESSION['register_error']; unset($_SESSION['register_error']); ?></div>
      <?php endif; ?>

      <div class="auth-card">
        <div class="text-center mb-3">
          <img src="<?php echo BASE_URL; ?>/assets/images/logo.svg" alt="Epsilon" width="56" height="56">
        </div>
        <h4 class="fw-bold text-center mb-1">Create Your Account</h4>
        <p class="text-muted text-center mb-4">Join the Epsilon student marketplace</p>

        <form method="POST" action="<?php echo BASE_URL; ?>/auth/register_process.php" novalidate>
          <!--
            Every hint lives inside its own box as a placeholder, so the form
            stays one clean grid with no text hanging underneath the fields.
            If a rule is broken the browser says exactly what is wrong.
          -->
          <div class="row g-3">
            <!-- Group 1: who you are at university -->
            <div class="col-12">
              <div class="form-section-title">Student Information</div>
            </div>

            <div class="col-md-6">
              <label for="student_id" class="form-label">Student ID</label>
              <input type="text" class="form-control" id="student_id" name="student_id"
                     placeholder="e.g. 242-15-782" required>
            </div>
            <div class="col-md-6">
              <label for="full_name" class="form-label">Full Name</label>
              <input type="text" class="form-control" id="full_name" name="full_name" required>
            </div>
            <div class="col-md-6">
              <label for="department" class="form-label">Department</label>
              <!-- A fixed list, so everyone in the same department is stored
                   under exactly the same name -->
              <select class="form-select" id="department" name="department" required>
                <option value="">Select your department</option>
                <?php foreach (DEPARTMENTS as $dept): ?>
                  <option value="<?php echo sanitize($dept); ?>"><?php echo sanitize($dept); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label for="batch" class="form-label">Batch</label>
              <input type="text" class="form-control" id="batch" name="batch" required>
            </div>
            <!-- Group 2: what you log in with -->
            <div class="col-12">
              <div class="form-section-title">Account Details</div>
            </div>

            <div class="col-md-6">
              <label for="email" class="form-label">Email</label>
              <input type="email" class="form-control" id="email" name="email"
                     placeholder="e.g. rafiul@student.edu" required>
            </div>
            <div class="col-md-6">
              <label for="phone" class="form-label">Phone Number</label>
              <input type="tel" class="form-control" id="phone" name="phone"
                     placeholder="11 digits" inputmode="numeric"
                     pattern="[0-9]{11}" minlength="11" maxlength="11" required>
            </div>

            <div class="col-md-6">
              <label for="password" class="form-label">Password</label>
              <!-- input-group puts the show/hide eye button beside the field -->
              <div class="input-group">
                <input type="password" class="form-control" id="password" name="password"
                       placeholder="6+ chars, 1 symbol" minlength="6" required>
                <button type="button" class="btn btn-outline-secondary toggle-password"
                        data-target="password" title="Show password">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>

            <div class="col-md-6">
              <label for="confirm_password" class="form-label">Confirm Password</label>
              <div class="input-group">
                <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                       placeholder="Re-type password" minlength="6" required>
                <button type="button" class="btn btn-outline-secondary toggle-password"
                        data-target="confirm_password" title="Show password">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
              <!-- the only text kept below: it appears live as you type -->
              <div class="form-text" id="passwordMatchHint"></div>
            </div>
          </div>

          <button type="submit" class="btn btn-brand w-100 mt-4">Register</button>
        </form>

        <p class="text-center small text-muted mt-4 mb-0">
          Already have an account? <a href="<?php echo BASE_URL; ?>/auth/login.php">Login here</a>
        </p>
      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
